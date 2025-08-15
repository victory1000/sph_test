<?php
include_once "steam_parser.php";

class SteamParserPuppeteer extends SteamParser {

  public function __construct() {
    parent::__construct();
  }

  protected function init(): void {
//    $this->sent_key = date('H');
//    $this->sent = json_decode($this->_redis->get($this->sent_key), true) ?? [];
    $this->price = json_decode($this->_redis->get('price'), true) ?? [];

    if (empty($this->price)) {
      foreach (Parser::getSkinsToParse() as $skin) {
        $r = Parser::curl_exec("https://steamcommunity.com/market/priceoverview/?market_hash_name=" . rawurlencode($skin) . "&appid=730&currency=5");
        $priceoverview = json_decode($r, true);
        $this->price[$skin] = Parser::toPrice($priceoverview['lowest_price'] ?? $priceoverview['median_price'] ?? 999);
      }
      $this->_redis->set('price', json_encode($this->price), 3600);
    }

    $di = (int)date('i', strtotime('now'));
    $dH = (int)date('H', strtotime('now'));
    if ($di > 58 && ($dH == 9 || $dH == 21)) $this->_redis->del('processed_listings');
  }

  protected function ParseSkins(): array {
    $redis_processed = json_decode($this->_redis->get('processed_listings'), true, flags: JSON_BIGINT_AS_STRING) ?? [];
    array_walk($redis_processed, fn(&$el) => $el = (string)$el);
    $input = json_encode($redis_processed);

//    $this->Debug("processed_listings", $redis_processed); // TODO debug level
//    $this->Debug("input", $input);

    $process = proc_open(
      'node /opt/sph_test/js/ppt_parser.js',
      [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
      ],
      $pipes
    );

    if (is_resource($process)) {
      fwrite($pipes[0], $input);
      fclose($pipes[0]);

      $output = stream_get_contents($pipes[1]);
      fclose($pipes[1]);

      $error = stream_get_contents($pipes[2]);
      fclose($pipes[2]);

      $exitCode = proc_close($process);

      $this->Debug("Exit code", "$exitCode".PHP_EOL.PHP_EOL."JS output: $output".PHP_EOL);

      if ($error) {
        $this->Debug("ERRORS", "\n$error".PHP_EOL);
        if (!$this->debug_enabled) {
          Parser::ErrorTG($error);
          exit();
        }
      }

      $output_listings = json_decode($output, true, flags: JSON_BIGINT_AS_STRING);
      unset($output, $error);

      $processed_listings = $redis_processed;
      foreach ($output_listings['new_listings'] as $ls_arr) {
        $processed_listings = array_merge($processed_listings, array_keys($ls_arr));
      }
      array_walk($processed_listings, fn(&$el) => $el = (string)$el);
      $processed_listings = array_unique($processed_listings);

      $this->_redis->set('processed_listings', json_encode($processed_listings), 43200);

      $this->Debug("OUTPUT (output_listings NEW)", $output_listings['new_listings']);
//      $this->Debug("OUTPUT (output_listings)", $output_listings);
//      $this->Debug("INSERT REDIS", json_encode($processed_listings));
    }

    return $output_listings['new_listings'] ?? [];
  }

  protected function CheckSkins(array $to_check): array {
    $to_send = [];
    if (empty($to_check)) return $to_send;

//    foreach (Parser::getSkinsToParse() as $skin) {
//      foreach ($to_check[$skin] as $listing_id => $data) {
//        if (in_array($listing_id, $this->sent)) {
//          unset($to_check[$skin][$listing_id]);
//        }
//      }
//    }

    foreach ($this->getChats() as $chat_id => $skins) {
      foreach ($skins as $skin_name => $skin) {
        foreach ($to_check[$skin_name] ?? [] as $listing_id => $p_p) {
          $price_diff = round(($p_p['price'] * 100) / $this->price[$skin_name] - 100, 2);
          if (!$this->checkPatternPrice($skin_name, $p_p['pattern'], $price_diff)) continue;
          $to_send[$chat_id][] = [
            'name' => $skin_name,
            'listing_id' => $listing_id,
            'pattern' => $p_p['pattern'],
            'price' => $p_p['price'],
            'url' => $this->url_listings . rawurlencode($skin_name), // . "?filter=" . $p_p['pattern'],
            'price_diff1' => $price_diff,
            'price_diff2' => round($p_p['price'] - $this->price[$skin_name], 2),
            'asset_id' => $p_p['asset_id'],
            'page' => $p_p['page'],
          ];
        }
      }
    }

    return $to_send;
  }

}

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

    if (true || empty($this->price)) {
      foreach (Parser::getSkinsToParse() as $skin) {
        $r = Parser::curl_exec("https://steamcommunity.com/market/priceoverview/?market_hash_name=" . rawurlencode($skin) . "&appid=730&currency=5");
        $priceoverview = json_decode($r, true);
        $this->price[$skin] = Parser::toPrice($priceoverview['lowest_price'] ?? $priceoverview['median_price'] ?? 999);
      }
      $this->_redis->set('price', json_encode($this->price), 3600);
    }
  }

  protected function ParseSkins(): array {
    $redis_processed = json_decode($this->_redis->get('processed_listings'), true, flags: JSON_BIGINT_AS_STRING) ?? [];
    $this->Debug("processed_listings", $redis_processed);
    $listings = [];
    $processed_listings = [];
    foreach (Parser::getSkinsToParse() as $skin) {
      $processed_listings[$skin] = [];
    }
    foreach ($redis_processed as $skin => $ls) {
      foreach ($ls as $l) {
        $processed_listings[$skin][] = (string) $l;
      }
    }
    $input = json_encode($processed_listings);
    $this->Debug("input", $input);

    $process = proc_open(
      'node /opt/sph_test/steam_ppt.js',
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
        $this->Debug("ERRORS", "$error".PHP_EOL);
        $this->ErrorTG($error);
      }

      $this->Debug("output", $output);

      $listings = json_decode($output, true, flags: JSON_BIGINT_AS_STRING);
      unset($output, $error);
      $this->Debug("OUTPUT (parsed skins)", $listings);

      foreach ($processed_listings as $skin => $ls_arr) {
        $processed_listings[$skin] = array_merge($ls_arr, empty($listings[$skin]) ? [] : array_keys($listings[$skin]));
      }
      $this->Debug("INSERT REDIS", json_encode($processed_listings));
      $this->_redis->set('processed_listings', json_encode($processed_listings), 43200);
    }

    return $listings;
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
            'url' => $this->url_listings . rawurlencode($skin_name) . "?filter=" . $p_p['pattern'],
            'price_diff1' => $price_diff,
            'price_diff2' => $p_p['price'] - $this->price[$skin_name],
          ];
        }
      }
    }

    return $to_send;
  }

}

//skins
//id name image
//
//chats
//id chat_id currency
//
//settings
//chat_id skin_id data
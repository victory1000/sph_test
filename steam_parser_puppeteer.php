<?php
include_once "steam_parser.php";

class SteamParserPuppeteer extends SteamParser {

  protected bool $debug_enabled = true;
  protected int $debug_level = 1;

  public function __construct() {
    parent::__construct();
  }

  protected function init(): void {
    $this->getPrice();
    $di = (int)date('i', strtotime('now'));
    $dH = (int)date('H', strtotime('now'));
    if ($di > 58 && ($dH == 9 || $dH == 21)) $this->_redis->del('processed_listings');
  }

  protected function ParseSkins(): array {
    $input = $this->getInputForJS();
    $output_listings = $this->execJSFile('ppt_parser', $input);

    $processed_listings = $input['processed_listings'];
    foreach ($output_listings['new_listings'] as $ls_arr) {
      $processed_listings = array_merge($processed_listings, array_keys($ls_arr));
    }
    array_walk($processed_listings, fn(&$el) => $el = (string)$el);
    $processed_listings = array_unique($processed_listings);

    $this->_redis->set('processed_listings', json_encode($processed_listings), 43200);
    $this->Debug("INSERT REDIS", json_encode($processed_listings), 2);

    return $output_listings['new_listings'] ?? [];
  }

  private function getInputForJS(): array {
    $redis_processed = json_decode($this->_redis->get('processed_listings'), true, flags: JSON_BIGINT_AS_STRING) ?? [];
    array_walk($redis_processed, fn(&$el) => $el = (string)$el);

    foreach (Parser::getChats() as $skins) {
      foreach ($skins as $skin => $conf) {
        foreach ($conf as $_conf) {
          $prices[$skin][] = $_conf['price_percent'] ?? 1;
        }
      }
    }

    foreach ($prices as $skin => $prs) {
      $prices[$skin] = max($prs);
    }

    foreach (Parser::getSkinsToParse() as $skin) {
      $max_price[$skin] = $this->price[$skin] + ($this->price[$skin] * $prices[$skin] / 100);
    }

    return ['max_price' => $max_price, 'processed_listings' => $redis_processed, 'skins' => Parser::getSkinsToParse()];
  }

  protected function CheckSkins(array $to_check): array {
    $to_send = [];
    if (empty($to_check)) return $to_send;

    foreach (Parser::getChats() as $chat_id => $skins) {
      foreach ($skins as $skin_name => $skin) {
        foreach ($to_check[$skin_name] ?? [] as $listing_id => $p_p) {
          $price_diff = round(($p_p['price'] * 100) / $this->price[$skin_name] - 100, 2);
          if (!$this->checkPatternPrice($skin_name, $p_p['pattern'], $price_diff)) continue;
          $to_send[$chat_id][] = [
            'name' => $skin_name,
            'listing_id' => $listing_id,
            'pattern' => $p_p['pattern'],
            'price' => $p_p['price'],
            'url' => $this->url_listings . rawurlencode($skin_name),
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

  private function getPrice(): void {
    $this->price = json_decode($this->_redis->get('price'), true) ?? [];

    if (empty($this->price)) {

      foreach (Parser::getSkinsToParse() as $skin) {
        $res = Parser::curl_exec("https://steamcommunity.com/market/priceoverview/?market_hash_name=" . rawurlencode($skin) . "&appid=730&currency=5");
        $price = json_decode($res, true);
        if (is_null($price) || !key_exists('lowest_price', $price) || !key_exists('median_price', $price)) break;
        $this->price[$skin] = Parser::toPrice($price['lowest_price'] ?? $price['median_price']);
        sleep(1);
      }

      if (empty($this->price)) {
        $this->price = $this->execJSFile('get_price', ['skins' => Parser::getSkinsToParse()])['price'] ?? [];
      }

      if (empty($this->price)) {
        throw new Exception("Unable to get price.");
      }

      $this->_redis->set('price', json_encode($this->price), 3600);
    }
  }

  private function execJSFile(string $file_name, array $input): array {
    $process = proc_open(
      "node /opt/sph_test/js/$file_name.js",
      [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
      ],
      $pipes
    );

    if (is_resource($process)) {
      fwrite($pipes[0], json_encode($input));
      fclose($pipes[0]);

      $output = stream_get_contents($pipes[1]);
      fclose($pipes[1]);

      $error = stream_get_contents($pipes[2]);
      fclose($pipes[2]);

      $exitCode = proc_close($process);

      $this->Debug("EXIT CODE", $exitCode);
      $this->Debug("INPUT", json_encode($input), 2);
      $this->Debug("OUTPUT", $output, 1);

      if ($error) {
        $this->Debug("ERRORS", "\n$error" . PHP_EOL);
        if (!$this->debug_enabled) {
          TG::sendError($error);
          throw new Exception($error);
        }
      }
    }

    return json_decode($output ?? '{}', true, flags: JSON_BIGINT_AS_STRING);
  }

}

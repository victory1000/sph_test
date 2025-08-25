<?php

class SteamParserPuppeteer {

  protected Redis $_redis;
  protected array $price = [];
  protected string $token = "7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4";

  protected bool $debug_enabled = true;
  protected int $debug_level = 1;

  public function __construct() {
    $this->_redis = Cache::get_instance();
    $this->init();
  }

  protected function init(): void {
    $this->getPrice();
    $di = (int)date('i', strtotime('now'));
    $dH = (int)date('H', strtotime('now'));
    if ($di > 58 && ($dH == 9 || $dH == 21)) $this->_redis->del('processed_listings');
  }

  public function Process(): void {
    $to_check = $this->ParseSkins();
    $to_send = $this->CheckSkins($to_check);
//    $sent = [];

    foreach ($to_send as $chat_id => $skins) {
      foreach ($skins as $skin) {
//        $sent[] = $skin['listing_id'];
        $page = 'Page: ' . match ($skin['page']) {
            1 => '1️⃣',
            2 => '2️⃣',
            3 => '3️⃣',
          };
        $diff_emodji = $skin['price_diff1'] > 5 ? '⚠️' : '✅';
        $text = "$skin[name] Pattern: <b>$skin[pattern]</b>\n";
        $text .= "Price: $skin[price] руб. ({$this->price[$skin['name']]} руб.) Diff: <b>$skin[price_diff1]%</b> $diff_emodji ($skin[price_diff2] руб.)\n";
        $text .= "$skin[url]\n\n$page\nListingID: <code>$skin[listing_id]</code>\n<code>$skin[url]</code>";
        $url = "https://api.telegram.org/bot$this->token/sendMessage?" . http_build_query([
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'html',
          ]);
        file_get_contents($url); // todo Failed to open stream: Connection timed out
      }
    }

//    $this->_redis->set($this->sent_key, json_encode(array_merge($this->sent, $sent)), 3600);
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

    // stat
    $stat = json_decode($this->_redis->get('stat'), true) ?? ['steam'=>0,'csfloat'=>0];
    $stat['steam'] = $stat['steam'] + $output_listings['stat']['steam'];
    $stat['csfloat'] = $stat['csfloat'] + $output_listings['stat']['csfloat'];
    error_log("Time: ".date("d-m-Y-H-i-s", strtotime('now'))." \$stat = ".print_r($stat, true));
    $this->_redis->set('stat', json_encode($stat), 3600);
    // stat

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
            'url' => "https://steamcommunity.com/market/listings/730/" . rawurlencode($skin_name),
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

  protected function checkPatternPrice($skin_name, $pattern, $price): bool {
    if (Parser::isRarePattern($pattern)) {
      return true;
    }
    foreach (Parser::getChats() as $skins) {
      foreach ($skins[$skin_name] as $data) {
        if ($pattern >= $data['pattern_m'] && $pattern <= $data['pattern_l'] && $price <= $data['price_percent']) {
          return true;
        }
      }
    }
    return false;
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

  public function Debug(string $caption, mixed $value, int $level = 1): void {
    if ($this->debug_enabled && $this->debug_level >= $level) {
      if (is_string($value)) {
        error_log("Debug __{$caption}__ $value".PHP_EOL);
      } else {
        error_log("Debug __{$caption}__ " . print_r($value, true)).PHP_EOL;
      }
    }
  }

  /** OLD
  protected function ParseSkins(): array {
    $to_check = [];
    foreach (Parser::getSkinsToParse() as $skin_name) {
//      echo "Process $skin_name".PHP_EOL;
      $r = Parser::curl_exec($this->url_listings.rawurlencode($skin_name).$this->url_render);
      $html = json_decode($r, true)['results_html'] ?? null;

      if (empty($html)) continue;
      if (!str_contains($html, 'Charm Template')) echo "!!!!!!! WARNING BAD RESPONSE !!!!!!!".PHP_EOL;

      $dom = new DOMDocument();
      $dom->loadHTML($html);
      $xpath = new DomXPath($dom);
      $listings = $xpath->query("//div[contains(@id, 'listing_')]");

      foreach ($listings as $node) {
        if ($node->getAttribute('class') != 'market_listing_row_details') {
          if (preg_match('/Charm Template:\s*(\d+)/', $node->nodeValue, $matches)) {
            $_listing_id = str_replace("listing_", "", $node->getAttribute('id'));
            if (in_array($_listing_id, $this->sent)) continue;

            $container = $xpath->query("//div[@id='listing_$_listing_id']")->item(0);
            if ($container) {
              $priceSpan = $xpath->query(".//span[contains(@class, 'market_listing_price_with_fee')]", $container)->item(0);
              $price = Parser::toPrice($priceSpan?->textContent);
              $to_check[$skin_name][] = [
                'listing_id' => $_listing_id,
                'pattern' => $matches[1],
                'price' => $price
              ];
            }
          }
        }
      }
    }

    return $to_check;
  }
   */

}

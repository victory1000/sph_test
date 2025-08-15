<?php

class SteamParser {

  protected Redis $_redis;
  protected array $sent, $price = [];
  protected string $url_listings = "https://steamcommunity.com/market/listings/730/";
  protected string $url_render = "/render/?query=&start=0&country=RU&count=100&currency=5";
  protected string $token = "7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4";
  protected string $sent_key;
  protected bool $debug_enabled = true;
  protected int $debug_level = 1;

  public function __construct() {
    $this->_redis = Cache::get_instance();
    $this->init();
  }

  protected function init(): void {
    $this->sent_key = date('H');
    $this->sent = json_decode($this->_redis->get($this->sent_key), true) ?? [];
    $this->price = json_decode($this->_redis->get('price'), true) ?? [];

    if (empty($this->price)) {
      foreach (Parser::getSkinsToParse() as $skin) {
        $r = Parser::curl_exec("https://steamcommunity.com/market/priceoverview/?market_hash_name=".rawurlencode($skin)."&appid=730&currency=5");
        $priceoverview = json_decode($r, true);
        $this->price[$skin] = Parser::toPrice($priceoverview['lowest_price'] ?? $priceoverview['median_price'] ?? 999);
      }
      $this->_redis->set('price', json_encode($this->price), 3600);
    }
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

  protected function CheckSkins(array $to_check): array {
    $to_send = [];
    if (empty($to_check)) return $to_send;

    foreach (Parser::getChats() as $chat_id => $skins) {
      foreach ($skins as $skin_name => $skin) {
        foreach ($to_check[$skin_name] ?? [] as $p_p) {
          if (empty($p_p['pattern'])) {
            $this->Debug("EMPTY PATTERN", $p_p);
            TG::sendError("EMPTY PATTERN".json_encode($p_p));
            continue;
          }
          $price_diff = round(($p_p['price'] * 100) / $this->price[$skin_name] - 100, 2);
          if (!$this->checkPatternPrice($skin_name, $p_p['pattern'], $price_diff)) continue;
          $to_send[$chat_id][] = [
            'name' => $skin_name,
            'listing_id' => $p_p['listing_id'],
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

  public function Debug(string $caption, mixed $value, int $level = 1): void {
    if ($this->debug_enabled && $this->debug_level >= $level) {
      if (is_string($value)) {
        error_log("Debug __{$caption}__ $value".PHP_EOL);
      } else {
        error_log("Debug __{$caption}__ " . print_r($value, true)).PHP_EOL;
      }
    }
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
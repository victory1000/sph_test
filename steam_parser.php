<?php

class SteamParser {
  
  private Redis $_redis;
  private array $sent, $price = [];
  private string $url_listings = "https://steamcommunity.com/market/listings/730/";
  private string $url_render = "/render/?query=&start=0&country=RU&count=10&currency=5";
  private string $token = "7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4";

  
  public function __construct() {
    $this->_redis = Cache::get_instance();
    $this->init();
  }

  private function init(): void {
    $this->sent = json_decode($this->_redis->get('sent'), true) ?? [];
    $this->price = json_decode($this->_redis->get('price'), true) ?? [];

    if (empty($this->price)) {
      foreach ($this->getSkinsToParse() as $skin) {
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
    $sent = [];

    foreach ($to_send as $chat_id => $skins) {
      foreach ($skins as $skin) {
        $sent[] = $skin['listing_id'];
        $text = "$skin[name] Template: <b>$skin[pattern]</b>\nPrice: $skin[price] руб. ({$this->price[$skin['name']]} руб.) Diff: <b>$skin[price_diff1]%</b> ($skin[price_diff2] руб.) \n$skin[url]";
        $url = "https://api.telegram.org/bot$this->token/sendMessage?" . http_build_query([
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'html',
          ]);
        file_get_contents($url);
      }
    }

    error_log("\$to_check = ".print_r($to_check, true));
    error_log("\$to_send = ".print_r($to_send, true));
    error_log("\$sent = ".print_r($sent, true));

    $this->_redis->set('sent', json_encode(array_merge($this->sent, $sent)), 3600);
    echo "Completed ".date('d-m-Y-H-i-s').PHP_EOL.PHP_EOL;
  }

  private function ParseSkins(): array {
    $to_check = [];
    foreach ($this->getSkinsToParse() as $skin_name) {
      echo "Process $skin_name".PHP_EOL;

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
              // echo "price = ".$price.PHP_EOL;
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

  private function CheckSkins(array $to_check): array {
    $to_send = [];
    if (empty($to_check)) return $to_send;

    foreach ($this->getChats() as $chat_id => $skins) {
      foreach ($skins as $skin_name => $skin) {
        foreach ($to_check[$skin_name] as $p_p) {
          if (!$this->checkPattern($skin_name, $p_p['pattern'])) continue;

          $price_diff = round(($p_p['price'] * 100) / $this->price[$skin_name] - 100, 2);
          error_log("\$skin = ".print_r($skin, true));

          if ($price_diff <= $skin['price_percent']) {
            $to_send[$chat_id][] = [
              'name' => $skin_name,
              'listing_id' => $p_p['listing_id'],
              'pattern' => $p_p['pattern'],
              'price' => $p_p['price'],
              'url' => $this->url_listings . rawurlencode($skin_name) . "?filter=" . $p_p['pattern'],
              'price_diff1' => $price_diff,
              'price_diff2' => $p_p['price'] - $this->price[$skin_name],
            ];
          } else {
            echo "$skin_name Template: " . $p_p['pattern'] . " Price: {$p_p['price']} MinPrice: {$this->price[$skin_name]} Diff: $price_diff%" . PHP_EOL;
          }
        }
      }
    }

    return $to_send;
  }

  private function checkPattern($skin_name, $pattern): bool {
    if (Parser::isRarePattern($pattern)) {
      return true;
    }
    foreach ($this->getChats() as $skins) {
      foreach ($skins[$skin_name] as $data) {
        if ($pattern >= $data['pattern_m'] && $pattern <= $data['pattern_l']) {
          return true;
        }
      }
    }
    return false;
  }

  private function getChats(): array {
    return [
      513209606 => [
        "Charm | Baby's AK" => [
//          ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 30],
          ['pattern_m' => 70000, 'pattern_l' => 80000, 'price_percent' => 30],
          ['pattern_m' => 1, 'pattern_l' => 1000, 'price_percent' => 30],
        ],
//        "Charm | Die-cast AK" => [
//          ['pattern_m' => 87_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//          ['pattern_m' => 1, 'pattern_l' => 24_000, 'price_percent' => 30],
//        ],
//        "Charm | Titeenium AWP" => [
////          ['pattern_m' => 99_000, 'pattern_l' => 1000, 'price_percent' => 30],
//          ['pattern_m' => 1, 'pattern_l' => 13_000, 'price_percent' => 30],
//        ],
//        "Charm | Disco MAC" => [
//          ['pattern_m' => 89_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//          ['pattern_m' => 1, 'pattern_l' => 28_000, 'price_percent' => 30],
//        ],
//        "Charm | Glamour Shot" => [
////          ['pattern_m' => 89_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//          ['pattern_m' => 1, 'pattern_l' => 4000, 'price_percent' => 30],
//        ],
      ]
    ];
  }

  private function getSkinsToParse(): array {
//    return ["Charm | Baby's AK", "Charm | Die-cast AK", "Charm | Titeenium AWP", "Charm | Disco MAC", "Charm | Glamour Shot"];
    return ["Charm | Baby's AK"];
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
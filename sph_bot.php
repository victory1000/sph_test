<?php

function toPrice($price) {
  $price = preg_replace('/[^0-9,]/', '', $price);
  $price = str_replace(',', '.', $price);
  return (float) $price;
}

function call($url) {
  $ch = curl_init($url);

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
      'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ],
  ]);

  $response = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($http_code === 429) {
    echo "Too Many Requests\n";
    return "";
  } else {
    return $response;
  }
}

function isRarePattern($seed) {
  $s = strval($seed);

  // 1. Повторяющиеся цифры (11, 2222, 55555 и т.п.)
  if (preg_match('/^(\d)\1{1,}$/', $s)) return true;

  // 2. Палиндром (12321, 44444, 35553)
//  if ($s === strrev($s)) return true;

  // 3. Заканчивается на много нулей (например, 1000, 90000)
  if (preg_match('/000$/', $s) || preg_match('/0000$/', $s)) return true;

  // 4. Возрастающая последовательность (123, 1234, 12345)
  if (strpos('0123456789', $s) !== false) return true;

  // 5. Убывающая последовательность (9876, 54321)
  if (strpos('9876543210', $s) !== false) return true;

  return false;
}

$set = [
  [
    'name' => "Charm | Baby's AK",
    'price_diff' => 30,
    'price_def' => 54,
    'check' => function($t) {
      return $t <= 5_000 || $t >= 99_000;
    },
  ],
  // [
  //   'name' => "Charm | Die-cast AK",
  //   'price_diff' => 30,
  //   'price_def' => 560,
  //   'check' => function($t) {
  //     return $t <= 24_000 || $t >= 87_000;
  //   },
  // ],
  // [
  //   'name' => "Charm | Titeenium AWP",
  //   'price_diff' => 30,
  //   'price_def' => 640,
  //   'check' => function($t) {
  //     return $t <= 13_000;
  //   },
  // ],
  // [
  //   'name' => "Charm | Disco MAC",
  //   'price_diff' => 30,
  //   'price_def' => 100,
  //   'check' => function($t) {
  //     return $t <= 28_000 || $t >= 89001;
  //   },
  // ],
  // [
  //   'name' => "Charm | Glamour Shot",
  //   'price_diff' => 30,
  //   'price_def' => 170,
  //   'check' => function($t) {
  //     return $t <= 4000;
  //   },
  // ],
];

$url_listings = "https://steamcommunity.com/market/listings/730/";
$url_render = "/render/?query=&start=0&country=RU&count=100&currency=5";
$token = "7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4";

$lower_price = $sent = [];

foreach ($set as $s) {
  $r = call("https://steamcommunity.com/market/priceoverview/?market_hash_name=".rawurlencode($s['name'])."&appid=730&currency=5");
  $priceoverview = json_decode($r, true);
  $lower_price[$s['name']] = toPrice($priceoverview['lowest_price'] ?? $priceoverview['median_price'] ?? $s['price_def']);
}

print_r($lower_price, 1);

while (true) {
  foreach ($set as $s) {
    echo "Process ".$s['name'].PHP_EOL;

    $r = call($url_listings.rawurlencode($s['name']).$url_render);
    $html = json_decode($r, true)['results_html'] ?? null;
    //  error_log("\$html = {$html} ");
    echo $html.PHP_EOL;

    if (empty($html)) continue;
    if (!str_contains($html, 'Charm Template')) {
      echo "!!!!!!! WARNING BAD RESPONSE !!!!!!!".PHP_EOL;
    }

    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DomXPath($dom);
    $listings = $xpath->query("//div[contains(@id, 'listing_')]");
    print_r($listings, true);

    $result = [];
    foreach ($listings as $node) {
print_r($node,1);
      exit();
      if (empty($node->className)) {
        continue;
      }
      if ($node->className != 'market_listing_row_details') {
        if (preg_match('/Charm Template:\s*(\d+)/', $node->nodeValue, $matches)) {
          $t = $matches[1];
          echo "t: $t".PHP_EOL;

          $_listing_id = str_replace("listing_", "", $node->id);

          if (in_array($_listing_id, $sent)) {
            continue;
          }

          if ($s['check']((int)$t) || isRarePattern($t)) {
            $result[$_listing_id] = [
              'template' => $t,
              'name' => $s['name']
            ];
          }
        }
      }
    }

    foreach (array_keys($result) as $listingId) {
      $container = $xpath->query("//div[@id='listing_$listingId']")->item(0);

      if ($container) {
        $priceSpan = $xpath->query(".//span[contains(@class, 'market_listing_price_with_fee')]", $container)->item(0);
        $price = toPrice($priceSpan?->textContent);

        $price_diff = round(($price * 100) / $lower_price[$s['name']] - 100, 2);

        if ($price_diff <= $s['price_diff']) {
          $result[$listingId]['price'] = $price;
          $result[$listingId]['url'] = $url_listings.rawurlencode($s['name'])."?filter=".$result[$listingId]['template'];
          $result[$listingId]['price_diff1'] = $price_diff;
          $result[$listingId]['price_diff2'] = $price-$lower_price[$s['name']];
          $sent[] = $listingId;
        } else {
          echo "{$s['name']} Template: ".$result[$listingId]['template']." Price: $price MinPrice: {$lower_price[$s['name']]} Diff: $price_diff%".PHP_EOL;
          unset($result[$listingId]);
        }
      }
    }

    if (!empty($result)) {
      foreach ($result as $listingId => $el) {
        $text = "$el[name] Template: <b>$el[template]</b>\nPrice: $el[price] руб. ({$lower_price[$el['name']]} руб.) Diff: <b>$el[price_diff1]%</b> ($el[price_diff2] руб.) \n$el[url]";
        $url = "https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
            'chat_id' => 513209606,
            'text' => $text,
            'parse_mode' => 'html',
          ]);

        file_get_contents($url);
      }
    }
  }

  echo "Sleep ".date('d-m-Y-H-i-s').PHP_EOL.PHP_EOL;
  sleep(120);
}

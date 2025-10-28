<?php
include_once "redis.php";
include_once "steam_parser_puppeteer.php";
include_once "classes/tg.php";

class Parser {

  static function curl_exec(string $url): string {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
      CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36",
        "Accept-Language: en-US,en;q=0.9",
        "Accept-Encoding: gzip, deflate, br",
        "Connection: keep-alive"
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

  static function isRarePattern(int $seed): bool {
    $s = strval($seed);

    // 1. Повторяющиеся цифры (11, 2222, 55555 и т.п.)
    if (preg_match('/^(\d)\1{1,}$/', $s)) return true;

    // 3. Заканчивается на много нулей (например, 1000, 90000)
    if (preg_match('/000$/', $s) || preg_match('/0000$/', $s)) return true;

    // 4. Возрастающая последовательность (123, 1234, 12345)
    if (strpos('0123456789', $s) !== false) return true;

    // 5. Убывающая последовательность (9876, 54321)
    if (strpos('9876543210', $s) !== false) return true;

    return false;
  }
  
  static function toPrice(mixed $price): float {
    $price = preg_replace('/[^0-9,]/', '', $price);
    $price = str_replace(',', '.', $price);
    return (float) $price;
  }

  static function getSkinsToParse(string $item_type): array {
    return array_keys(self::getChats()[TG::OWNER][$item_type]);
  }

  static function getChats(): array {
    return [
      513209606 => [
        "charm" => [
          "Charm | Baby's AK" => [
            ['pattern_m' => 59_500, 'pattern_l' => 100_000, 'price_percent' => 20],
            ['pattern_m' => 1, 'pattern_l' => 500, 'price_percent' => 20],
          ],
//          "Charm | Die-cast AK" => [
//            ['pattern_m' => 87_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//            ['pattern_m' => 1, 'pattern_l' => 5_000, 'price_percent' => 30],
//            ['pattern_m' => 21000, 'pattern_l' => 29_000, 'price_percent' => 30],
//          ],
//          "Charm | Titeenium AWP" => [
//            ['pattern_m' => 90_000, 'pattern_l' => 100_000, 'price_percent' => 10],
//            ['pattern_m' => 1, 'pattern_l' => 10_000, 'price_percent' => 10],
//          ],
//          "Charm | Disco MAC" => [
//            ['pattern_m' => 1, 'pattern_l' => 15_000, 'price_percent' => 5],
//            ['pattern_m' => 49500, 'pattern_l' => 50500, 'price_percent' => 5],
//            ['pattern_m' => 95_000, 'pattern_l' => 100_000, 'price_percent' => 5],
//          ],
//          "Charm | Glamour Shot" => [
//            ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//            ['pattern_m' => 1, 'pattern_l' => 1000, 'price_percent' => 30],
//          ],
//          "Charm | Hot Hands" => [
//            ['pattern_m' => 99_500, 'pattern_l' => 100_000, 'price_percent' => 10],
//            ['pattern_m' => 1, 'pattern_l' => 500, 'price_percent' => 10],
//          ],
//          "Charm | POP Art" => [
//            ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 20],
//            ['pattern_m' => 500, 'pattern_l' => 1000, 'price_percent' => 20],
//            ['pattern_m' => 0, 'pattern_l' => 500, 'price_percent' => 30],
//          ],
//          "Charm | Whittle Knife" => [
//            ['pattern_m' => 99_500, 'pattern_l' => 100_000, 'price_percent' => 20],
//            ['pattern_m' => 1, 'pattern_l' => 500, 'price_percent' => 20],
//          ],
//          "Charm | Pocket AWP" => [
//            // ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 5],
//            ['pattern_m' => 1, 'pattern_l' => 500, 'price_percent' => 20],
//          ],
//          "Charm | Lil' Cap Gun" => [
//            ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 5],
//            ['pattern_m' => 1, 'pattern_l' => 1000, 'price_percent' => 5],
//          ],
//          "Charm | Lil' SAS" => [
//            ['pattern_m' => 98_000, 'pattern_l' => 100_000, 'price_percent' => 10],
//            ['pattern_m' => 1, 'pattern_l' => 2000, 'price_percent' => 10],
//          ],
//          "Charm | Pinch O' Salt" => [
//            ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 10],
//            ['pattern_m' => 1, 'pattern_l' => 1000, 'price_percent' => 10],
//          ],
//          "Charm | Hot Sauce" => [
//            ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 10],
//            ['pattern_m' => 1, 'pattern_l' => 5_000, 'price_percent' => 10],
//          ],
//          "Charm | Diamond Dog" => [
//            ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 10],
//            ['pattern_m' => 1, 'pattern_l' => 1000, 'price_percent' => 10],
//          ],
        ],

        "skin" => [
          "SSG 08 | Acid Fade (Factory New)" => [
            ['price_percent' => 10, 'paint_seed' => [260]],
          ],
        ]
      ]
    ];
  }

}


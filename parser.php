<?php
include_once "redis.php";
include_once "steam_parser.php";
include_once "steam_parser_puppeteer.php";

class Parser {

  static function curl_exec(string $url): string {
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

  static function getSkinsToParse(): array {
    return [
      "Charm | Disco MAC",
      "Charm | Baby's AK",
      "Charm | Die-cast AK",
      "Charm | Titeenium AWP",
      "Charm | Glamour Shot"
    ];
  }

  static function ErrorTG(mixed $message): void {
    $url = "https://api.telegram.org/bot7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4/sendMessage?" . http_build_query([
        'chat_id' => 513209606,
        'text' => urlencode($message),
        'parse_mode' => 'html'
      ]);
    file_get_contents($url);
  }

}


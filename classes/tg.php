<?php

class TG {
  const TOKEN = "7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4";

  static function sendMessage(mixed $message, int $chat_id = 513209606): void {
    $url = "https://api.telegram.org/bot".self::TOKEN."/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'html'
      ]);
    file_get_contents($url);
  }

  static function sendError(mixed $message): void {
    $message = "❌" . urlencode($message);
    self::sendMessage($message);
  }

  static function getUpdates(): array {
//    $offsetFile = __DIR__ . '/offset.txt';
// читаем последний offset, чтобы не читать старые сообщения
//    $offset = is_file($offsetFile) ? (int)file_get_contents($offsetFile) : 0;

    $offset = 150;
    $url = "https://api.telegram.org/bot".self::TOKEN."/getUpdates?timeout=5&offset={$offset}";
    return json_decode(file_get_contents($url), true);
  }



}
<?php

class TG {
  const TOKEN = "7143696549:AAFEf9cpwTBx77q1ASheg3RbHbem9STBYl4";
  const OWNER = 513209606;

  static function sendMessage(mixed $message, int $chat_id = self::OWNER): void {
    $url = "https://api.telegram.org/bot".self::TOKEN."/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'html'
      ]);
    file_get_contents($url);
  }

  static function sendError(mixed $message): void {
    $url = "https://api.telegram.org/bot".self::TOKEN."/sendMessage?" . http_build_query([
        'chat_id' => self::OWNER,
        'text' => "❌$message",
        'parse_mode' => 'MarkdownV2'
      ]);
    $t = file_get_contents($url);
    error_log("sendError = {$t} ");
  }

  static function getUpdates(): array {
    $offsetFile = __DIR__ . "/../files/offset.txt";
    $offset = is_file($offsetFile) ? (int)file_get_contents($offsetFile) : 0;
    $url = "https://api.telegram.org/bot".self::TOKEN."/getUpdates?timeout=5&offset={$offset}";
    $updates = json_decode(@file_get_contents($url), true) ?? [];
    if (!empty($updates['result'])) {
      foreach ($updates['result'] as $update) {
        $offset = $update['update_id'] + 1;
        file_put_contents($offsetFile, $offset);
      }
    }

    return $updates;
  }



}
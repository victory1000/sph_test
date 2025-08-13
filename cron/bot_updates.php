<?php
include_once __DIR__ . "/../classes/tg.php";
$stopFile = __DIR__ . "/../files/stop.flag";

$updates = TG::getUpdates();
error_log("\$updates = ".print_r($updates, true));

if (!empty($updates['result'])) {
  foreach ($updates['result'] as $update) {
    $message = $update['message']['text'] ?? '';
    $chat_id = $update['message']['chat']['id'] ?? '';

    if ($chat_id === TG::OWNER) {
      if ($message === 'stop server') {
        file_put_contents($stopFile, '1');
        TG::sendMessage('Server stopped ✅');
      } elseif ($message === 'start server') {
        @unlink($stopFile);
        TG::sendMessage('Server started 🚀');
      }
    } else {
      TG::sendMessage("New message:\n$message");
      TG::sendMessage("Message received", $chat_id);
    }
  }
}



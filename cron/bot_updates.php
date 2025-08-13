<?php
include_once __DIR__ . "/../classes/tg.php";
$stop_file = __DIR__ . "/../files/stop.flag";

$updates = TG::getUpdates();

if (!empty($updates['result'])) {
  foreach ($updates['result'] as $update) {
    $message = $update['message']['text'] ?? '';
    $chat_id = $update['message']['chat']['id'] ?? '';

    if ($chat_id === TG::OWNER) {
      if ($message === 'stop server') {
        file_put_contents($stop_file, '1');
        TG::sendMessage('Server has been stopped ✅');
      } elseif ($message === 'start server') {
        @unlink($stop_file);
        TG::sendMessage('Server has been started 🚀');
      }
    } else {
      TG::sendMessage("New message:\n$message");
      TG::sendMessage("Message received", $chat_id);
    }
  }
}

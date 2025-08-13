<?php
include_once __DIR__ . "/../parser.php";

try {
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
        } elseif ($message === 'clear processed listings') {
          $_redis = Cache::get_instance();
          $_redis->del('processed_listings');
          TG::sendMessage('The listings have been cleared.');
        }
      } else {
        TG::sendMessage("New message:\n$message");
        TG::sendMessage("Message received", $chat_id);
      }
    }
  }

} catch (Throwable $e) {
  $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
  $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
  TG::sendError($message);
}

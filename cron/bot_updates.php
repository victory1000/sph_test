<?php
include_once __DIR__ . "/../classes/parser.php";

try {
  $updates = TG::getUpdates();

  if (!empty($updates['result'])) {
    foreach ($updates['result'] as $update) {
      $message = $update['message']['text'] ?? '';
      $chat_id = $update['message']['chat']['id'] ?? '';

      if ($chat_id === TG::OWNER) {
        switch ($message) {
          case 'stop server charm':
            file_put_contents(__DIR__ . "/../files/stop_charms.flag", '1');
            TG::sendMessage('Server Charms has been stopped ✅');
            break;
          case 'start server charm':
            @unlink(__DIR__ . "/../files/stop_charms.flag");
            TG::sendMessage('Server Charms has been started 🚀');
            break;
          case 'stop server skins':
            file_put_contents(__DIR__ . "/../files/stop_skins.flag", '1');
            TG::sendMessage('Server Skins has been stopped ✅');
            break;
          case 'start server skins':
            @unlink(__DIR__ . "/../files/stop_skins.flag");
            TG::sendMessage('Server Skins has been started 🚀');
            break;
          case 'clear processed listings':
            $_redis = Cache::get_instance();
            $_redis->del('processed_listings');
            TG::sendMessage('The listings have been cleared.');
            break;
          case 'update price':
            $_redis = Cache::get_instance();
            $_redis->del('price');
            TG::sendMessage('The price has been cleared.');
            break;
          case 'commands':
            TG::sendMessage("<code>start server charm</code>\n<code>stop server charm</code>\n<code>clear processed listings</code>\n<code>update price</code>");
            break;
        }
      } else {
        TG::sendMessage("New message:\n$message");
        TG::sendMessage("Bot under development", $chat_id);
      }
    }
  }

} catch (Throwable $e) {
  $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
  $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
  TG::sendError($message);
}

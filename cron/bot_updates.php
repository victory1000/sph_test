<?php
include_once __DIR__ . "/../classes/tg.php";
//$stopFile = __DIR__ . '/stop.flag';

$updates = TG::getUpdates();
error_log("\$updates = ".print_r($updates, true));


//if (!empty($updates['result'])) {
//  foreach ($updates['result'] as $update) {
//    $offset = $update['update_id'] + 1;
////    file_put_contents($offsetFile, $offset);
//
//    $message = $update['message']['text'] ?? '';
//    $chatId = $update['message']['chat']['id'] ?? '';
//
//    if (strtolower($message) === 'stop server') {
//      file_put_contents($stopFile, '1');
//      sendMessage($chatId, 'Парсер остановлен ✅');
//    } elseif (strtolower($message) === 'start server') {
//      @unlink($stopFile);
//      sendMessage($chatId, 'Парсер запущен 🚀');
//    }
//  }
//}



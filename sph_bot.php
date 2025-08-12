<?php
include_once "parser.php";
error_log('start');

$time = 30;
//while ($time < 60) {
  $start_mem = memory_get_usage();
  $start_time = microtime(true);
  try {
    $_steam = new SteamParserPuppeteer();
    $_steam->Process();
    $processing_time = microtime(true) - $start_time;
    error_log("\$processing_time = ".$processing_time);
    $time += max($processing_time, 25);
    error_log("time = $time");
    $_steam->Debug("Execution", [
      'time' => $processing_time,
      'memory' => round((memory_get_usage() - $start_mem) / 1048576.00, 2),
      'max memory' => memory_get_peak_usage() / 1048576.00
    ]);
    if ($time < 60) {
      error_log("sleep = ".(30-$processing_time));
//      sleep(30-$processing_time);
    }
  } catch (Throwable $e) {
    $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
    $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
    Parser::ErrorTG($message);
  }
//}
error_log('end');

//$lockFile = fopen(__DIR__ . '/script.lock', 'c');
//
//// Если не удалось получить блокировку — значит, скрипт уже запущен
//if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
//  echo "Скрипт уже запущен, выходим..." . PHP_EOL;
//  exit;
//}
//
//// Твой код
//echo date('Y-m-d H:i:s') . " — Начало работы" . PHP_EOL;
//sleep(40); // имитация работы
//
//// Снимаем блокировку
//flock($lockFile, LOCK_UN);
//fclose($lockFile);
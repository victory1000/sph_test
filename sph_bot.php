<?php
// TODO сделать остановку сервера через бота
include_once "parser.php";

$lockFile = fopen(__DIR__ . '/script_bot.lock', 'c');
if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
  Parser::ErrorTG("Script already running, exiting");
  fclose($lockFile);
  exit;
}

$time = 30;
while ($time < 60) {
  $start_mem = memory_get_usage();
  $start_time = microtime(true);

  try {
    $_steam = new SteamParserPuppeteer();
    $_steam->Process();

    $processing_time = microtime(true) - $start_time;
    $time += max($processing_time, 25);

    $_steam->Debug("Execution", [
      'time' => $processing_time,
      'memory' => round((memory_get_usage() - $start_mem) / 1048576.00, 2),
      'max memory' => memory_get_peak_usage() / 1048576.00
    ]);

    if ($time < 60) {
      sleep(30 - (int)$processing_time);
    }

  } catch (Throwable $e) {
    $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
    $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
    Parser::ErrorTG($message);
    fclose($lockFile);
    exit;
  }
}

flock($lockFile, LOCK_UN);
fclose($lockFile);
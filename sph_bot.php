<?php
include_once "parser.php";

$start_mem = memory_get_usage();
$start_time = microtime(true);

$time = 30;
while ($time < 60) {
  try {
    $_steam = new SteamParserPuppeteer();
    $_steam->Process();
    $processing_time = microtime(true) - $start_time;
    $time += max($processing_time, 25);
    error_log("time = $time");
    $_steam->Debug("Execution", [
      'time' => $processing_time,
      'memory' => round((memory_get_usage() - $start_mem) / 1048576.00, 2),
      'max memory' => memory_get_peak_usage() / 1048576.00
    ]);
  } catch (Throwable $e) {
    $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
    $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
    Parser::ErrorTG($message);
  }
}

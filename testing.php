<?php
include_once "parser.php";

$start_mem = memory_get_usage();
$start_time = microtime(true);

try {
  $_steam = new SteamParserPuppeteer('skin');
  $_steam->Process();

  $_steam->Debug("Execution", [
    'time' => microtime(true) - $start_time,
    'memory' => round((memory_get_usage() - $start_mem) / 1048576.00, 2),
    'max memory' => memory_get_peak_usage() / 1048576.00
  ]);

} catch (Throwable $e) {
  $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
  $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
  TG::sendError($message);
}

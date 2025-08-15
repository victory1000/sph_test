<?php
include_once "parser.php";







$process = proc_open(
  'node /opt/sph_test/js/get_price.js',
  [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w'],  // stderr
  ],
  $pipes
);

if (is_resource($process)) {
  fwrite($pipes[0], '');
  fclose($pipes[0]);

  $output = stream_get_contents($pipes[1]);
  fclose($pipes[1]);

  $error = stream_get_contents($pipes[2]);
  fclose($pipes[2]);

  $exitCode = proc_close($process);

  error_log("\$output = " . print_r($output, true));
  error_log("\$error = " . print_r($error, true));
  error_log("\$exitCode = " . print_r($exitCode, true));
}

exit();








$start_mem = memory_get_usage();
$start_time = microtime(true);

try {
  $_steam = new SteamParserPuppeteer();
  $_steam->Process();
} catch (Throwable $e) {
  $message = "Exception: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
  $message .= "Backtrace:" . PHP_EOL . $e->getTraceAsString();
  TG::sendError($message);
}

$_steam->Debug("Execution", [
  'time' => microtime(true) - $start_time,
  'memory' => round((memory_get_usage() - $start_mem) / 1048576.00, 2),
  'max memory' => memory_get_peak_usage() / 1048576.00
]);
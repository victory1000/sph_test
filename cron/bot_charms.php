<?php
include_once "../classes/parser.php";

$stop_file = __DIR__ . '/../files/stop_charms.flag';
if (file_exists($stop_file)) {
  exit;
}

$lock_file = fopen(__DIR__ . '/../files/cron_script_charms.lock', 'c');
if (!flock($lock_file, LOCK_EX | LOCK_NB)) {
  fclose($lock_file);
  exit;
}

$time = 30;
while ($time < 60) {
  $start_mem = memory_get_usage();
  $start_time = microtime(true);

  try {
    $_steam = new SteamParserPuppeteer('charm');
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
    TG::sendError($message);
    fclose($lock_file);
    exit;
  }
}

flock($lock_file, LOCK_UN);
fclose($lock_file);
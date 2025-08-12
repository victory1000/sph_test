<?php
include_once "parser.php";

$start_mem = memory_get_usage();
$start_time = microtime(true);
exit();
$_steam = new SteamParserPuppeteer();
$_steam->Process();

$_steam->Debug("Execution", [
  'time' => microtime(true) - $start_time,
  'memory' => round((memory_get_usage() - $start_mem) / 1048576.00, 2),
  'max memory' => memory_get_peak_usage() / 1048576.00
]);
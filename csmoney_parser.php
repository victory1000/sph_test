<?php
include_once "parser.php";

foreach (Parser::getSkinsToParse() as $skin_name) {
  $url = "https://cs.money/2.0/market/sell-orders?limit=60&offset=0&type=21&name=".rawurlencode($skin_name)."&order=asc&sort=price";
  $json = file_get_contents($url);
  $data = json_decode($json, true);
  error_log("\$data = ".print_r($data, true));
  break;
}
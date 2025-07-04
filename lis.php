<?php

$u = "https://lis-skins.com/ru/market/csgo/fracture-case/?sort_by=price_asc";

function toPrice($price) {
  $price = preg_replace('/[^0-9.]/', '', $price);
  $price = str_replace(',', '.', $price);
  return (float) $price;
}

$html = call($u);
if (str_contains($html, "Just a moment")) {
  echo "Just a moment";
}
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DomXPath($dom);
$listing = $xpath->query("//div[contains(@class, 'market_item')]")->item(0);
$price = $xpath->query(".//div[@class='price']", $listing)->item(0)?->nodeValue ?? "";
$price = toPrice($price);

echo $price;
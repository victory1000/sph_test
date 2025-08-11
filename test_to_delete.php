<?php



$url = "https://api.csfloat.com/?url=steam://rungame/730/76561202255233023/+csgo_econ_action_preview%20M646948255089500956A45460667394D7953503640324289169";
$origin = "chrome-extension://jjicbefpemnphinccgikpdaagjebbnhg";

$ch = curl_init();

curl_setopt_array($ch, [
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYPEER => false, // для теста можно выключить
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTPHEADER => [
    "Origin: $origin",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
    "Accept: application/json, text/plain, */*",
    "Accept-Language: en-US,en;q=0.9",
    "Referer: https://steamcommunity.com/",
  ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
  echo "Ошибка cURL: " . curl_error($ch);
} else {
  echo $response;
}

curl_close($ch);
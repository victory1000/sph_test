<?php

$chats = [
  513209606 => [
//    "Charm | Baby's AK" => [
//      ['pattern_m' => 99_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//      ['pattern_m' => 1, 'pattern_l' => 1000, 'price_percent' => 30],
//    ],
    "Charm | Die-cast AK" => [
      ['pattern_m' => 87_000, 'pattern_l' => 100_000, 'price_percent' => 30],
      ['pattern_m' => 1, 'pattern_l' => 24_000, 'price_percent' => 30],
    ],
//    "Charm | Titeenium AWP" => [
////          ['pattern_m' => 99_000, 'pattern_l' => 1000, 'price_percent' => 30],
//['pattern_m' => 1, 'pattern_l' => 13_000, 'price_percent' => 30],
//    ],
//    "Charm | Disco MAC" => [
//      ['pattern_m' => 89_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//      ['pattern_m' => 1, 'pattern_l' => 5_000, 'price_percent' => 30],
//    ],
//    "Charm | Glamour Shot" => [
////          ['pattern_m' => 89_000, 'pattern_l' => 100_000, 'price_percent' => 30],
//['pattern_m' => 1, 'pattern_l' => 4000, 'price_percent' => 30],
//    ],
  ]
];

$input = json_encode($chats);

$process = proc_open(
  'node /opt/sph_test/steam_ppt.js',
  [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w'],  // stderr
  ],
  $pipes
);

if (is_resource($process)) {
  fwrite($pipes[0], $input);
  fclose($pipes[0]);

  $output = stream_get_contents($pipes[1]);
  fclose($pipes[1]);

  $error = stream_get_contents($pipes[2]);
  fclose($pipes[2]);

  proc_close($process);

  echo "Ответ JS: $output\n";
  if ($error) echo "Ошибки: $error\n";

  $listings = json_decode($output, true);
  error_log("\$listings = ".print_r($listings, true));
}

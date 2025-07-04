<?php
include_once "parser.php";
include_once "redis.php";
include_once "steam_parser.php";

$_steam = new SteamParser();
$_steam->Process();
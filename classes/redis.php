<?php

class Cache {
  private static ?Redis $instance = null;

  public static function get_instance(): Redis {
    if (self::$instance == null) {
      self::$instance = new Redis();
      self::$instance->connect('127.0.0.1', 6379);
    }
    return self::$instance;
  }

}
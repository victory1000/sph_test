<?php

class Cache {
  private static Redis $instance;

  public static function get_instance(): Redis {
    if (!self::$instance instanceof Redis) {
      self::$instance = new Redis();
      self::$instance->connect('127.0.0.1', 6379);
    }
    return self::$instance;
  }

}
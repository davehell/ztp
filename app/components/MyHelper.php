<?php
class MyHelper {
  /**
   * case insensitive vyhledávání
   */
  public static function ireplace($string, $search, $replace) {
    return str_ireplace($search, $replace, $string);
  }
}

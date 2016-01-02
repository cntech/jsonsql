<?php

namespace CnTech\JsonSql;

class SecureField {
  
  public static function secureField($columns, $field) {
    $index = array_search($field, $columns);
    if($index !== FALSE) {
      return $columns[$index];
    }
    return FALSE;
  }
  
}


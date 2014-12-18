<?php

namespace SQRT\DB;

class Exception extends \SQRT\Exception
{
  const CONNECTION_NOT_EXISTS = 10;
  const QUERY                 = 20;

  protected static $errors_arr = array(
    self::CONNECTION_NOT_EXISTS => 'Подключение к БД "%s" не существует',
    self::QUERY                 => '[%2$s] %3$s'
  );
} 
<?php

namespace SQRT\DB;

class Exception extends \SQRT\Exception
{
  const CONNECTION_NOT_EXISTS = 10;
  const QUERY                 = 20;
  const SCHEMA_NOT_EXISTS     = 30;
  const PK_NOT_SET            = 40;
  const ENUM_BAD_VALUE        = 50;
  const FILE_NOT_EXISTS       = 60;
  const PROCESSING_FILE       = 61;
  const NOT_REPOSITORY        = 70;
  const COLUMN_NOT_EXISTS     = 80;

  protected static $errors_arr = array(
    self::CONNECTION_NOT_EXISTS => 'Подключение к БД "%s" не существует',
    self::QUERY                 => '[%2$s] %3$s',
    self::SCHEMA_NOT_EXISTS     => 'Схема "%s" не существует',
    self::PK_NOT_SET            => 'Первичный ключ для "%s" не задан',
    self::ENUM_BAD_VALUE        => 'Поле "%s" не содержит варианта "%s"',
    self::FILE_NOT_EXISTS       => 'Файл "%s" не существует',
    self::PROCESSING_FILE       => 'Ошибка при обработке файла: %s',
    self::NOT_REPOSITORY        => 'Класс "%s" не является репозиторием',
    self::COLUMN_NOT_EXISTS     => 'Столбец "%s" не существует',
  );
} 
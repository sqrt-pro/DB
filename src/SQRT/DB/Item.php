<?php

namespace SQRT\DB;

use SQRT\Image;
use SQRT\Helpers\Container;
use Stringy\StaticStringy;

class Item extends Container
{
  /** @var Manager */
  protected $manager;
  protected $table;
  protected $is_new;
  protected $fields;
  protected $autoescape  = true;
  protected $primary_key;
  protected $files_path;
  protected $public_path;
  protected $serialized;

  function __construct(Manager $manager, $table = null, $is_new = true)
  {
    $this->manager = $manager;
    $this->table   = $table;
    $this->is_new  = $is_new;

    $this->init();
  }

  /** Установить значение */
  public function set($name, $value)
  {
    $this->vars[$name] = $value;

    return $this;
  }

  /**
   * Добавить флаг в битовую маску
   * @return static
   */
  public function bitAdd($name, $value)
  {
    return $this->set($name, $this->get($name, 0) | $value);
  }

  /** Установить список битов из массива. $clean - обнулить значение до */
  public function bitSet($name, array $bits, $clean = true)
  {
    if ($clean) {
      $this->set($name, 0);
    }

    foreach ($bits as $b) {
      $this->bitAdd($name, $b);
    }

    return $this;
  }

  /** Получить значение битовой маски в виде массива опций */
  public function bitGet($name, array $options)
  {
    $arr = array();

    foreach ($options as $b) {
      if ($this->bitCheck($name, $b)) {
        $arr[] = $b;
      }
    }

    return $arr;
  }

  /** Удалить флаг из битовой маски */
  public function bitRemove($name, $value)
  {
    return $this->set($name, $this->get($name, 0) & ~ $value);
  }

  /** Проверить, есть ли флаг в битовой маске */
  public function bitCheck($name, $value)
  {
    return (bool) ($this->get($name, 0) & $value);
  }

  /** Сохранение объекта */
  public function save($reload = false, $no_triggers = false)
  {
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $pk = $this->getPrimaryKey();

    if (!$no_triggers) {
      $this->beforeSave();
    }

    if ($this->isNew()) {
      $reload = true;

      $q = $qb->insert($this->getTable())
        ->setFromArray($this->getVarsForDB());
    } else {
      if (!$pk) {
        Exception::ThrowError(Exception::PK_NOT_SET, __CLASS__);
      }

      $q = $qb->update($this->getTable())
        ->setFromArray($this->getVarsForDB())
        ->where(array($pk => $this->get($pk)));
    }

    $m->query($q);

    if ($this->isNew()) {
      $id = $m->getConnection()->lastInsertId();

      $this->setIsNew(false);
      $this->set($pk, $id);
    }

    if ($reload && $pk) {
      $this->load();
    }

    if (!$no_triggers) {
      $this->afterSave();
    }

    return $this;
  }

  /** Загрузка данных из БД */
  public function load()
  {
    $m = $this->getManager();
    if (!$pk = $this->getPrimaryKey()) {
      Exception::ThrowError(Exception::PK_NOT_SET, __CLASS__);
    }

    $q = $m->getQueryBuilder()
      ->select($this->getTable())
      ->where(array($pk => $this->get($pk)));

    $this->vars = $m->query($q)->fetch(\PDO::FETCH_ASSOC);

    return $this;
  }

  /** Удаление объекта из БД */
  public function delete($no_triggers = false)
  {
    if (!$no_triggers) {
      $this->beforeDelete();
    }

    $m = $this->getManager();
    if (!$pk = $this->getPrimaryKey()) {
      Exception::ThrowError(Exception::PK_NOT_SET, __CLASS__);
    }

    $q = $m->getQueryBuilder()
      ->delete($this->getTable())
      ->where(array($pk => $this->get($pk)));

    $m->query($q);

    if (!$no_triggers) {
      $this->afterDelete();
    }

    return $this;
  }

  public function get($name, $default = false, $autoescape = null)
  {
    $autoescape = !is_null($autoescape) ? $autoescape : $this->getAutoescape();
    $value      = parent::get($name, $default);

    return $autoescape && is_string($value) ? htmlspecialchars($value) : $value;
  }

  /** Приведение поля с датой в нужный формат */
  public function getAsDate($column, $default = false, $format = null)
  {
    if ($v = $this->get($column)) {
      return date($format ?: 'd.m.Y', strtotime($v));
    }

    return $default;
  }

  /** Установка значения поля с датой */
  public function setAsDate($column, $value)
  {
    $this->set($column, $value ? date('Y-m-d H:i:s', strtotime($value)) : null);

    return $this;
  }

  /** NumberFormat для числового поля */
  public function getAsFloat($column, $default = false, $decimals = null, $point = null, $thousands = null)
  {
    if (is_null($decimals)) {
      $decimals = 2;
    }
    if (is_null($point)) {
      $point = '.';
    }
    if (is_null($thousands)) {
      $thousands = '';
    }
    $v = $this->get($column);

    return $v ? number_format($v, $decimals, $point, $thousands) : $default;
  }

  /** Получить значение из сериализованного поля */
  public function getSerialized($column, $default = false)
  {
    if (!isset($this->serialized[$column])) {
      $v = $this->get($column, false, false);

      $this->serialized[$column] = !empty($v) ? unserialize($v) : false;
    }

    return $this->serialized[$column] ?: $default;
  }

  /** Сериализовать значение в поле */
  public function setSerialized($column, $value)
  {
    unset($this->serialized[$column]);

    return $this->set($column, !empty($value) ? serialize($value) : null);
  }

  /** Сбросить кеш десериализованных полей */
  public function resetSerializedCache()
  {
    $this->serialized = null;

    return $this;
  }

  /** Режим безопасного отображения данных с полным экранированием */
  public function getAutoescape()
  {
    return $this->autoescape;
  }

  /** Режим безопасного отображения данных с полным экранированием */
  public function setAutoescape($autoescape)
  {
    $this->autoescape = $autoescape;

    return $this;
  }

  /** @return mixed */
  public function getTable()
  {
    return $this->table;
  }

  /** @return static */
  public function setTable($table)
  {
    $this->table = $table;

    return $this;
  }

  /** Первичный ключ для записей */
  public function getPrimaryKey()
  {
    return $this->primary_key;
  }

  /**
   * Первичный ключ для записей
   * @return static
   */
  public function setPrimaryKey($primary_key)
  {
    $this->primary_key = $primary_key;

    return $this;
  }

  /** Флаг, что при сохранении делается insert */
  public function isNew()
  {
    return $this->is_new;
  }

  /**
   * Признак, что при сохранении делается insert
   * @return static
   */
  public function setIsNew($is_new)
  {
    $this->is_new = $is_new;

    return $this;
  }

  /** @return Manager */
  public function getManager()
  {
    return $this->manager;
  }

  /** @return static */
  public function setManager(Manager $manager)
  {
    $this->manager = $manager;

    return $this;
  }

  /** Список полей для сохранения в БД */
  public function getFields()
  {
    return !empty($this->fields) ? $this->fields : false;
  }

  /**
   * Список полей для сохранения в БД
   * @return static
   */
  public function setFields(array $fields = null)
  {
    $this->fields = $fields;

    return $this;
  }

  /** Путь к файлам на сервере */
  public function getFilesPath()
  {
    return $this->files_path;
  }

  /**
   * Путь к файлам на сервере
   * @return static
   */
  public function setFilesPath($files_path)
  {
    $this->files_path = $files_path;

    return $this;
  }

  /** Путь на веб-сервере к файлам */
  public function getPublicPath()
  {
    return $this->public_path;
  }

  /**
   * Путь на веб-сервере к файлам
   * @return static
   */
  public function setPublicPath($public_path)
  {
    $this->public_path = $public_path;

    return $this;
  }

  public static function MakeGetterName($column)
  {
    return 'get' . StaticStringy::upperCamelize($column);
  }

  public static function MakeSetterName($column)
  {
    return 'set' . StaticStringy::upperCamelize($column);
  }

  /** Значения для сохранения в БД */
  protected function getVarsForDB()
  {
    if (!$fields = $this->getFields()) {
      return $this->vars;
    }

    return array_intersect_key($this->vars, array_flip($fields));
  }

  /**
   * Обработка файла и сохранение в объект
   * $column - поле
   * $file - исходный файл
   * $filename - относительный путь выходного файла
   * $name - название файла
   */
  protected function processFile($column, $file, $filename, $name = null)
  {
    if (!is_file($file)) {
      Exception::ThrowError(Exception::FILE_NOT_EXISTS, $file);
    }

    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $size = filesize($file);

    $this->copyFile($file, $this->getFilesPath() . $filename);

    return $this->setSerialized(
      $column,
      array(
        'file'      => $filename,
        'extension' => $ext,
        'size'      => $size,
        'name'      => $name,
      )
    );
  }

  /**
   * Обработка изображения и сохранение в объект
   * $column - поле
   * $file - исходный файл
   * $filename - относительный путь выходного файла
   * $name - название файла
   * $size - "размерность" файла
   */
  protected function processImage($column, $file, $filename, $name = null, $size = false)
  {
    if (!is_file($file)) {
      Exception::ThrowError(Exception::FILE_NOT_EXISTS, $file);
    }

    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $path = $this->getFilesPath() . $filename;

    /** @var Image $img */
    $method = 'prepareImageFor' . StaticStringy::upperCamelize($column);
    $img    = call_user_func_array(array($this, $method), array($file, $size));
    if ($img instanceof Image) {
      $img->save($path);
      $w = $img->getWidth();
      $h = $img->getHeight();
    } else {
      $this->copyFile($file, $path);
      list($w, $h) = getimagesize($path);
    }

    $file_arr = array(
      'file'      => $filename,
      'extension' => $ext,
      'size'      => filesize($path),
      'name'      => $name,
      'width'     => $w,
      'height'    => $h,
    );

    if ($size) {
      $this->resetSerializedCache($column);
      $arr = $this->getSerialized($column);

      $arr[$size] = $file_arr;
    } else {
      $arr = $file_arr;
    }

    return $this->setSerialized($column, $arr);
  }

  /** Перенос загруженного или копирование файла */
  protected function copyFile($source, $dest)
  {
    $dir  = dirname($dest);

    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    if (is_uploaded_file($source)) {
      $res = @move_uploaded_file($source, $dest);
    } else {
      $res = @copy($source, $dest);
    }

    if (!$res) {
      $err = error_get_last();

      Exception::ThrowError(Exception::PROCESSING_FILE, $err['message']);
    }
  }

  /** Человеческое представление размера файла */
  protected function getHumanFileSize($size)
  {
    if ($size < 1024) {
      return $size . ' байт';
    } elseif ($size < 1024*1024) {
      return number_format($size / 1024, 2, '.', '')  . ' килобайт';
    } else {
      return number_format($size / 1024 / 1024, 2, '.', '')  . ' мегабайт';
    }
  }

  /** Создание пути для файла */
  protected function makeFileName($column, $filename, $size = false)
  {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $pk  = $this->getPrimaryKey();
    $dir = '/' . $this->getTable();
    if ($id = $this->get($pk)) {
      $dir .= '/' . $id;
    }

    return $dir . '/' . $column . ($size ? '_' . $size : '') . '_' . md5(uniqid()) . ($ext ? '.' . $ext : '');
  }

  protected function beforeSave()
  {

  }

  protected function afterSave()
  {

  }

  protected function beforeDelete()
  {

  }

  protected function afterDelete()
  {

  }

  protected function init()
  {

  }
}
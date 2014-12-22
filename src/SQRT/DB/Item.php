<?php

namespace SQRT\DB;

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

  function __construct(Manager $manager, $table = null, $is_new = true)
  {
    $this->manager = $manager;
    $this->table   = $table;
    $this->is_new  = $is_new;

    $this->init();
  }

  /** Сохранение объекта */
  public function save($reload = false)
  {
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $pk = $this->getPrimaryKey();

    $this->beforeSave();

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

    $this->afterSave();

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
  public function delete()
  {
    $this->beforeDelete();

    $m = $this->getManager();
    if (!$pk = $this->getPrimaryKey()) {
      Exception::ThrowError(Exception::PK_NOT_SET, __CLASS__);
    }

    $q = $m->getQueryBuilder()
      ->delete($this->getTable())
      ->where(array($pk => $this->get($pk)));

    $m->query($q);

    $this->afterDelete();

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
    $v = $this->get($column, false, false);

    return !empty($v) ? unserialize($v) : $default;
  }

  /** Сериализовать значение в поле */
  public function setSerialized($column, $value)
  {
    $this->set($column, !empty($value) ? serialize($value) : null);
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
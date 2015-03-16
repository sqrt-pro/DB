<?php

namespace SQRT\DB;

use SQRT\QueryBuilder;
use SQRT\QueryBuilder\Query;

class Manager
{
  protected $prefix;

  protected $path_to_items;
  protected $path_to_schemas;
  protected $path_to_forms;

  /** @var Schema[] */
  protected $schemas;

  /** @var \PDO[] */
  protected $connections;
  protected $schema_namespace = '\\Schema';

  protected $collections;
  protected $queries;

  protected $debug = false;

  /**
   * Выполнение запроса к БД
   *
   * @return \PDOStatement
   * @throws \SQRT\Exception
   */
  public function query($sql, $values = null, $connection = null)
  {
    if ($this->isDebug()) {
      $i = $this->getQueriesCount();
      $t = microtime(true);

      $this->queries[$i] = array('query' => $sql, 'values' => $values);
    }

    $pdo = $this->getConnection($connection);

    if ($sql instanceof Query) {
      $values = $values ?: $sql->getBindedValues();
      $sql    = $sql->asStatement();
    }

    $stmt = $pdo->prepare($sql);
    $res = $stmt->execute($values);

    if ($this->isDebug()) {
      $this->queries[$i]['time'] = microtime(true) - $t;
    }

    if (!$res) {
      Exception::ThrowError(Exception::QUERY, $stmt->errorInfo());
    }

    return $stmt;
  }

  /**
   * Получить все записи из запроса в виде массива
   * Если указать $key ключами массива будет значение этого столбца
   */
  public function fetchAll($sql, $key = null, $values = null, $connection = null)
  {
    $st = $this->query($sql, $values, $connection);

    if (!$res = $st->fetchAll(\PDO::FETCH_ASSOC)) {
      return false;
    }

    if (!$key) {
      return $res;
    }

    if (!array_key_exists($key, current($res))) {
      Exception::ThrowError(Exception::COLUMN_NOT_EXISTS, $key);
    }

    $out = array();
    foreach ($res as $row) {
      $out[$row[$key]] = $row;
    }

    return $out;
  }

  /**
   * Вызов $callable к каждому результату выборки
   * Функция вернет массив с результатами вызова функций
   */
  public function each($sql, $callable, $values = null, $connection = null)
  {
    $st = $this->query($sql, $values, $connection);

    $out = false;
    while($row = $st->fetch(\PDO::FETCH_ASSOC)) {
      $out[] = call_user_func_array($callable, array($row));
    }

    return $out;
  }

  /** Получить одну строку в виде массива */
  public function fetchOne($sql, $values = null, $connection = null)
  {
    $st = $this->query($sql, $values, $connection);

    return $st->fetch(\PDO::FETCH_ASSOC);
  }

  /** Получить значение из запроса */
  public function fetchValue($sql, $col = null, $values = null, $connection = null)
  {
    $st = $this->query($sql, $values, $connection);

    if (!$row = $st->fetch(\PDO::FETCH_ASSOC)) {
      return false;
    }

    if (!$col) {
      return current($row);
    }

    if (!array_key_exists($col, $row)) {
      Exception::ThrowError(Exception::COLUMN_NOT_EXISTS, $col);
    }

    return $row[$col];
  }

  /** Получить один столбец в виде массива */
  public function fetchColumn($sql, $col = null, $values = null, $connection = null)
  {
    $st = $this->query($sql, $values, $connection);

    if (!$res = $st->fetchAll(\PDO::FETCH_ASSOC)) {
      return false;
    }

    if (!array_key_exists($col, current($res))) {
      Exception::ThrowError(Exception::COLUMN_NOT_EXISTS, $col);
    }

    $out = false;
    foreach ($res as $row) {
      $out[] = $row[$col];
    }

    return $out;
  }

  /**
   * Получить ассоциативный массив ключ-значение из запроса.
   * В результатах запроса должно быть ровно два столбца!
   */
  public function fetchPair($sql, $values = null, $connection = null)
  {
    $st = $this->query($sql, $values, $connection);

    return $st->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /** @return QueryBuilder */
  public function getQueryBuilder()
  {
    return new QueryBuilder($this->getPrefix());
  }

  /** Массив выполненных запросов к БД (требует debug = true) */
  public function getQueries()
  {
    return $this->queries ?: array();
  }

  /** Количество выполненных запросов к БД (требует debug = true) */
  public function getQueriesCount()
  {
    return count($this->getQueries());
  }

  /** Сумма время затраченное на выполнение всех запросов в секундах (требует debug = true) */
  public function getQueriesTime()
  {
    $res = array_reduce($this->getQueries(), function($result, $arr){
      if (isset($arr['time'])) {
        $result[] = $arr['time'];
      }

      return $result;
    }, array());

    return array_sum($res);
  }

  /** Сброс статистики по запросам в БД */
  public function resetQueries()
  {
    $this->queries = null;

    return $this;
  }

  /** @return \PDO */
  public function getConnection($name = null)
  {
    $name = $name ?: 'default';

    if (!isset($this->connections[$name])) {
      Exception::ThrowError(Exception::CONNECTION_NOT_EXISTS, $name);
    }

    $conn = $this->connections[$name];
    if (!$conn['pdo']) {
      $conn['pdo'] = new \PDO('mysql:host=' . $conn['host'] . ';dbname=' . $conn['db'], $conn['user'], $conn['pass']);
      $conn['pdo']->query('SET NAMES ' . $conn['charset']);

      $this->connections[$name] = $conn;
    }

    return $conn['pdo'];
  }

  /**
   * Добавление подключения
   *
   * @return static
   */
  public function addConnection($host, $user, $pass, $dbname, $charset = 'utf8', $name = null)
  {
    $this->connections[$name ?: 'default'] = array(
      'pdo'     => false,
      'host'    => $host,
      'user'    => $user,
      'pass'    => $pass,
      'db'      => $dbname,
      'charset' => $charset
    );

    return $this;
  }

  /** @return static */
  public function addSchema(Schema $schema)
  {
    $this->schemas[$schema->getName()] = $schema;

    return $this;
  }

  /** @return Schema */
  public function getSchema($class, $no_relations = false)
  {
    if ($no_relations) {
      $schema = $this->makeSchema($class, $no_relations);
    } else {
      $schema = isset($this->schemas[$class]) ? $this->schemas[$class] : false;

      if (empty($schema)) {
        if ($schema = $this->makeSchema($class)) {
          $this->addSchema($schema);
        }
      }
    }

    return $schema;
  }

  public function getAllSchemas()
  {
    return !empty($this->schemas) ? $this->schemas : false;
  }

  /** @return Collection */
  public function getCollection($name, $default = 'SQRT\DB\Collection')
  {
    if ($cl = $this->getCollectionClass($name)) {
      return new $cl($this);
    }

    if (!$s = $this->getSchema($name)) {
      Exception::ThrowError(Exception::SCHEMA_NOT_EXISTS, $name);
    }

    /** @var $obj Collection */
    $obj = new $default($this, $s->getTable(), $s->getItemClass());

    return $obj;
  }

  /** Класс для коллекции */
  public function getCollectionClass($name)
  {
    $name = strtolower($name);

    return isset($this->collections[$name]) ? $this->collections[$name] : false;
  }

  /**
   * Класс для коллекции
   * @return static
   */
  public function setCollectionClass($name, $class)
  {
    if (!class_exists($class) || !in_array('SQRT\DB\Collection', class_parents($class))) {
      Exception::ThrowError(Exception::NOT_COLLECTION, $class);
    }

    $this->collections[strtolower($name)] = $class;

    return $this;
  }

  /** Неймспейс, где будут искаться схемы */
  public function getSchemaNamespace()
  {
    return $this->schema_namespace;
  }

  /** @return static */
  public function setSchemaNamespace($schema_namespace)
  {
    $this->schema_namespace = $schema_namespace;

    return $this;
  }

  /** Префикс для всех таблиц */
  public function getPrefix()
  {
    return $this->prefix;
  }

  /** Префикс для всех таблиц */
  public function setPrefix($prefix)
  {
    $this->prefix = $prefix;

    return $this;
  }

  /** Режим отладки */
  public function isDebug()
  {
    return $this->debug;
  }

  /** Режим отладки */
  public function setDebug($debug = true)
  {
    $this->debug = $debug;

    return $this;
  }

  /** @return Schema */
  protected function makeSchema($class, $no_relations = false)
  {
    $nm = $this->getSchemaNamespace() . '\\' . $class;

    return class_exists($nm) ? new $nm($this, null, null, $no_relations) : false;
  }
}
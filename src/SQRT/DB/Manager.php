<?php

namespace SQRT\DB;

use SQRT\QueryBuilder;

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
      $this->queries[] = array('query' => $sql, 'values' => $values);
    }

    $pdo = $this->getConnection($connection);

    if ($sql instanceof QueryBuilder\Query) {
      $values = $values ?: $sql->getBindedValues();
      $sql    = $sql->asStatement();
    }

    $stmt = $pdo->prepare($sql);
    if (!$res = $stmt->execute($values)) {
      Exception::ThrowError(Exception::QUERY, $stmt->errorInfo());
    }

    return $stmt;
  }

  /** @return QueryBuilder */
  public function getQueryBuilder()
  {
    return new QueryBuilder($this->getPrefix());
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

  public function addConnection($host, $user, $pass, $dbname, $charset = 'UTF-8', $name = null)
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
  public function getSchema($class)
  {
    return isset($this->schemas[$class]) ? $this->schemas[$class] : false;
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
}
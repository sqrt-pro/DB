<?php

namespace SQRT\DB;

class Repository
{
  /** @var Item[] */
  protected $items;

  protected $manager;
  protected $table;
  protected $item_class = '\SQRT\DB\Item';
  protected $collection_class = '\SQRT\DB\Collection';

  function __construct(Manager $manager, $table = null, $item_class = null)
  {
    $this->manager = $manager;

    if ($table) {
      $this->setTable($table);
    }

    if ($item_class) {
      $this->setItemClass($item_class);
    }

    $this->init();
  }

  /**
   * Получить коллекцию с объектами
   * @return Collection
   */
  public function find($where = null, $orderby = null, $onpage = null, $page = null)
  {
    $this->items = null;

    $q = $this->getManager()
      ->getQueryBuilder()
      ->select($this->getTable())
      ->where($where)
      ->orderby($orderby)
      ->page($page, $onpage);

    return $this->fetch($q);
  }

  /**
   * Получение объекта из запроса
   * @return Item
   */
  public function fetchObject(\PDOStatement $statement)
  {
    $statement->setFetchMode(
      \PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE,
      $this->getItemClass(),
      array($this->getManager(), $this->getTable(), false)
    );

    /** @var Item $obj */
    $obj = $statement->fetch();

    return $obj;
  }

  /**
   * Получение объектов в коллекцию из SQL-запроса
   * @return Collection
   */
  public function fetch($query, $values = null)
  {
    $m  = $this->getManager();
    $st = $m->query($query, $values);

    /** @var $collection Collection */
    $class = $this->getCollectionClass();
    $collection = new $class;
    while ($obj = $this->fetchObject($st)) {
      if ($pk = $obj->getPrimaryKey()) {
        $collection->set($obj->get($pk), $obj);
      } else {
        $collection->add($obj);
      }
    }

    return $collection;
  }

  /**
   * Найти и получить один объект
   * @return Item
   */
  public function findOne($where = null, $orderby = null)
  {
    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->select($this->getTable())
      ->where($where)
      ->orderby($orderby)
      ->limit(1);

    return $this->fetchObject($m->query($q));
  }

  /** Количество элементов по запросу */
  public function count($where = null)
  {
    $m = $this->getManager();

    $q = $m->getQueryBuilder()
      ->select($this->getTable())
      ->columns('COUNT(*) as total')
      ->where($where);

    return $m->query($q)->fetchColumn();
  }

  /**
   * Создать новый объект
   * @return Item
   */
  public function make()
  {
    $c = $this->getItemClass();

    return new $c($this->getManager(), $this->getTable());
  }

  /** Применение $callable ко всем элементам выборки, без создания коллекции */
  public function each($callable, $where = null, $orderby = null, $onpage = null, $page = null)
  {
    $q = $this->getManager()
      ->getQueryBuilder()
      ->select($this->getTable())
      ->where($where)
      ->orderby($orderby)
      ->page($page, $onpage);

    $st = $this->getManager()->query($q);

    while($obj = $this->fetchObject($st)) {
      call_user_func_array($callable, array($obj));
    }

    return $this;
  }

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

  /** Класс для элемента */
  public function getItemClass()
  {
    return $this->item_class;
  }

  /** Класс для элемента */
  public function setItemClass($item_class)
  {
    $this->item_class = $item_class;

    return $this;
  }

  /** Класс для коллекции */
  public function getCollectionClass()
  {
    return $this->collection_class;
  }

  /** Класс для коллекции */
  public function setCollectionClass($collection_class)
  {
    $this->collection_class = $collection_class;

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

  protected function init()
  {

  }
}
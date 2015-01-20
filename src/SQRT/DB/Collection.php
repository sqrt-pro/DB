<?php

namespace SQRT\DB;

class Collection implements \IteratorAggregate, \Countable, \ArrayAccess
{
  /** @var Item[] */
  protected $items;

  protected $manager;
  protected $table;
  protected $item_class = '\SQRT\DB\Item';

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
   * Загрузить в коллекцию объекты
   * @return static
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
   * Добавление объекта в коллекцию
   * @return static
   */
  public function add(Item $item)
  {
    if ($pk = $item->getPrimaryKey()) {
      $this->items[$item->get($pk)] = $item;
    } else {
      $this->items[] = $item;
    }

    return $this;
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
   * @return static
   */
  public function fetch($query, $values = null)
  {
    $m  = $this->getManager();
    $st = $m->query($query, $values);

    while ($obj = $this->fetchObject($st)) {
      $this->add($obj);
    }

    return $this;
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

  /** Количество элементов в коллекции */
  public function count()
  {
    return count($this->items);
  }

  /** Проверка что в коллекции нет элементов */
  public function isEmpty()
  {
    return $this->count() == 0;
  }

  /** Проверка что в коллекции нет элементов */
  public function isNotEmpty()
  {
    return $this->count() > 0;
  }

  /** Количество элементов по запросу */
  public function countQuery($where = null)
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

  /** Применение $callable ко всем элементам коллекции, полученным ранее */
  public function map($callable)
  {
    if (!empty($this->items)) {
      array_map($callable, $this->items);
    }

    return $this;
  }

  /** Применение $callable ко всем элементам выборки, без наполнения коллекции */
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

  /** Фильтрация элементов коллекции */
  public function reduce($callable)
  {
    $this->items = array_filter($this->items, $callable);

    return $this;
  }

  /**
   * Сортировка элементов.
   * $sort имя поля или callable
   */
  public function sort($sort, $asc = true)
  {
    $method = false;
    if (!is_callable($sort)) {
      $method = Item::MakeGetterName($sort);
    }

    $sort_arr = array();
    foreach ($this->getIterator() as $item) {
      $v = $method
        ? (method_exists($item, $method) ? $item->$method() : $item->get($sort))
        : $sort($item);

      $sort_arr[$v][] = $item;
    }

    $keys = array_keys($sort_arr);
    if ($asc) {
      sort($keys);
    } else {
      rsort($keys);
    }

    $this->items = array();
    foreach ($keys as $k) {
      foreach ($sort_arr[$k] as $item) {
        $this->add($item);
      }
    }

    return $this;
  }

  /** @return Item[] */
  public function getIterator($as_array = false)
  {
    $arr = $this->items ?: array();

    return $as_array ? $arr : new \ArrayIterator($arr);
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

  public function getItemClass()
  {
    return $this->item_class;
  }

  /** @return static */
  public function setItemClass($item_class)
  {
    $this->item_class = $item_class;

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

  /** @return static */
  public function setItems($items)
  {
    $this->items = $items instanceof Collection ? $items->getIterator(true) : $items;

    return $this;
  }

  /** Проверка, есть ли элемент по ключу (ID) */
  public function has($key)
  {
    return isset($this->items[$key]);
  }

  /** Список ID элементов */
  public function getIDs()
  {
    return $this->isNotEmpty() ? array_keys($this->items) : array();
  }

  /**
   * Получить элемент по ключу (ID)
   * @return Item
   */
  public function get($key)
  {
    return $this->has($key) ? $this->items[$key] : false;
  }

  public function offsetExists($offset)
  {
    return $this->has($offset);
  }

  public function offsetGet($offset)
  {
    return $this->get($offset);
  }

  public function offsetSet($offset, $value)
  {
    $this->items[$offset] = $value;
  }

  public function offsetUnset($offset)
  {
    unset($this->items[$offset]);
  }

  protected function init()
  {

  }
}
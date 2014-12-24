<?php

namespace ORM;

use SQRT\DB\Exception;

/** Этот файл сгенерирован автоматически по схеме Authors */
abstract class Author extends \Base\Item
{
  /** @var \SQRT\DB\Collection|\Book[] */
  protected $books_arr;

  protected function init()
  {
    $this->setPrimaryKey('id');
    $this->setTable('authors');
    $this->setFields(
      array(
        'id',
        'name',
      )
    );
  }

  public function getId($default = null)
  {
    return $this->get('id', $default);
  }

  /** @return static */
  public function setId($id)
  {
    return $this->set('id', $id);
  }

  public function getName($default = null)
  {
    return $this->get('name', $default);
  }

  /** @return static */
  public function setName($name)
  {
    return $this->set('name', $name);
  }

  /** @return \SQRT\DB\Collection|\Book[] */
  public function getBooks($reload = false)
  {
    $c = $this->getManager()->getCollection('Books');

    if (is_null($this->books_arr) || $reload) {
      $this->books_arr = $c->find(array('author_id' => $this->get('id')))->getIterator(true);
    }

    return $c->setItems($this->books_arr);
  }

  /** @return static */
  public function setBooks($books_arr = null)
  {
    $this->books_arr = $books_arr;

    return $this;
  }
}

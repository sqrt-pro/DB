<?php

namespace ORM;

use SQRT\DB\Exception;

/** Этот файл сгенерирован автоматически по схеме Authors */
abstract class Author extends \Base\Item
{
  /** @var \Collection\Books|\Book[] */
  protected $my_books_arr;

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

  /** @return \Collection\Books|\Book[] */
  public function getMyBooks($reload = false)
  {
    $c = $this->getManager()->getCollection('Books');

    if (is_null($this->my_books_arr) || $reload) {
      $this->my_books_arr = $this->findMyBooks()->getIterator(true);
    }

    return $c->setItems($this->my_books_arr);
  }

  /** @return static */
  public function setMyBooks($my_books_arr = null)
  {
    $this->my_books_arr = $my_books_arr;

    return $this;
  }

  /** @return \Collection\Books|\Book[] */
  protected function findMyBooks()
  {
    return $this->getManager()->getCollection('Books')->find(array('author_id' => $this->get('id')));
  }
}

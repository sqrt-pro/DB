<?php

namespace ORM;

use SQRT\DB\Exception;
use SQRT\DB\Collection;

/** Этот файл сгенерирован автоматически по схеме Authors */
abstract class Author extends \Base\Item
{
  /** @var Collection|\Book[] */
  protected $my_books_arr;

  protected function init()
  {
    $this->setPrimaryKey('id');
    $this->setTable('authors');
    $this->setFields(
      array(
        'id',
        'name',
        'birthday',
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

  /** @return \SQRT\Helpers\DateTime|bool */
  public function getBirthday($format = null, $default = false)
  {
    return $this->getAsDate('birthday', $format ?: 'd.m.Y', $default);
  }

  /** @return static */
  public function setBirthday($birthday)
  {
    return $this->setAsDate('birthday', $birthday);
  }

  /** @return Collection|\Book[] */
  public function getMyBooks($reload = false)
  {
    if (is_null($this->my_books_arr) || $reload) {
      $this->my_books_arr = $this->findMyBooks();
    }

    return clone $this->my_books_arr;
  }

  /** @return static */
  public function setMyBooks($my_books_arr = null)
  {
    $this->my_books_arr = $my_books_arr;

    return $this;
  }

  /** @return Collection|\Book[] */
  protected function findMyBooks()
  {
    return $this->getManager()->getRepository('Books')->find(array('author_id' => $this->get('id')));
  }
}

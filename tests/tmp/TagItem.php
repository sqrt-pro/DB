<?php

namespace ORM;

use SQRT\DB\Exception;

/** Этот файл сгенерирован автоматически по схеме Tags */
abstract class Tag extends \Base\Item
{
  /** @var \SQRT\DB\Collection|\Book[] */
  protected $books_arr;

  protected $tbl_books = 'books_tags';

  protected function init()
  {
    $this->setPrimaryKey('id');
    $this->setTable('tags');
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
    $m = $this->getManager();
    $c = $m->getCollection('Books');

    if (is_null($this->books_arr) || $reload) {
      $q = $m->getQueryBuilder()
        ->select('books t')
        ->columns('t.*')
        ->join($this->tbl_books . ' j', 't.id = j.book_id')
        ->where(array('j.tag_custom_id' => $this->get('id')));
      
      $this->books_arr = $c->fetch($q)->getIterator(true);
    }

    return $c->setItems($this->books_arr);
  }

  public function addBook($book)
  {
    $id = $book instanceof \Book ? $book->get('id') : $book;
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $qb->insert($this->tbl_books)
      ->setEqual('book_id', $id)
      ->setEqual('tag_custom_id', $this->get('id'));
    $m->query($qb);

    return $this;
  }

  public function removeBook($book)
  {
    $id = $book instanceof \Book ? $book->get('id') : $book;
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $qb->delete($this->tbl_books)
      ->where(array('book_id' => $id, 'tag_custom_id' => $this->get('id')));
    $m->query($qb);

    return $this;
  }

  public function removeAllBooks()
  {
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $qb->delete($this->tbl_books)
      ->where(array('tag_custom_id' => $this->get('id')));
    $m->query($qb);

    return $this;
  }
}

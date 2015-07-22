<?php

namespace ORM;

use SQRT\DB\Exception;
use SQRT\DB\Collection;

/** Этот файл сгенерирован автоматически по схеме Tags */
abstract class Tag extends \Base\Item
{
  /** @var Collection|\Book[] */
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

  /** @return Collection|\Book[] */
  public function getBooks($reload = false)
  {
    if (is_null($this->books_arr) || $reload) {
      $this->books_arr = $this->findBooks();
    }

    return clone $this->books_arr;
  }

  /** @return static */
  public function addBook($book)
  {
    if (is_array($book) || $book instanceof \Traversable) {
      foreach ($book as $id) {
        $this->addBook($id);
      }
    } else {
      $m = $this->getManager();
      $q = $m->getQueryBuilder()
        ->insert($this->tbl_books)
        ->setEqual('book_id', $this->getBookPK($book))
        ->setEqual('tag_custom_id', $this->get('id'));
      $m->query($q);
    }

    return $this;
  }

  /** @return static */
  public function removeBook($book)
  {
    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->delete($this->tbl_books)
      ->where(array('book_id' => $this->getBookPK($book), 'tag_custom_id' => $this->get('id')));
    $m->query($q);

    return $this;
  }

  /** @return static */
  public function syncBooks($array)
  {
    $ids     = (array) $this->getBookPK($this->getBooks());
    $new_ids = (array) $this->getBookPK($array);

    $drop = array_diff($ids, $new_ids);
    $add  = array_unique(array_diff($new_ids, $ids));

    if (!empty($drop)) {
      $this->removeBook($drop);
    }

    if (!empty($add)) {
      $this->addBook($add);
    }

    return $this;
  }

  /** @return static */
  public function removeAllBooks()
  {
    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->delete($this->tbl_books)
      ->where(array('tag_custom_id' => $this->get('id')));
    $m->query($q);

    return $this;
  }

  protected function getBookPK($book)
  {
    if (is_array($book) || $book instanceof \Traversable) {
      $ids = array();
      foreach ($book as $item) {
        $ids[] = $this->getBookPK($item);
      }

      return $ids;
    }

    return $book instanceof \Book ? $book->get('id') : $book;
  }

  /** @return Collection|\Book[] */
  protected function findBooks()
  {
    $m = $this->getManager();
    $c = $m->getRepository('Books');
    $q = $m->getQueryBuilder()
      ->select('books t')
      ->columns('t.*')
      ->join($this->tbl_books . ' j', 't.id = j.book_id')
      ->where(array('j.tag_custom_id' => $this->get('id')));

    return $c->fetch($q);
  }
}

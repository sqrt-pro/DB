<?php

namespace ORM;

use SQRT\DB\Exception;

/** Этот файл сгенерирован автоматически по схеме Books */
abstract class Book extends \Base\Item
{
  /** @var \Author */
  protected $my_author;

  /** @var \SQRT\DB\Collection|\Tag[] */
  protected $my_tags_arr;

  protected $tbl_my_tags = 'books_tags';

  protected function init()
  {
    $this->setPrimaryKey('id');
    $this->setTable('books');
    $this->setFields(
      array(
        'id',
        'name',
        'author_id',
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

  public function getAuthorId($default = null)
  {
    return (int)$this->get('author_id', $default);
  }

  /** @return static */
  public function setAuthorId($author_id)
  {
    return $this->set('author_id', (int)$author_id);
  }

  /** @return \Author */
  public function getMyAuthor($reload = false)
  {
    if (!$id = $this->get('author_id')) {
      return false;
    }

    if (is_null($this->my_author) || $reload) {
      $c = $this->getManager()->getCollection('Authors');

      $this->my_author = $c->findOne(array('id' => $id));
    }

    return $this->my_author;
  }

  /** @return static */
  public function setMyAuthor(\Author $my_author)
  {
    $this->my_author = $my_author;

    return $this->set('author_id', $my_author->get('id'));
  }

  /** @return \SQRT\DB\Collection|\Tag[] */
  public function getMyTags($reload = false)
  {
    $m = $this->getManager();
    $c = $m->getCollection('Tags');

    if (is_null($this->my_tags_arr) || $reload) {
      $q = $m->getQueryBuilder()
        ->select('tags t')
        ->columns('t.*')
        ->join($this->tbl_my_tags . ' j', 't.id = j.tag_custom_id')
        ->where(array('j.book_id' => $this->get('id')));
      
      $this->my_tags_arr = $c->fetch($q)->getIterator(true);
    }

    return $c->setItems($this->my_tags_arr);
  }

  public function addMyTag($my_tag)
  {
    $id = $my_tag instanceof \Tag ? $my_tag->get('id') : $my_tag;

    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->insert($this->tbl_my_tags)
      ->setEqual('tag_custom_id', $id)
      ->setEqual('book_id', $this->get('id'));
    $m->query($q);

    return $this;
  }

  public function removeMyTag($my_tag)
  {
    $id = $my_tag instanceof \Tag ? $my_tag->get('id') : $my_tag;

    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->delete($this->tbl_my_tags)
      ->where(array('tag_custom_id' => $id, 'book_id' => $this->get('id')));
    $m->query($q);

    return $this;
  }

  public function removeAllMyTags()
  {
    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->delete($this->tbl_my_tags)
      ->where(array('book_id' => $this->get('id')));
    $m->query($q);

    return $this;
  }
}

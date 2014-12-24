<?php

namespace ORM;

use SQRT\DB\Exception;

/** Этот файл сгенерирован автоматически по схеме Books */
abstract class Book extends \Base\Item
{
  /** @var \Author */
  protected $author;

  /** @var \SQRT\DB\Collection|\Tag[] */
  protected $tags_arr;

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
  public function getAuthor($reload = false)
  {
    if (!$id = $this->get('author_id')) {
      return false;
    }

    if (is_null($this->author) || $reload) {
      $c = $this->getManager()->getCollection('Authors');

      $this->author = $c->findOne(array('id' => $id));
    }

    return $this->author;
  }

  /** @return static */
  public function setAuthor(\Author $author)
  {
    $this->author = $author;

    return $this->set('author_id', $author->get('id'));
  }

  /** @return \SQRT\DB\Collection|\Tag[] */
  public function getTags($reload = false)
  {
    $m = $this->getManager();
    $c = $m->getCollection('Tags');

    if (is_null($this->tags_arr) || $reload) {
      $q = $m->getQueryBuilder()
        ->select('tags t')
        ->columns('t.*')
        ->join('books_tags j', 't.id = j.tag_custom_id')
        ->where(array('j.book_id' => $this->get('id')));
      
      $this->tags_arr = $c->fetch($q)->getIterator(true);
    }

    return $c->setItems($this->tags_arr);
  }

  public function addTag($tag)
  {
    $id = $tag instanceof \Tag ? $tag->get('id') : $tag;
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $qb->insert('books_tags')
      ->setEqual('tag_custom_id', $id)
      ->setEqual('book_id', $this->get('id'));
    $m->query($qb);

    return $this;
  }

  public function removeTag($tag)
  {
    $id = $tag instanceof \Tag ? $tag->get('id') : $tag;
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $qb->delete('books_tags')
      ->where(array('tag_custom_id' => $id, 'book_id' => $this->get('id')));
    $m->query($qb);

    return $this;
  }

  public function removeAllTags()
  {
    $m  = $this->getManager();
    $qb = $m->getQueryBuilder();
    $qb->delete('books_tags')
      ->where(array('book_id' => $this->get('id')));
    $m->query($qb);

    return $this;
  }
}

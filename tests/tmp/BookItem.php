<?php

namespace ORM;

use SQRT\DB\Exception;
use SQRT\DB\Collection;

/** Этот файл сгенерирован автоматически по схеме Books */
abstract class Book extends \Base\Item
{
  /** @var \Author */
  protected $my_author;

  /** @var \Author */
  protected $redactor;

  /** @var Collection|\Tag[] */
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
        'redactor_id',
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
    return $this->set('author_id', is_null($author_id) ? null : (int)$author_id);
  }

  public function getRedactorId($default = null)
  {
    return (int)$this->get('redactor_id', $default);
  }

  /** @return static */
  public function setRedactorId($redactor_id)
  {
    return $this->set('redactor_id', is_null($redactor_id) ? null : (int)$redactor_id);
  }

  /** @return \Author */
  public function getMyAuthor($reload = false)
  {
    if (!$id = $this->get('author_id')) {
      return false;
    }

    if (is_null($this->my_author) || $reload) {
      $this->my_author = $this->findOneMyAuthor($id);
    }

    return $this->my_author;
  }

  /** @return static */
  public function setMyAuthor(\Author $my_author)
  {
    $this->my_author = $my_author;

    return $this->set('author_id', $my_author->get('id'));
  }

  /** @return \Author */
  public function getRedactor($reload = false)
  {
    if (!$id = $this->get('redactor_id')) {
      return false;
    }

    if (is_null($this->redactor) || $reload) {
      $this->redactor = $this->findOneRedactor($id);
    }

    return $this->redactor;
  }

  /** @return static */
  public function setRedactor(\Author $redactor)
  {
    $this->redactor = $redactor;

    return $this->set('redactor_id', $redactor->get('id'));
  }

  /** @return Collection|\Tag[] */
  public function getMyTags($reload = false)
  {
    if (is_null($this->my_tags_arr) || $reload) {
      $this->my_tags_arr = $this->findMyTags();
    }

    return clone $this->my_tags_arr;
  }

  /** @return static */
  public function addMyTag($my_tag)
  {
    if (is_array($my_tag) || $my_tag instanceof \Traversable) {
      foreach ($my_tag as $id) {
        $this->addMyTag($id);
      }
    } else {
      $m = $this->getManager();
      $q = $m->getQueryBuilder()
        ->insert($this->tbl_my_tags)
        ->setEqual('tag_custom_id', $this->getMyTagPK($my_tag))
        ->setEqual('book_id', $this->get('id'));
      $m->query($q);
    }

    return $this;
  }

  /** @return static */
  public function removeMyTag($my_tag)
  {
    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->delete($this->tbl_my_tags)
      ->where(array('tag_custom_id' => $this->getMyTagPK($my_tag), 'book_id' => $this->get('id')));
    $m->query($q);

    return $this;
  }

  /** @return static */
  public function syncMyTags($array)
  {
    $ids     = (array) $this->getMyTagPK($this->getMyTags());
    $new_ids = (array) $this->getMyTagPK($array);

    $drop = array_diff($ids, $new_ids);
    $add  = array_unique(array_diff($new_ids, $ids));

    if (!empty($drop)) {
      $this->removeMyTag($drop);
    }

    if (!empty($add)) {
      $this->addMyTag($add);
    }

    return $this;
  }

  /** @return static */
  public function removeAllMyTags()
  {
    $m = $this->getManager();
    $q = $m->getQueryBuilder()
      ->delete($this->tbl_my_tags)
      ->where(array('book_id' => $this->get('id')));
    $m->query($q);

    return $this;
  }

  /** @return \Author */
  protected function findOneMyAuthor($id)
  {
    return $this->getManager()->getRepository('Authors')->findOne(array('id' => $id));
  }

  /** @return \Author */
  protected function findOneRedactor($id)
  {
    return $this->getManager()->getRepository('Authors')->findOne(array('id' => $id));
  }

  protected function getMyTagPK($my_tag)
  {
    if (is_array($my_tag) || $my_tag instanceof \Traversable) {
      $ids = array();
      foreach ($my_tag as $item) {
        $ids[] = $this->getMyTagPK($item);
      }

      return $ids;
    }

    return $my_tag instanceof \Tag ? $my_tag->get('id') : $my_tag;
  }

  /** @return Collection|\Tag[] */
  protected function findMyTags()
  {
    $m = $this->getManager();
    $c = $m->getRepository('Tags');
    $q = $m->getQueryBuilder()
      ->select('tags t')
      ->columns('t.*')
      ->join($this->tbl_my_tags . ' j', 't.id = j.tag_custom_id')
      ->where(array('j.book_id' => $this->get('id')));

    return $c->fetch($q);
  }
}

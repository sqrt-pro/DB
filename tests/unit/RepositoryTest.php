<?php

use SQRT\DB\Manager;
use SQRT\DB\Repository;

class RepositoryTest extends PHPUnit_Framework_TestCase
{
  function testCount()
  {
    $m = $this->getManager();
    $c = new Repository($m);
    $c->setTable('pages');

    $this->assertEquals(0, $c->count(), 'Записей нет');

    $this->fillPages(10);

    $this->assertEquals(10, $c->count(), 'Подсчет всех записей в таблице');
    $this->assertEquals(4, $c->count(array('`id` < 5')), 'Подсчет с условием');
  }

  function testFind()
  {
    $this->fillPages(10);

    $m = $this->getManager();
    $r = new Repository($m);
    $r->setTable('pages');

    $collection = $r->find('`id` > 5');

    $this->assertEquals(5, $collection->count(), '5 элементов после выборки');

    $collection = $r->find('id > 100');
    $this->assertTrue($collection->isEmpty(), 'Выборка пуста');
  }

  function testEach()
  {
    $this->fillPages(10);

    $m = $this->getManager();
    $r = new Repository($m);
    $r->setTable('pages');

    $arr = array();
    $r->each(
      function (\SQRT\DB\Item $item) use (&$arr) {
        $arr[] = $item->get('id');
      }, 'id > 5', 'id DESC', 3, 2
    );

    $this->assertEquals(array(7, 6), $arr, 'Массивы совпадают');
  }

  function testFindOne()
  {
    $this->fillPages(10);

    $m = $this->getManager();
    $c = new Repository($m);
    $c->setTable('pages');

    $p = $c->findOne(2);
    $this->assertInstanceOf('SQRT\DB\Item', $p, 'Объект Item');
    $this->assertEquals(2, $p->get('id'), 'ID = 2');

    $p = $c->findOne('id < 10', 'id DESC');
    $this->assertEquals(9, $p->get('id'), 'ID = 9');
  }

  function testMake()
  {
    $m = $this->getManager();

    $c = new Repository($m, 'pages');
    $this->assertInstanceOf('SQRT\DB\Item', $c->make(), 'Объект Item по-умолчанию');

    $c = new Repository($m, 'pages', '\TestItem');

    $p = $c->make();
    $this->assertInstanceOf('\TestItem', $p, 'Объект \TestItem');
    $this->assertTrue($p->isNew(), 'Новый объект');
    $this->assertFalse($p->get('id'), 'ID не задан');
  }

  protected function fillPages($num, $from = 1)
  {
    $m = $this->getManager();
    for ($i = $from; $i <= $num; $i++) {
      $q = $m->getQueryBuilder()
        ->insert('pages')
        ->setFromArray(array('id' => $i, 'name' => 'Page #' . $i));

      $m->query($q);
    }
  }

  protected function getManager()
  {
    $m = new Manager();
    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);
    $m->setPrefix('test_');

    return $m;
  }

  protected function setUp()
  {
    $this->tearDown();

    $q = 'CREATE TABLE `test_pages` ('
      . '`id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,'
      . '`name` VARCHAR(250),'
      . '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
      . ')';
    $this->getManager()->query($q);
  }

  protected function tearDown()
  {
    $this->getManager()->query('DROP TABLE IF EXISTS test_pages');
  }
}
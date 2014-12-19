<?php

require_once __DIR__ . '/../init.php';

use SQRT\DB\Manager;
use SQRT\DB\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
  function testCount()
  {
    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $this->assertEquals(0, $c->countQuery(), 'Записей нет');

    $this->fillPages(10);

    $this->assertEquals(10, $c->countQuery(), 'Подсчет всех записей в таблице');
    $this->assertEquals(4, $c->countQuery(array('`id` < 5')), 'Подсчет с условием');
  }

  function testFind()
  {
    $this->fillPages(10);

    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $this->assertEquals(0, $c->count(), 'Коллекция пуста');

    $c->find('`id` > 5');

    $this->assertEquals(5, $c->count(), '4 элемента после выборки');
  }

  function testFindOne()
  {
    $this->fillPages(3);

    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $p = $c->findOne(2);
    $this->assertInstanceOf('SQRT\DB\Item', $p, 'Объект Item');
    $this->assertEquals(2, $p->get('id'), 'ID = 2');
  }

  function testMake()
  {
    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $p = $c->make();
    $this->assertInstanceOf('SQRT\DB\Item', $p, 'Объект Item');
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
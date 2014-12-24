<?php

require_once __DIR__ . '/../init.php';

use SQRT\DB\Manager;
use SQRT\DB\Collection;

class CollectionTest extends PHPUnit_Framework_TestCase
{
  function testSort()
  {

  }

  function testEach()
  {

  }

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
    $c1 = new Collection($m);
    $c1->setTable('pages');

    $this->assertTrue($c1->isEmpty(), 'Коллекция пуста');

    $c1->find('`id` > 5');

    $this->assertEquals(5, $c1->count(), '5 элементов после выборки');
    $this->assertInstanceOf('ArrayIterator', $c1->getIterator(), 'Iterator');
    $this->assertTrue(is_array($c1->getIterator(true)), 'Массив');

    $c2 = new Collection($m);
    $c2->setItems($c1);
    $this->assertEquals(5, $c2->count(), '5 элементов загружено');
    $this->assertTrue($c2->isNotEmpty(), 'Коллекция содержит элементы');
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

    $c = new Collection($m, 'pages');
    $this->assertInstanceOf('SQRT\DB\Item', $c->make(), 'Объект Item по-умолчанию');

    $c = new Collection($m, 'pages', '\TestItem');

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
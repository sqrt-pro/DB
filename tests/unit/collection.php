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
    $this->fillPages(10);

    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $this->assertTrue($c->isEmpty(), 'Коллекция пуста');

    $arr = false;
    $c->each(
      function (\SQRT\DB\Item $item) use (&$arr) {
        $arr[] = $item->get('id');
      },
      'id > 5'
    );

    $exp = array(6, 7, 8, 9, 10);
    $this->assertEquals($exp, $arr, 'Результат');
    $this->assertTrue($c->isEmpty(), 'Коллекция все еще пуста');
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

  function testArrayAccess()
  {
    $m = $this->getManager();
    $c = new Collection($m);

    $this->assertEquals(0, $c->count(), '0 элементов');
    $this->assertFalse($c->has(10), 'Нет id=10');

    $c[10] = new TestItem($m);

    $this->assertCount(1, $c, '1 элемент');
    $this->assertTrue(isset($c[10]), 'Есть объект');
    $this->assertInstanceOf('TestItem', $c[10], 'Объект TestItem');

    unset($c[10]);

    $this->assertTrue($c->isEmpty(), 'Пустой набор');
  }

  function testFind()
  {
    $this->fillPages(10);

    $m = $this->getManager();
    $c1 = new Collection($m);
    $c1->setTable('pages');

    $this->assertTrue($c1->isEmpty(), 'Коллекция пуста');

    $arr = false;
    $c1->map(
      function (\SQRT\DB\Item $item) use (&$arr) {
        $arr[] = $item->get('id');
      }
    );
    $this->assertFalse($arr, 'В коллекции нет элементов');

    $c1->find('`id` > 5');

    $this->assertEquals(5, $c1->count(), '5 элементов после выборки');
    $this->assertInstanceOf('ArrayIterator', $c1->getIterator(), 'Iterator');
    $this->assertTrue(is_array($c1->getIterator(true)), 'Массив');

    $c2 = new Collection($m);
    $c2->setItems($c1);
    $this->assertEquals(5, $c2->count(), '5 элементов загружено');
    $this->assertTrue($c2->isNotEmpty(), 'Коллекция содержит элементы');

    $c1->find('id < 3');
    $this->assertEquals(2, $c1->count(), 'Новые элементы затирают старые');

    $arr = false;
    $c2->map(
      function (\SQRT\DB\Item $item) use (&$arr) {
        $arr[] = $item->get('id');
      }
    );
    $exp = array(6, 7, 8, 9, 10);
    $this->assertEquals($exp, $arr, 'Проход по всем элементам');
  }

  function testFindOne()
  {
    $this->fillPages(10);

    $m = $this->getManager();
    $c = new Collection($m);
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
<?php

require_once __DIR__ . '/../init.php';

use SQRT\DB\Manager;
use SQRT\DB\Collection;

class ItemTest extends PHPUnit_Framework_TestCase
{
  function testBeforeSave()
  {

  }

  function testChangePrimaryKey()
  {

  }

  function testOneToMany()
  {

  }

  function testOneToOne()
  {

  }

  function testManyToMany()
  {

  }

  function testAddFile()
  {

  }

  function testSave()
  {
    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $p = $c->make();
    $p->setPrimaryKey('id');
    $p->set('name', 'Hello');
    $p->save();

    $this->assertEquals(1, $c->countQuery(), 'В базе создался один объект');

    $this->assertEquals(1, $p->get('id'), 'Primary Key сохраняется после insert');
    $this->assertFalse($p->isNew(), 'Флаг is new снят');
    $this->assertNotEmpty($p->get('created_at'), 'При вставке данные загружаются из БД автоматически');

    $p->set('name', 'World');
    $p->save();

    $this->assertEquals(1, $c->countQuery(), 'В базе остался один объект');

    $p1 = $c->findOne(1);
    $this->assertEquals('World', $p1->get('name'), 'Изменения сохранились');

    $p1->setPrimaryKey(false);
    try {
      $p1->save();

      $this->fail('Ожидаемое исключение');
    } catch (\SQRT\DB\Exception $e) {
      $this->assertEquals(\SQRT\DB\Exception::PK_NOT_SET, $e->getCode(), 'Первичный ключ не задан');
    }
  }

  function testDelete()
  {
    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $p = $c->make();
    $p->setPrimaryKey('id');
    $p->set('name', 'Hello');
    $p->save();

    $p1 = $c->findOne(1);
    $p1->setPrimaryKey(false);
    try {
      $p1->delete();

      $this->fail('Ожидаемое исключение');
    } catch (\SQRT\DB\Exception $e) {
      $this->assertEquals(\SQRT\DB\Exception::PK_NOT_SET, $e->getCode(), 'Первичный ключ не задан');
    }

    $p->delete();
    $this->assertEquals(0, $c->countQuery(), 'Записей в таблице нет');
  }

  function testFieldsSave()
  {
    $m = $this->getManager();
    $c = new Collection($m);
    $c->setTable('pages');

    $p = $c->make();
    $p->setPrimaryKey('id');
    $p->setFields(array('id', 'name', 'created_at'));
    $p->set('id', 12);
    $p->set('name', 'Hello');
    $p->set('hello', 'there');

    $p->save();
  }

  function testAutoescape()
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $str = '<a href="#">John</a>';

    $i->set('name', $str);
    $this->assertEquals('&lt;a href=&quot;#&quot;&gt;John&lt;/a&gt;', $i->get('name'), 'Автоэкранирование по умолчанию включено');
    $this->assertEquals($str, $i->get('name', false, false), 'Получение начального значения');

    $arr = array(1, 2, 3);
    $i->set('arr', $arr);
    $this->assertEquals($arr, $i->get('arr'), 'Затрагиваются только строки');

    $i->setAutoescape(false);
    $this->assertEquals($str, $i->get('name'), 'Отключение автоэкранирования по умолчанию');
  }

  /**
   * @dataProvider dataGetAsDate
   */
  function testGetAsDate($date, $format, $exp, $msg)
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $i->set('date', $date);
    $this->assertEquals($exp, $i->getAsDate('date', false, $format), $msg);
  }

  function dataGetAsDate()
  {
    return array(
      array(false, null, false, 'Нулевая дата'),
      array('1812-06-24', null, '24.06.1812', 'Даты вне Unix time'),
      array('2012-01-10', null, '10.01.2012', 'Год вначале'),
      array('21.12.2012', 'Y-m-d H:i', '2012-12-21 00:00', 'Преобразование'),
    );
  }

  /**
   * @dataProvider dataSetAsDate
   */
  function testSetAsDate($value, $exp, $msg)
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $i->setAsDate('birthday', $value);

    $this->assertEquals($exp, $i->getAsDate('birthday'), $msg);
  }

  function dataSetAsDate()
  {
    return array(
      array('', false, 'Дата не указана №1'),
      array(false, false, 'Дата не указана №2'),
      array('24.06.1812', '24.06.1812', 'Сквозное преобразование'),
      array('2012-01-01 00:00', '01.01.2012', 'Разные форматы')
    );
  }

  function testGetAsFloat()
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $i->set('price', 123456.789);

    $exp = '123456.79';
    $this->assertEquals($exp, $i->getAsFloat('price'), 'Приведение по-умолчанию');

    $exp = '123`456,78900';
    $this->assertEquals($exp, $i->getAsFloat('price', false, 5, ',', '`'), 'Форматирование');
  }

  function testSerialized()
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $arr = array('male', 'female');
    $i->setSerialized('gender', $arr);

    $this->assertEquals(serialize($arr), $i->get('gender', false, false), 'Данные хранятся в сериализованной строке');
    $this->assertEquals($arr, $i->getSerialized('gender'), 'Десериализация');
  }

  function testMakeNames()
  {
    $this->assertEquals('getHelloThere', \SQRT\DB\Item::MakeGetterName('hello_there'), 'Геттер');
    $this->assertEquals('setHelloThere', \SQRT\DB\Item::MakeSetterName('hello_there'), 'Сеттер');
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
    $q = 'CREATE TABLE `test_pages` ('
      . '`id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,'
      . '`name` VARCHAR(250),'
      . '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
      . ')';
    $this->getManager()->query($q);
  }

  protected function tearDown()
  {
    $this->getManager()
      ->query('DROP TABLE IF EXISTS test_pages');
  }
}
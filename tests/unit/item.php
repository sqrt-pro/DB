<?php

require_once __DIR__ . '/../init.php';

use SQRT\DB\Manager;
use SQRT\DB\Collection;

class ItemTest extends PHPUnit_Framework_TestCase
{
  protected $temp;

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
    $m = $this->getManager();
    $i = new TestItem($m);
    $i->setTable('pages');
    $i->setPublicPath('/files');
    $i->setFilesPath($this->temp);

    $i->setFile(__FILE__);
    $res = $i->getSerialized('file');

    $this->assertNotEmpty($res['file'], 'Путь к файлу');
    $this->assertFileExists($this->temp . $res['file'], 'Файл сохранился');
    $this->assertEquals('php', $res['extension']);
    $this->assertEquals(filesize(__FILE__), $res['size'], 'Размер верный');
    $this->assertEquals('item.php', $res['name'], 'Название файла');
  }

  function testAddPhoto()
  {
    $m = $this->getManager();
    $i = new TestItem($m);
    $i->setTable('pages');
    $i->setPublicPath('/files');
    $i->setFilesPath($this->temp);

    $i->setPhoto($this->temp . '/vertical.jpg');

    $res = $i->getSerialized('photo');
    $this->assertArrayHasKey('thumb', $res);
    $this->assertArrayHasKey('big', $res);

    $this->assertFileExists($this->temp . $res['big']['file'], 'Файл сохранился');
    $this->assertEquals('jpg', $res['big']['extension'], 'Расширение');
    $this->assertEquals(17890, $res['big']['size'], 'Размер');
    $this->assertEquals('vertical.jpg', $res['big']['name'], 'Название');
    $this->assertEquals(225, $res['big']['width'], 'Ширина');
    $this->assertEquals(300, $res['big']['height'], 'Высота');

    $this->assertFileExists($this->temp . $res['thumb']['file'], 'Файл сохранился');
    $this->assertEquals('jpg', $res['thumb']['extension'], 'Расширение');
    $this->assertEquals(3606, $res['thumb']['size'], 'Размер');
    $this->assertEquals('vertical.jpg', $res['thumb']['name'], 'Название');
    $this->assertEquals(99, $res['thumb']['width'], 'Ширина');
    $this->assertEquals(100, $res['thumb']['height'], 'Высота');
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

  function testSetNull()
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $i->set('id', 1);

    $this->assertEquals(array('id' => 1), $i->toArray(), 'Значение ID = 1');

    $i->set('id', null);

    $this->assertEquals(array('id' => null), $i->toArray(), 'Значение ID = null');
  }

  function testBitmask()
  {
    $m = new \SQRT\DB\Manager();
    $i = new \SQRT\DB\Item($m);

    $this->assertFalse($i->bitCheck('level', 1), 'Бит еще не установлен');

    $i->bitAdd('level', 1);
    $i->bitAdd('level', 4);

    $this->assertEquals(5, $i->get('level'), 'Значение битовой маски с двумя битами');
    $this->assertTrue($i->bitCheck('level', 1), 'Бит 1 установлен');
    $this->assertFalse($i->bitCheck('level', 2), 'Бит 2 не установлен');
    $this->assertTrue($i->bitCheck('level', 4), 'Бит 4 установлен');
    $this->assertEquals(array(1, 4), $i->bitGet('level', array(1, 2, 4)), 'Значение битовой маски');

    $i->bitRemove('level', 1);
    $this->assertFalse($i->bitCheck('level', 1), 'Бит 1 удален');
    $this->assertEquals(4, $i->get('level'), 'Значение битовой маски c одним битом');

    $i->bitRemove('level', 1);
    $this->assertFalse($i->bitCheck('level', 1), 'Удаление выключенного бита');

    $i->bitSet('level', array(2, 4));
    $this->assertEquals(array(2, 4), $i->bitGet('level', array(1, 2, 4)), 'Биты установлены с обнулением');

    $i->bitSet('level', array(1, 4), false);
    $this->assertEquals(array(1, 2, 4), $i->bitGet('level', array(1, 2, 4)), 'Биты установлены без обнулением');
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

    $i->set('gender', false);
    $this->assertEquals($arr, $i->getSerialized('gender'), 'Десериализация закеширована');

    $i->resetSerializedCache();
    $this->assertFalse($i->getSerialized('gender'), 'Кеширование сброшено');
  }

  function testMakeNames()
  {
    $this->assertEquals('getHelloThere', \SQRT\DB\Item::MakeGetterName('hello_there'), 'Геттер');
    $this->assertEquals('setHelloThere', \SQRT\DB\Item::MakeSetterName('hello_there'), 'Сеттер');
  }

  function testBeforeAfter()
  {
    $i = new TestItem($this->getManager(), 'pages');
    $i->setPrimaryKey('id');
    $i->set('name', 1);
    $i->save();

    $this->assertEquals(10, $i->get('id'), 'Триггер beforeSave установил ID');
    $this->assertEquals(1, $i->get('after_save'), 'Триггер afterSave установил поле');

    $i->delete();

    $this->assertEquals(1, $i->get('before_delete'), 'Триггер beforeDelete установил поле');
    $this->assertEquals(1, $i->get('after_delete'), 'Триггер afterDelete установил поле');
  }

  function testNoTriggers()
  {
    $i = new TestItem($this->getManager(), 'pages');
    $i->setPrimaryKey('id');
    $i->set('name', 1);
    $i->save(false, true);

    $this->assertEquals(1, $i->get('id'), 'Триггер beforeSave не сработал');
    $this->assertEmpty($i->get('after_save'), 'Триггер afterSave не сработал');

    $i->delete(true);

    $this->assertEmpty($i->get('before_delete'), 'Триггер beforeDelete не сработал');
    $this->assertEmpty($i->get('after_delete'), 'Триггер afterDelete не сработал');
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
    $this->temp = realpath(__DIR__ . '/../tmp');

    $m = $this->getManager();
    $q = 'CREATE TABLE `test_pages` ('
      . '`id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,'
      . '`name` VARCHAR(250),'
      . '`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
      . ')';

    $m->query($q);
    $m->query('DROP TABLE IF EXISTS test_books_tags');
    $m->query('DROP TABLE IF EXISTS test_books');
    $m->query('DROP TABLE IF EXISTS test_authors');
    $m->query('DROP TABLE IF EXISTS test_tags');
  }

  protected function tearDown()
  {
    $m = $this->getManager();
    $m->query('DROP TABLE IF EXISTS test_pages');
  }
}
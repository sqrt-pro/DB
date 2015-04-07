<?php

use SQRT\DB\Exception;

class managerTest extends PHPUnit_Framework_TestCase
{
  function testConnection()
  {
    $m = new \SQRT\DB\Manager();

    try {
      $m->getConnection('not_exists');
    } catch (Exception $e) {
      $this->assertEquals(\SQRT\DB\Exception::CONNECTION_NOT_EXISTS, $e->getCode(), 'Код ошибки');
    }

    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);

    $pdo = $m->getConnection();
    $this->assertInstanceOf('\PDO', $pdo, 'Объект PDO');
  }

  function testQuery()
  {
    $m = new \SQRT\DB\Manager();
    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);
    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB, 'UTF-8', 'another');

    $this->assertEquals(0, $m->getQueriesCount(), 'Количество запросов = 0');
    $this->assertEquals(0, $m->getQueriesTime(), 'Время выполнения - 0');

    try {
      $m->query('SELECT * FROM `not_exists`');

      $this->fail('Ожидаемое исключение');
    } catch (Exception $e) {
      $this->assertEquals(Exception::QUERY, $e->getCode());
    }

    $this->assertEquals(0, $m->getQueriesCount(), 'Количество запросов = 0, т.к. debug не включен');

    $m->setDebug(true);

    $m->query('CREATE TABLE `names` (`id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, `name` varchar(250))');
    $m->query('INSERT INTO `names` (`id`, `name`) VALUES (:id, :name)', array('id' => 1, 'name' => 'John'), 'another');

    $this->assertEquals(2, $m->getQueriesCount(), 'Количество запросов = 2');
    $this->assertNotEmpty($m->getQueriesTime(), 'Время выполнения больше нуля');

    $m->resetQueries();
    $this->assertEquals(0, $m->getQueriesCount(), 'Статистика сброшена');

    $res = $m->query('SELECT * FROM `names`');
    $this->assertInstanceOf('\PDOStatement', $res, 'Результат запроса есть');

    $this->assertEquals(array(array('id' => 1, 'name' => 'John')), $res->fetchAll(PDO::FETCH_ASSOC));
  }

  function testFetchAll()
  {
    $m = $this->getManager();

    $t = 'pages';
    $this->fillTable($t);

    $q   = 'SELECT id, name FROM ' . $t . ' WHERE id > :id LIMIT 3';
    $res = $m->fetchAll($q, null, array('id' => 3));

    $exp = array(
      array('id' => 4, 'name' => 'John The 4'),
      array('id' => 5, 'name' => 'John The 5'),
      array('id' => 6, 'name' => 'John The 6'),
    );
    $this->assertEquals($exp, $res, 'Массив без упорядочивания');

    $res = $m->fetchAll($q, 'id', array('id' => 3));

    $exp = array(
      4 => array('id' => 4, 'name' => 'John The 4'),
      5 => array('id' => 5, 'name' => 'John The 5'),
      6 => array('id' => 6, 'name' => 'John The 6'),
    );
    $this->assertEquals($exp, $res, 'Массив упорядоченный по столбцу');

    try {
      $m->fetchAll($q, 'not_exists', array('id' => 3));

      $this->fail('Ожидаемое исключение');
    } catch (Exception $e) {
      $this->assertEquals(Exception::COLUMN_NOT_EXISTS, $e->getCode(), 'Код ошибки');
    }

    $this->assertFalse($m->fetchAll($q, 'ololo', array('id' => 30)), 'Исключения не будет, т.к. выборка пустая');
  }

  function testEach()
  {
    $m = $this->getManager();

    $t = 'pages';
    $this->fillTable($t);

    $q   = 'SELECT id, name FROM ' . $t . ' WHERE id > :id LIMIT 3';
    $res = $m->each(
      $q,
      function ($row) {
        return $row['id'];
      },
      array('id' => 5)
    );

    $exp = array(6, 7, 8);
    $this->assertEquals($exp, $res, 'Результат выполнения');

    $res = $m->each(
      $q,
      function ($row) {
        return $row['id'];
      },
      array('id' => 50)
    );

    $this->assertFalse($res, 'Пустая выборка');
  }

  function testFetchPair()
  {
    $m = $this->getManager();

    $t = 'pages';
    $this->fillTable($t);

    $q = 'SELECT id, name FROM ' . $t . ' WHERE id > :id LIMIT 3';

    $res = $m->fetchPair($q, array('id' => 3));
    $exp = array(
      4 => 'John The 4',
      5 => 'John The 5',
      6 => 'John The 6',
    );

    $this->assertEquals($exp, $res, 'Результат');
  }

  function testFetchColumn()
  {
    $m = $this->getManager();

    $t = 'pages';
    $this->fillTable($t);

    $q = 'SELECT id, name FROM ' . $t . ' WHERE id > :id LIMIT 3';

    $res = $m->fetchColumn($q, 'id', array('id' => 3));
    $exp = array(4, 5, 6);

    $this->assertEquals($exp, $res, 'Результат');
  }

  function testFetchOne()
  {
    $m = $this->getManager();

    $t = 'pages';
    $this->fillTable($t);

    $q = 'SELECT id, name FROM ' . $t . ' WHERE id > :id LIMIT 3';

    $res = $m->fetchOne($q, array('id' => 3));
    $exp = array('id' => 4, 'name' => 'John The 4');

    $this->assertEquals($exp, $res, 'Результат');
  }

  function testFetchValue()
  {
    $m = $this->getManager();

    $t = 'pages';
    $this->fillTable($t);

    $q = 'SELECT id, name FROM ' . $t . ' WHERE id > :id LIMIT 3';

    $res = $m->fetchValue($q, null, array('id' => 3));
    $this->assertEquals(4, $res, 'Если $col не указан - первый столбец');

    $res = $m->fetchValue($q, 'name', array('id' => 3));
    $this->assertEquals('John The 4', $res, 'Столбец указан явно');

    $res = $m->fetchValue($q, 'name', array('id' => 30));
    $this->assertFalse($res, 'Пустая выборка');

    try {
      $res = $m->fetchValue($q, 'not_exists', array('id' => 3));

      $this->fail('Ожидаемое исключение');
    } catch (Exception $e) {
      $this->assertEquals(Exception::COLUMN_NOT_EXISTS, $e->getCode(), 'Код ошибки');
    }
  }

  function testQueryBuilderExecute()
  {
    $m = new \SQRT\DB\Manager();
    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);
    $m->setPrefix('test_');

    $m->query('CREATE TABLE `test_names` (`id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, `name` varchar(250))');

    $qb = $m->getQueryBuilder();
    $q = $qb->insert('names')->setFromArray(array('name' => 'John'));

    $this->assertEquals('INSERT INTO `test_names` SET `name`="John"', $q->asSQL(), 'Префикс подцепился');

    $m->query($q);
    $this->assertEquals(1, $m->getConnection()->lastInsertId(), 'ID добавленной записи #1');

    $m->query($q, array('set_name' => 'Mark'));
    $this->assertEquals(2, $m->getConnection()->lastInsertId(), 'ID добавленной записи #2');

    $q = $qb->select('names')->columns('name')->where(2);

    $this->assertEquals('Mark', $m->query($q)->fetchColumn(), 'Значение переопределено при вызове');
  }

  function testAddSchema()
  {
    $m = new \SQRT\DB\Manager();

    $this->assertFalse($m->getAllSchemas(), 'Схемы еще не добавлены');

    $s = new \SQRT\DB\Schema($m);
    $s->setName('Users');
    $m->addSchema($s);

    $this->assertEquals($s, $m->getSchema('Users'), 'Схема добавлена и доступна по имени');
  }

  function testGetCollection()
  {
    $m = new \SQRT\DB\Manager();
    $s = new \SQRT\DB\Schema($m);
    $s->setName('Users');
    $s->setTable('users');
    $s->setItemClass('\User');
    $m->addSchema($s);

    $c = $m->getCollection('Users');
    $this->assertInstanceOf('SQRT\DB\Collection', $c, 'Класс коллекции по умолчанию');
    $this->assertEquals('users', $c->getTable(), 'Таблица из схемы');
    $this->assertEquals('\User', $c->getItemClass(), 'Класс Item из Схемы');

    $m->setCollectionClass('users', 'TestCollection');

    $c = $m->getCollection('Users');
    $this->assertInstanceOf('TestCollection', $c, 'Заданный класс для коллекции');
    $this->assertEquals('test', $c->getTable(), 'Таблица из init()');
    $this->assertEquals('\TestItem', $c->getItemClass(), 'Класс Item из init()');

    try {
      $m->setCollectionClass('users', 'managerTest');

      $this->fail('Ожидаемое исключение');
    } catch (Exception $e) {
      $this->assertEquals(\SQRT\DB\Exception::NOT_COLLECTION, $e->getCode(), 'Код ошибки');
    }
  }

  protected function tearDown()
  {
    $m = new \SQRT\DB\Manager();
    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);
    $m->query('DROP TABLE IF EXISTS `names`');
    $m->query('DROP TABLE IF EXISTS `test_names`');
  }

  protected function getManager()
  {
    $m = new \SQRT\DB\Manager();
    $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);
    $m->setPrefix('test_');

    return $m;
  }

  protected function makeTable($table)
  {
    $m = $this->getManager();

    $m->query('DROP TABLE IF EXISTS ' . $table);

    $q = 'CREATE TABLE ' . $table . ' ('
      . '`id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, '
      . '`name` varchar(250), '
      . '`age` int(10) UNSIGNED'
      . ')';

      $m->query($q);
  }

  protected function fillTable($table, $num = 10)
  {
    $this->makeTable($table);
    $m = $this->getManager();

    for ($i = 1; $i <= $num; $i++) {
      $q = 'INSERT INTO ' . $table . ' (id, name, age) VALUES (:id, :name, :age)';
      $m->query($q, array('id' => $i, 'name' => 'John The ' . $i, 'age' => $i * 5));
    }
  }
}

class TestCollection extends \SQRT\DB\Collection
{
  protected function init()
  {
    $this->setTable('test');
    $this->setItemClass('\TestItem');
  }
}
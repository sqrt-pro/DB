<?php

require_once __DIR__ . '/../init.php';

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

    try {
      $m->query('SELECT * FROM `not_exists`');

      $this->fail('Ожидаемое исключение');
    } catch (Exception $e) {
      $this->assertEquals(Exception::QUERY, $e->getCode());
    }

    $m->query('CREATE TABLE `names` (`id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, `name` varchar(250))');
    $m->query('INSERT INTO `names` (`id`, `name`) VALUES (:id, :name)', array('id' => 1, 'name' => 'John'), 'another');

    $res = $m->query('SELECT * FROM `names`');
    $this->assertInstanceOf('\PDOStatement', $res, 'Результат запроса есть');

    $this->assertEquals(array(array('id' => 1, 'name' => 'John')), $res->fetchAll(PDO::FETCH_ASSOC));
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
    $m->addSchema($s);

    $this->assertInstanceOf('SQRT\DB\Collection', $m->getCollection('Users'), 'Класс коллекции по умолчанию');

    $m->setCollectionInfo('users', 'TestCollection', 'users', 'Item');

    $this->assertInstanceOf('TestCollection', $m->getCollection('Users'), 'Заданный класс для коллекции');

    try {
      $m->setCollectionInfo('users', 'managerTest', 'users', 'item');

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
}

class TestCollection extends \SQRT\DB\Collection
{

}
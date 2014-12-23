<?php

require_once __DIR__ . '/../init.php';

use SQRT\DB\Schema;

class schemaTest extends PHPUnit_Framework_TestCase
{
  protected $temp;

  function testAddColumns()
  {
    $m = $this->getManager(null, false);
    $s = new Schema($m);
    $s->add('one', Schema::COL_INT, 'INT(10) UNSIGNED');
    $s->add('two', Schema::COL_BOOL, 'BOOL DEFAULT 0', 'some');

    $one = array(
      'column'     => 'one',
      'type'       => Schema::COL_INT,
      'definition' => 'INT(10) UNSIGNED',
      'options'    => null
    );
    $two = array(
      'column'     => 'two',
      'type'       => Schema::COL_BOOL,
      'definition' => 'BOOL DEFAULT 0',
      'options'    => 'some'
    );

    $this->assertEquals($one, $s->get('one'), 'Поле one');
    $this->assertEquals($two, $s->get('two'), 'Поле two');
    $this->assertFalse($s->get('three'), 'Несуществующее поле');
  }

  function testMigrationCreateTable()
  {
    $m = $this->getManager('test_');
    $s = new Schema($m);
    $s->setTable('users');

    $s->addId()
      ->addBool('is_active')
      ->addInt('age')
      ->addChar('name')
      ->addEnum('type', array('one', 'two'))
      ->addFloat('price')
      ->addText('text', true)
      ->addFile('image');

    $exp  = file_get_contents($this->temp . '/MigrationCreate.php');
    $name = 'my migration';
    $res  = $s->makeMigration($name);

//    file_put_contents($this->temp . '/MigrationCreate.php', $s->makeMigration($name));

    $this->assertEquals($exp, $res, 'Генерация файла миграции');
  }

  function testMigrationUpdateTable()
  {
    $m = $this->getManager('test_');
    $s = new Schema($m);
    $s->setTable('pages');

    $s->addId()
      ->addBool('is_active')
      ->addFloat('price');

    $exp  = file_get_contents($this->temp . '/MigrationUpdate.php');
    $name = 'my migration';
    $res  = $s->makeMigration($name);

//    file_put_contents($this->temp . '/MigrationUpdate.php', $s->makeMigration($name));

    $this->assertEquals($exp, $res, 'Генерация файла миграции');
  }

  function testMigrationWithoutID()
  {
    $m = $this->getManager('test_');
    $s = new Schema($m);
    $s->setTable('pages');

    $s->addChar('name')
      ->addFloat('price');

    $exp  = file_get_contents($this->temp . '/MigrationNoID.php');
    $name = 'my migration';
    $res  = $s->makeMigration($name);

//    file_put_contents($this->temp . '/MigrationNoID.php', $s->makeMigration($name));

    $this->assertEquals($exp, $res, 'Схема без ID');
  }

  function testIndexes()
  {
    $m = $this->getManager('test_');
    $s = new Schema($m);
    $s->setTable('users')
      ->addId('token')
      ->addInt('age')
      ->addIndex('age')
      ->addUniqueIndex('token', 'age');

    $exp  = file_get_contents($this->temp . '/MigrationIndexes.php');
    $name = 'my migration';
    $res  = $s->makeMigration($name);

//    file_put_contents($this->temp . '/MigrationIndexes.php', $s->makeMigration($name));

    $this->assertEquals($exp, $res, 'Индекс');
  }

  function testForeignKey()
  {
    $m = $this->getManager('test_');

    $a = new Schema($m);
    $a->setTable('authors');
    $a->addId();
    $a->addChar('name');

    $name = 'authors';
    $res = $a->makeMigration($name);

    $s = new Schema($m);
    $s->setTable('books');

    $s->addId()
      ->addInt('author_id')
      ->addForeignKey('author_id', $a, null, Schema::FK_RESTRICT, Schema::FK_CASCADE);

    $exp  = file_get_contents($this->temp . '/MigrationForeignKey.php');
    $name = 'foreign_key';
    $res  = $s->makeMigration($name);

//    file_put_contents($this->temp . '/MigrationForeignKey.php', $s->makeMigration($name));

    $this->assertEquals($exp, $res, 'Внешний ключ');
  }

  function testItemClass()
  {
    $m = $this->getManager('test_');

    $s = new Schema($m);
    $s->setName('Users');

    $this->assertEquals('User', $s->getItemClass(), 'Название сгенерировалось из названия схемы');

    $s->setName('Schedule');
    $this->assertEquals('Schedule', $s->getItemClass(), 'Нет s на конце');

    $s->setName('News');
    $s->setItemClass('News');
    $this->assertEquals('News', $s->getItemClass(), 'Явно заданное имя класса');
  }

  function testMakeItemAndCollection()
  {
    $m = $this->getManager('test_');
    $s = new Schema($m);
    $s->setName('Users');
    $s->setTable('users');
    $s->addId()
      ->addBool('is_active')
      ->addEnum('type', array('new', 'old'))
      ->addInt('age')
      ->addChar('name')
      ->addFloat('price')
      ->addTime('created_at')
      ->addFile('pdf')
      ->addImage('avatar')
      ->addImage('photo', array('thumb', 'big'));

    $exp = file_get_contents($this->temp . '/UsersCollection.php');

//    file_put_contents($this->temp . '/UsersCollection.php', $s->makeCollection());
    $this->assertEquals($exp, $s->makeCollection(), 'Коллекция');

    $exp = file_get_contents($this->temp . '/UserItem.php');

//    file_put_contents($this->temp . '/UserItem.php', $s->makeItem());
    $this->assertEquals($exp, $s->makeItem(), 'Item');
  }

  protected function setUp()
  {
    $this->temp = realpath(__DIR__ . '/../tmp');

    $m = $this->getManager();
    $m->query('CREATE TABLE `test_pages` (`id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, `name` VARCHAR(250))');
  }

  protected function tearDown()
  {
    $m = $this->getManager();
    $m->query('DROP TABLE IF EXISTS `test_pages`');
    $m->query('DROP TABLE IF EXISTS `test_users`');
    $m->query('DROP TABLE IF EXISTS `phinxlog`');
  }

  protected function saveMigr($res, Schema $s, $name = 'my migration')
  {
    file_put_contents(TEST_MIGR . '/' . $s->makeMigrationName($name), $res);
  }

  protected function getManager($prefix = null, $conn = true)
  {
    $m = new \SQRT\DB\Manager();
    if ($prefix) {
      $m->setPrefix($prefix);
    }
    if ($conn) {
      $m->addConnection(TEST_HOST, TEST_USER, TEST_PASS, TEST_DB);
    }

    return $m;
  }
}
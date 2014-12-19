<?php

require_once __DIR__ . '/../init.php';

use SQRT\DB\Schema;

class schemaTest extends PHPUnit_Framework_TestCase
{
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
      ->addText('text');

    $exp = <<<PHP
<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    \$tbl = \$this->table('test_users', array('id' => 'id'));
    \$tbl->addColumn("is_active", "boolean", array ( 'default' => 0,));
    \$tbl->addColumn("age", "integer", array ( 'length' => 10, 'default' => 0, 'signed' => true,));
    \$tbl->addColumn("name", "string", array ( 'length' => 255,));
    \$tbl->addColumn("type", "string", array ( 'default' => NULL, 'length' => 255,));
    \$tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    \$tbl->addColumn("text", "text");
    \$tbl->save();
  }

  public function down()
  {
    \$tbl = \$this->table('test_users', array('id' => 'id'));
    \$tbl->drop();
  }
}

PHP;

    $res = $s->makeMigration('my migration');
    $this->saveMigr($res, $s);

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

    $exp = <<<PHP
<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    \$tbl = \$this->table('test_pages', array('id' => 'id'));
    \$tbl->addColumn("is_active", "boolean", array ( 'default' => 0,));
    \$tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    \$tbl->removeColumn("name");
    \$tbl->save();
  }

  public function down()
  {
    \$tbl = \$this->table('test_pages', array('id' => 'id'));
    \$tbl->removeColumn("is_active");
    \$tbl->removeColumn("price");
    // TODO: добавить инструкции для создания столбца name
    \$tbl->save();
  }
}

PHP;

    $res = $s->makeMigration('my migration');
    $this->assertEquals($exp, $res, 'Генерация файла миграции');
  }

  function testMigrationWithoutID()
  {
    $m = $this->getManager('test_');
    $s = new Schema($m);
    $s->setTable('pages');

    $s->addChar('name')
      ->addFloat('price');

    $exp = <<< PHP
<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    \$tbl = \$this->table('test_pages', array('id' => false));
    \$tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    \$tbl->removeColumn("id");
    \$tbl->changeColumn("name", "string", array ( 'length' => 255,));
    \$tbl->save();
  }

  public function down()
  {
    \$tbl = \$this->table('test_pages', array('id' => false));
    \$tbl->removeColumn("price");
    // TODO: добавить инструкции для создания столбца id
    \$tbl->save();
  }
}

PHP;

    $res = $s->makeMigration('my migration');
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

    $exp = <<< PHP
<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    \$tbl = \$this->table('test_users', array('id' => 'token'));
    \$tbl->addColumn("age", "integer", array ( 'length' => 10, 'default' => 0, 'signed' => true,));
    \$tbl->addIndex(array("age"));
    \$tbl->addIndex(array("token", "age"), array("unique" => true));
    \$tbl->save();
  }

  public function down()
  {
    \$tbl = \$this->table('test_users', array('id' => 'token'));
    \$tbl->drop();
  }
}

PHP;

    $res = $s->makeMigration('my migration');
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
    $this->saveMigr($res, $a, $name);

    $s = new Schema($m);
    $s->setTable('books');

    $s->addId()
      ->addInt('author_id')
      ->addForeignKey('author_id', $a, null, Schema::FK_RESTRICT, Schema::FK_CASCADE);

    $exp = <<< PHP
<?php

use Phinx\Migration\AbstractMigration;

class ForeignKey extends AbstractMigration
{
  public function up()
  {
    \$tbl = \$this->table('test_books', array('id' => 'id'));
    \$tbl->addColumn("author_id", "integer", array ( 'length' => 10, 'default' => 0, 'signed' => true,));
    \$tbl->addForeignKey("author_id", "test_authors", "id", array("delete" => "RESTRICT", "update" => "CASCADE"));
    \$tbl->save();
  }

  public function down()
  {
    \$tbl = \$this->table('test_books', array('id' => 'id'));
    \$tbl->drop();
  }
}

PHP;

    $name = 'foreign_key';
    $res = $s->makeMigration($name);

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
      ->addTime('created_at');

    $exp = <<<PHP
<?php

namespace Collection;

/**
 * @method \User findOne(\$where = null) Найти и получить один объект
 * @method \User make() Создать новый объект
 * @method \User fetchObject(\PDOStatement \$statement) Получение объекта из запроса
*/
class Users extends \Base\Collection
{
  protected function init()
  {
    \$this->setItemClass('\User');
    \$this->setTable('users');
  }
}

PHP;

    $this->assertEquals($exp, $s->makeCollection(), 'Коллекция');

    $exp = <<< PHP
<?php

namespace ORM;

use SQRT\DB\Exception;

class User extends \Base\Item
{
  const TYPE_NEW = 'new';
  const TYPE_OLD = 'old';

  protected static \$type_arr = array(
    self::TYPE_NEW => 'new',
    self::TYPE_OLD => 'old',
  );

  protected function init()
  {
    \$this->setPrimaryKey('id');
    \$this->setTable('users');
    \$this->setFields(
      array(
        'is_active',
        'type',
        'age',
        'name',
        'price',
        'created_at',
      )
    );
  }

  public function getId(\$default = null)
  {
    return \$this->get('id', \$default);
  }

  /** @return static */
  public function setId(\$id)
  {
    return \$this->set('id', \$id);
  }

  public function getIsActive(\$default = null)
  {
    return (int)\$this->get('is_active', \$default);
  }

  /** @return static */
  public function setIsActive(\$is_active)
  {
    return \$this->set('is_active', (int)\$is_active);
  }

  public function getType(\$default = null)
  {
    return \$this->get('type', \$default);
  }

  public function getTypeName()
  {
    return static::GetNameForType(\$this->getType());
  }

  /** @return static */
  public function setType(\$type)
  {
    if (!empty(\$type) && !static::GetNameForType(\$type)) {
      Exception::ThrowError(Exception::ENUM_BAD_VALUE, 'type', \$type);
    }

    return \$this->set('type', \$type);
  }

  public function getAge(\$default = null)
  {
    return (int)\$this->get('age', \$default);
  }

  /** @return static */
  public function setAge(\$age)
  {
    return \$this->set('age', (int)\$age);
  }

  public function getName(\$default = null)
  {
    return \$this->get('name', \$default);
  }

  /** @return static */
  public function setName(\$name)
  {
    return \$this->set('name', \$name);
  }

  public function getPrice(\$default = false, \$decimals = null, \$point = null, \$thousands = null)
  {
    return \$this->getAsFloat('price', \$default, \$decimals, \$point, \$thousands);
  }

  /** @return static */
  public function setPrice(\$price)
  {
    return \$this->set('price', \$price);
  }

  public function getCreatedAt(\$default = false, \$format = null)
  {
    return \$this->getAsDate('created_at', \$default, \$format);
  }

  /** @return static */
  public function setCreatedAt(\$created_at)
  {
    return \$this->setAsDate('created_at', \$created_at);
  }

  public static function GetTypeArr()
  {
    return static::\$type_arr;
  }

  public static function GetNameForType(\$type)
  {
    \$a = static::GetTypeArr();

    return isset(\$a[\$type]) ? \$a[\$type] : false;
  }
}

PHP;

    $this->assertEquals($exp, $s->makeItem(), 'Item');
  }

  protected function setUp()
  {
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
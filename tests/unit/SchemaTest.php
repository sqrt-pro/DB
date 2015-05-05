<?php

use SQRT\DB\Schema;

class schemaTest extends PHPUnit_Framework_TestCase
{
  protected $temp;
  protected $last_migr;

  function testRelations()
  {
    $m = $this->getManager('test_');

    // Создание схем
    $authors = new Schema($m, 'authors');
    $authors
      ->addId()
      ->addChar('name');

    $books = new Schema($m);
    $books
      ->setName('Books')
      ->addId()
      ->addChar('name');

    $tags = new Schema($m);
    $tags
      ->setName('Tags')
      ->addId()
      ->addChar('name');

    // Расстановка ключей

    $books_tags = new Schema($m);
    $books_tags
      ->setTable('books_tags')
      ->addOneToOne($books, null, null, Schema::FK_CASCADE)
      ->addOneToOne($tags, 'tag_custom_id');

    $authors->addOneToMany($books, null, null, 'MyBooks', 'MyBook');

    $books
      ->addOneToOne($authors, null, null, Schema::FK_RESTRICT, Schema::FK_CASCADE, 'MyAuthors', 'MyAuthor')
      ->addOneToOne($authors, 'redactor_id', null, Schema::FK_RESTRICT, Schema::FK_CASCADE, 'Redactors', 'Redactor')
      ->addManyToMany($tags, $books_tags, 'tag_custom_id', null, null, null, 'MyTags', 'MyTag');

    $tags->addManyToMany($books, $books_tags, null, 'tag_custom_id');

    // Генерация миграций

//    $this->saveMigr($authors, 'new authors');
//    $this->saveMigr($books, 'new books');
//    $this->saveMigr($tags, 'new tags');
//    $this->saveMigr($books_tags, 'new books tags');

    $exp  = file_get_contents($this->temp . '/MigrationAuthors.php');
    $res  = $authors->makeMigration('new authors');
    $this->assertEquals($exp, $res, 'Генерация миграции Authors');

    $exp  = file_get_contents($this->temp . '/MigrationBooks.php');
    $res  = $books->makeMigration('new books');
    $this->assertEquals($exp, $res, 'Генерация миграции Books');

    $exp  = file_get_contents($this->temp . '/MigrationTags.php');
    $res  = $tags->makeMigration('new tags');
    $this->assertEquals($exp, $res, 'Генерация миграции Tags');

    $exp  = file_get_contents($this->temp . '/MigrationBooksTags.php');
    $res  = $books_tags->makeMigration('new books tags');
    $this->assertEquals($exp, $res, 'Генерация миграции BooksTags');

    // Генерация модели

//    file_put_contents($this->temp . '/AuthorItem.php', $authors->makeItem());
//    file_put_contents($this->temp . '/BookItem.php', $books->makeItem());
//    file_put_contents($this->temp . '/TagItem.php', $tags->makeItem());

    $exp = file_get_contents($this->temp . '/AuthorItem.php');
    $this->assertEquals($exp, $authors->makeItem(), 'Author Item');

    $exp = file_get_contents($this->temp . '/BookItem.php');
    $this->assertEquals($exp, $books->makeItem(), 'Book Item');

    $exp = file_get_contents($this->temp . '/TagItem.php');
    $this->assertEquals($exp, $tags->makeItem(), 'Tag Item');
  }

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
      ->addBitmask('access', array('guest', 'owner', 'god'))
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
      ->addFloat('price')
      ->addForeignKey('parent_id', $s, 'id');

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

  function testNamesAndClass()
  {
    $m = $this->getManager('test_');

    $s = new Schema($m);

    $this->assertEquals('Schema', $s->getName(), 'По-умолчанию имя берется из класса');

    $s->setName('Users');

    $this->assertEquals('users', $s->getTable(), 'Таблица сгенерировалась из названия');
    $this->assertEquals('\User', $s->getItemClass(), 'Название сгенерировалось из названия схемы');
    $this->assertEquals('\User', $s->getItemClass(), 'Название без неймспейса');

    $s->setName('Schedule');
    $this->assertEquals('\Schedule', $s->getItemClass(), 'Нет s на конце');

    $s->setName('News');
    $s->setItemClass('\My\Good\News');
    $this->assertEquals('\My\Good\News', $s->getItemClass(), 'Явно заданное имя класса');
    $this->assertEquals('News', $s->getItemClass(false), 'Явно заданное имя класса без неймспейса');

    $s->setItemClass('News');
    $this->assertEquals('News', $s->getItemClass(false), 'Класс без неймспейсов');

    $s = new Schema($m, 'user_books');
    $this->assertEquals('UserBooks', $s->getName(), 'Название сгенерировалось из таблицы');
    $this->assertEquals('\UserBook', $s->getItemClass(), 'Класс сгенерился из таблицы');
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
      ->addBitmask('level', array('guest', 'owner', 'admin'))
      ->addInt('age')
      ->addChar('name')
      ->addFloat('price')
      ->addTime('created_at')
      ->addFile('pdf')
      ->addImage('avatar')
      ->addImage('photo', array('thumb', 'big'));

//    file_put_contents($this->temp . '/UsersRepository.php', $s->makeRepository());

    $exp = file_get_contents($this->temp . '/UsersRepository.php');
    $this->assertEquals($exp, $s->makeRepository(), 'Репозиторий');

//    file_put_contents($this->temp . '/UserItem.php', $s->makeItem());

    $exp = file_get_contents($this->temp . '/UserItem.php');
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

  protected function saveMigr(Schema $s, $name = 'my migration')
  {
    $this->last_migr++;

    file_put_contents(TEST_MIGR . '/' . $s->makeMigrationName($name, $this->last_migr), $s->makeMigration($name));
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
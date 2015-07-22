# DB

[![Build Status](https://travis-ci.org/sqrt-pro/DB.svg?branch=master)](https://travis-ci.org/sqrt-pro/DB)
[![Coverage Status](https://coveralls.io/repos/sqrt-pro/DB/badge.svg?branch=master)](https://coveralls.io/r/sqrt-pro/DB?branch=master)
[![Latest Stable Version](https://poser.pugx.org/sqrt-pro/db/v/stable.svg)](https://packagist.org/packages/sqrt-pro/db)
[![License](https://poser.pugx.org/sqrt-pro/db/license.svg)](https://packagist.org/packages/sqrt-pro/db)

Работа с базой возможна двумя способами - выполнение прямых запросов в БД, или работа с объектами.

**Терминология \ Используемые классы:**

* `Manager` - менеджер базы данных. Хранит подключения к БД, репозитории, и предоставляет возможность выполнять прямые запросы к БД.
* `Schema` - Описание структуры БД, по которой генерируются миграции, объекты Repository, Item.
* `Repository` - Объект представляющий таблицу в БД и позволяющий производить выборки объектов Item.
* `Collection` - Хранилище для объектов Item, реализующее интерфейс доступа к массиву, и набор методов для работы с коллекцией.
* `Item` - Объект представляющий запись в БД и содержащий бизнес-логику с ним связанную.

## Подключение к БД

Настройки подключения хранятся в объекте `Manager`, и задаются через метод:

~~~ php
$manager->addConnection($host, $user, $pass, $db_name, $db_charset = 'utf8', $connection_name = null);
~~~ 
    
Если не указывать имя подключения, ему будет присвоено имя default, которое будет использоваться по-умолчанию.
При желании, можно добавить несколько подключений, и обращаться к ним с указанием имени подключения.

~~~ php
$manager->getConnection($name = null); // Возвращает объект PDO
~~~
    
Для удобства разворачивания проекта в ограниченных условиях виртуального хостинга предусмотрена возможность задавать 
префикс для всех таблиц, используемых компонентом:
    
~~~ php
$manager->setPrefix($prefix);
~~~
    
Тогда все создаваемые таблицы и объекты их использующие, будут обращаться к таблицам с этим префиксом.

**Важно!** В запросах, формируемых пользователем, префикс нужно подставлять вручную!

## Прямая работа с БД

Из объекта Manager можно получить объект PDO и выполнять запросы напрямую, либо использовать методы для получения данных.

Все значения, подставляемые в запрос рекомендуется подставлять через PDO-плейсхолдеры вида `WHERE id = :id`, а сами значения 
передавать в аргументе $values.

В качестве запроса можно передавать строку, или объект Query, создаваемый [QueryBuilder`ом](https://github.com/sqrt-pro/QueryBuilder). 
В таком случае данные будут получены из объекта Query и подставлены в запрос автоматически.

~~~ php
// Выполнить запрос в БД, возвращает \PDOStatement
$manager->query($sql, $values = null, $connection = null)

// Получить все записи в виде списка ассоциативных массивов. Если указать $key, значения этого столбца будет ключами списка.
$manager->fetchAll($sql, $key = null, $values = null, $connection = null)

// Получить одну строку в виде ассоциативного массива
$manager->fetchOne($sql, $values = null, $connection = null)

// Получить одно значение из первой строки ответа. $col - имя столбца, или будет возвращено значение первого столбца в ответе.
$manager->fetchValue($sql, $col = null, $values = null, $connection = null)

// Выбрать один столбец и возвратить список значений этого столбца
$manager->fetchColumn($sql, $col = null, $values = null, $connection = null)

// Получить массив вида ключ => значение из запроса
$manager->fetchPair($sql, $values = null, $connection = null)

// Применить $callable ко всем результатам выборки по очереди.
// Первым аргументом будет передан массив содержащий текущую строку $callable($row)
$manager->each($sql, $callable, $values = null, $connection = null)
~~~ 

В целях отладки можно включить логирование всех выполняемых запросов:

~~~ php
$manager->setDebug($debug = true); // Включить отладку

$manager->getQueries(); // Получить список всех выполненных запросов, {query:..., values:..., time:...}
$manager->getQueriesCount(); // Количество запросов к БД
$manager->getQueriesTime(); // Суммарное время выполнения запросов к БД
~~~

## Схема

Схема содержит логическое представление полей в БД и их типов. По схеме генерируются файлы модели, а также можно 
автоматически создавать файлы миграции, в сравнении с текущим состоянием БД. 

Настройки схемы производятся через наследование базового класса схемы и переопределении методов `init()` и `relations()`

Возможные типы полей:

~~~ php
$schema->addInt($col, $default = 0, $signed = true, $length = 10)
$schema->addBool($col)
$schema->addChar($col, $length = 255)
$schema->addFloat($col, $length = 10, $decimals = 2, $signed = false)
$schema->addText($col, $size = false)
$schema->addTime($col, $unix = true)
$schema->addTimeCreated($col = 'created_at')
$schema->addTimeUpdated($col = 'updated_at')
~~~
    
**Важно!** Таблица может содержать только одно из полей `addTimeCreated` или `addTimeUpdated`, т.к. MySQL не позволяет 
создавать несколько полей с CURRENT_TIME. Остальные даты должны быть заданы с помощью обычного метода `addTime`
    
Кроме "обычных" полей, могут быть поля содержащие дополнительную логику, при генерации модели.
    
~~~ php
// Первичный ключ таблицы
$schema->addId($col = 'id')

// Поле INT и набор методов для работы с битовой маской
$schema->addBitmask($col, array $options, $default = 0) 

// Поле ENUM, содержащее выбор из нескольких вариантов
$schema->addEnum($col, array $options, $default = null) 

// Поле TEXT, содержащее сериализованный массив данных о файле
$schema->addFile($column)

// Поле TEXT, содержащее сериализованный массив данных о изображении
$schema->addImage($column, array $size_arr = null)
~~~

## Генерация базовых классов ORM

По схеме генерируется базовый класс модели, в котором создаются геттеры\сеттеры для всех полей, с учетом их типа, а также 
сопутствующие поля\методы\константы.

### Даты

* Сеттер поддерживает указание даты в любом формате, поддерживаемом функцией `strtotime()`
  ~~~ php
  $item->setCreatedAt('2015-01-01 12:45');
  $item->setCreatedAt('-7 days');
  ~~~

* Геттер поддерживает форматирование, принимаемом функцией date()
  ~~~
  $item->getCreatedAt(false, 'd.m.Y H:i');
  ~~~

### Float

* Геттер поддерживает форматирование в формате функции `number_format()`

  ~~~ php
  $item->getPrice(); // 12345.67
  $item->getPrice(false, 1, ',', ' '); // 12 345,7
  ~~~
  
### ENUM

Поле ENUM содержит список допустимых опций для поля. Например:

~~~ php
$schema->addEnum('status', array('new', 'progress', 'done'));
~~~

* Для всех опций сгенерируются константы вида `[column]_[value]`, например STATUS_NEW, STATUS_DONE.
* Будет сгенерирован массив имен для констант, который можно переопределить в наследующем классе и задать человекопонятные имена

  ~~~ php
  protected static $status_arr = array(
      self::STATUS_NEW => 'new',
      self::STATUS_PROGRESS => 'progress',
      self::STATUS_DONE => 'done',
    );
  ~~~
  
* При попытке передать в сеттер значение, которого нет в массиве имен, будет выброшено исключение
* Будут сгенерированы дополнительные методы:
    * Геттер вида `get[column]Name()`, который будет возвращать имя значения, содержащееся в массиве имен. 
    * Статический метод `Get[column]Arr()`, возвращающий массив имен
    * Статический метод `GetNameFor[column]($status)`, возвращающий имя для указанного значения

### Bitmask - битовая маска

Битовая маска позволяет указать несколько значений из списка и сохранить их в одном поле. 
Значения будут иметь числовое представление в виде степени двойки.
Основное отличие от ENUM в возможности сохранить сразу несколько значений одновременно.

~~~ php
$schema->addBitmask('status', array('payed', 'delivered', 'happy'))
~~~

* Для всех опций сгенерируются константы вида `[column]_[value]`, значение констант будет присвоено как 2 в степени ключ массива (0, 1, 2, ...).
  
  ~~~ php
  const STATUS_PAYED = 1;
  const STATUS_DELIVERED = 2;
  const STATUS_HAPPY = 4;
  ~~~
  
* Будет сгенерирован массив имен для констант, который можно переопределить в наследующем классе и задать человекопонятные имена
  
  ~~~ php
  protected static $status_arr = array(
      self::STATUS_PAYED => 'payed',
      self::STATUS_DELIVERED => 'delivered',
      self::STATUS_HAPPY => 'happy',
  );
  ~~~
  
* Сеттеры двух типов:
  
  ~~~ php
  $item->addStatus(Item::STATUS_PAYED);
  $item->setStatus(array(Item::STATUS_PAYED, Item::STATUS_HAPPY));
  ~~~
  
* Геттер возвращает массив:
  
  ~~~ php
  $item->getStatus(); // [1, 4]
  ~~~
  
* Проверка, установлен ли соответствующий флаг:
  
  ~~~ php
  $item->hasStatus(Item::STATUS_PAYED);
  ~~~

**Важно!** Не следует злоупотреблять битовой маской для хранения часто меняющихся данных. 
Для этого следует использовать связь многие-к-многим и внешние таблицы-справочники.

### File

Типовая задача сохранения информации о файле требует генерации нескольких полей и ручной обработки загрузки этого файла.
Зачастую при этом не требуется поиска или какой-либо обработки этих данных внутри БД, поэтому можно пойти на денормализацию
и упростить процесс.

~~~ php
$schema->addFile('pdf')
~~~

В таблице будет добавлено поле TEXT, в котором будет храниться сериализованный массив свойств файла.

* Сеттер производит запись информации о файле, а также выполняет копирование файла в директорию, указанную при 
  инициализации класса с помощью `setFilesPath()`
* Генерируются геттеры на все свойства файла вида get[column][property], например для `column = "pdf"`
    * `getPdf($default = false)` - относительный путь к файлу. Путь к папке на сервере задается через `setPublicPath()` при инициализации.
    * `getPdfPath($default = false)` - путь к файлу на сервере
    * `getPdfUrl($default = false)` - объект URL, ведущий на относительный путь к файлу
    * `getPdfSize($human = true)` - размер файла. Если $human == true, применяется форматирование
    * `getPdfName($default = false)` - название файла
    * `getPdfExtension($default = false)` - расширение файла

### Image

Типовая задача загрузки изображения требует сохранить изображение, иногда с изменением в размерах и\или наложением 
водяного знака, а также сохранением его в нескольких вариантах.

~~~ php
$schema->addImage('image', array('thumb', 'medium', 'orig');
~~~

В таблице будет добавлено поле TEXT, в котором будет храниться сериализованный массив свойств файла.

* Сеттер производит запись информации о файле, а также выполняет копирование файла в директорию, указанную при 
  инициализации класса с помощью `setFilesPath()`.
* При сохранении фото для каждого из размеров вызывается приватный метод `prepareImageFor[column]($file, $size)`, где 
  size - каждый из размеров, указанных в схеме. 
  Если этот метод возвращает объект `SQRT\Image`, изображение будет сохранено из него, иначе будет сохранено оригинальное изображение.  
* Генерируются геттеры на все свойства файла, аналогично типу File, но с добавлением размерности изображения, вида `get[column][size][property]`.
* Плюс создаются методы специфичные для изображений. Например для `column = "image", size = "thumb"`
    * `getImageThumbWidth($default = false)` - ширина изображения
    * `getImageThumbHeight($default = false)` - ширина изображения
    * `getImageThumbImg($alt = null, $attr = null, $default = false)` - генерация тега `Img` с подставленными значениями пути к файлу, ширины и высоты

## Индексы

Схема предусматривает создание индексов по одному или нескольким столбцам.

~~~ php
$schema->addIndex($column, $_ = null)
$schema->addUniqueIndex($column, $_ = null)
~~~

## Связи между таблицами

Для создания внешнего ключа в таблицах InnoDB можно воспользоваться методом:

~~~ php
$schema->addForeignKey($col, $schema, $foreign_id = null, $on_delete = null, $on_update = null)
~~~

Или создать одну из связей, при которой будут сгенерированы дополнительные функции в объекте модели, реализующие базовую 
логику данного типа связей.

Для каждой из сущностей можно указывать разные типы связей, так, предположим, для одной книги (Book) может быть один 
автор (Author), но при этом у автора может быть много книг. Т.е. связь `Book -> Author` один-к-одному, но 
`Author -> Book` уже один-к-многим. Соответственно каждая из связей и её тип указывается в своей схеме.  

**Важно!** Все таблицы, между которыми создаются связи должны быть InnoDB.

### Один-к-одному

~~~
addOneToOne($schema, $col = null, $foreign_id = null, $on_delete = null, $on_update = null, $name = null, $one = null)
~~~

Самый простой тип связей, когда ID внешнего объекта явно указан в одном из полей текущего объекта. 
Связь добавляет поле с именем `$col` INT UNSIGNED DEFAULT NULL в текущую таблицу.

**Например:** объект Book содержит поле author_id, которому соответствует один объект Author.  

~~~ php
$schema->addOneToOne('Authors', 'author_id', 'id', Schema::FK_RESTRICT, Schema::FK_RESTRICT)
~~~

Если соблюдать правила именования столбцов и таблиц, то можно оставлять значения по-умолчанию для большинства столбцов:

~~~ php
$schema->addOneToOne('Authors')
~~~
    
Когда на одну таблицу существует несколько связей, или нужно именование сущностей отличное от имени схемы, аргументы 
`$name` и `$one` позволяют указать произвольное именование для связи:

~~~ php
$schema->addOneToOne('Authors', 'author_id', 'id', Schema::FK_RESTRICT, Schema::FK_RESTRICT, 'MyAuthors', 'MyAuthor')
~~~
    
**Объект Book будет содержать методы:**

~~~ php
/** @return \Author */
public function getMyAuthor($reload = false)

/** @return static */
public function setMyAuthor(\Author $my_author)

/** @return \Author */
protected function findOneMyAuthor($id)
~~~

### Один-к-многим

~~~ php
addOneToMany($schema, $foreign_id = null, $col = null, $name = null, $one = null)
~~~

Связь позволяет выбирать несколько объектов, у которых связь на текущий объект указана в одном из полей. 
В текущую таблицу в БД изменений не вносится.

**Например:** Объект Author имеет связь с несколькими Book, у которых зависимость указана в поле author_id.

~~~ php
$schema->addOneToMany('Books', 'book_id', 'id')
~~~
    
Когда на одну таблицу существует несколько связей, или нужно именование сущностей отличное от имени схемы, аргументы 
`$name` и `$one` позволяют указать произвольное именование для связи.

~~~ php
$schema->addOneToMany('Books', null, null, 'MyBooks', 'MyBook')
~~~
    
**Объект Author будет содержать методы:**

~~~ php
/** @return Collection|\Book[] */
public function getMyBooks($reload = false)

/** @return static */
public function setMyBooks($my_books_arr = null)

/** @return Collection|\Book[] */
protected function findMyBooks()
~~~
    
### Многие-к-многим

~~~ php
addManyToMany($schema, $join_table = null, $foreign_col = null, $my_col = null, $foreign_id = null, $my_id = null, $name = null, $one = null)
~~~

Связь двух таблиц через третью. 

**Например:** У книги может быть несколько авторов (Author), у автора может быть несколько книг (Book). 
Для этого создаем таблицу AuthorBook, содержащюю поля `book_id` и `author_id`, и получаем связь авторов и книг через JOIN к этой таблице.

~~~ php
// Схема AuthorBook
$schema->addOneToOne('Authors') // author_id
$schema->addOneToOne('Books') // book_id

// В схеме Authors:
addManyToMany('Books', 'author_book', 'book_id', 'author_id', 'id', 'id')

// В схеме Books:
addManyToMany('Authors', 'author_book', 'author_id', 'book_id', 'id', 'id')
~~~

**Объект Author будет содержать методы:**

~~~
/** @return Collection|\Book[] */
public function getBooks($reload = false)

/** @return static */
public function addBook($book)

/** @return static */
public function removeBook($book)

/** @return static */
public function removeAllBooks()

/** @param $book integer|\Book */ 
protected function getBookPK($book)

/** @return Collection|\Book[] */
protected function findBooks()
~~~

## Миграции

На основе схемы и текущего состояния БД можно сгенерировать файлы миграций для [менеджера миграций Phinx](https://phinx.org).

Если таблицы в базе еще не существует, генерируется миграция создающая эту таблицу и все столбцы\индексы в ней.

**Пример создания новой таблицы Books:**

~~~ php
class NewBooksTable extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_books', array('id' => 'id'));
    $tbl->addColumn("name", "string", array ( 'length' => 255, 'null' => true,));
    $tbl->addColumn("author_id", "integer", array ( 'length' => 11, 'signed' => true, 'null' => true,));
    $tbl->addForeignKey("author_id", "test_authors", "id", array (  'delete' => 'RESTRICT',  'update' => 'CASCADE',));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_books', array('id' => 'id'));
    $tbl->drop();
  }
}
~~~
    
Если таблица в базе уже существует, в миграции генерируется добавление и удаление столбцов, отличающихся в схеме и БД, 
для столбцов, которые ранее были созданы и присутствуют в БД генерируются методы `changeColumn`.

**Пример изменения существующей таблицы Pages:**

~~~ php
class MyMigration extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_pages', array('id' => 'id'));
    $tbl->addColumn("is_active", "boolean", array ( 'default' => 0,));
    $tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    $tbl->removeColumn("name");
    $tbl->changeColumn("created_at", "timestamp", array ( 'default' => 'CURRENT_TIMESTAMP',));
    if (!$tbl->hasForeignKey("parent_id")) {
      $tbl->addForeignKey("parent_id", "test_pages", "id", array ());
    }
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_pages', array('id' => 'id'));
    $tbl->removeColumn("is_active");
    $tbl->removeColumn("price");
    // TODO: добавить инструкции для создания столбца name
    $tbl->save();
  }
}
~~~

**Важно!** Сгенерированную миграцию необходимо воспринимать как черновик, который необходимо проверить и скорректировать 
при необходимости. Не накатывайте миграцию вслепую!
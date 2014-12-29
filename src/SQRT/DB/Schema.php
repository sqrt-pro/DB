<?php

namespace SQRT\DB;

use Stringy\StaticStringy;

class Schema
{
  /** Тип таблиц MyISAM принят по умолчанию в MySQL */
  const TYPE_MYISAM = 'MyISAM';
  /** Таблицы с поддержкой транзакций и блокировкой строк. */
  const TYPE_INNODB = 'InnoDB';
  /** Данные для этой таблицы хранятся только в памяти. */
  const TYPE_HEAP = 'HEAP';

  const RELATION_ONE_TO_ONE   = 'one_to_one';
  const RELATION_ONE_TO_MANY  = 'one_to_many';
  const RELATION_MANY_TO_MANY = 'many_to_many';

  const INDEX_INDEX    = 'index';
  const INDEX_UNIQUE   = 'unique';
  const INDEX_FULLTEXT = 'fulltext';

  const COL_INT       = 'integer';
  const COL_CHAR      = 'string';
  const COL_ENUM      = 'enum';
  const COL_TIMESTAMP = 'timestamp';
  const COL_DATETIME  = 'datetime';
  const COL_BOOL      = 'boolean';
  const COL_FLOAT     = 'float';
  const COL_TEXT      = 'text';
  const COL_FILE      = 'file';
  const COL_IMAGE     = 'image';

  const FK_SET_NULL = 'SET NULL';
  const FK_CASCADE  = 'CASCADE';
  const FK_RESTRICT = 'RESTRICT';

  const TINYTEXT   = 255;
  const TEXT       = 65535;
  const MEDIUMTEXT = 16777215;
  const LONGTEXT   = 16777216;

  /** @var Manager */
  protected $manager;
  protected $table;
  protected $name;
  protected $type = self::TYPE_INNODB;
  protected $primary_key;

  protected $item_class;
  protected $item_base_class       = '\Base\Item';
  protected $collection_base_class = '\Base\Collection';

  protected $indexes;
  protected $columns;
  protected $relations;
  protected $foreign_keys;
  protected $actual_columns;

  function __construct(Manager $manager, $table = null, $name = null, $no_relations = false)
  {
    $this->setManager($manager);

    if ($table) {
      $this->setTable($table);
    }

    if ($name) {
      $this->setName($name);
    }

    $this->init();

    if (!$no_relations) {
      $this->relations();
    }
  }

  /** Заполнение таблицы данными по-умолчанию */
  public function fixture()
  {

  }

  /**
   * Столбец таблицы
   *
   * $type - string, text, integer, biginteger, float, decimal, datetime, timestamp, time, date, binary, boolean,
   * $definition - список доп.опций: limit, length, default, null, precision, scale, after, update, comment
   * Свойства default и update могут принимать ‘CURRENT_TIMESTAMP’ как значение
   */
  public function add($column, $type, $definition = null, $options = null)
  {
    $this->columns[$column] = array(
      'column'     => $column,
      'type'       => $type,
      'definition' => $definition,
      'options'    => $options
    );

    return $this;
  }

  /** Столбец таблицы */
  public function get($column)
  {
    return isset($this->columns[$column]) ? $this->columns[$column] : false;
  }

  /** Добавление ID. Автоматически проставляет первичный ключ */
  public function addId($col = 'id')
  {
    return $this->setPrimaryKey($col);
  }

  public function addInt($col, $default = 0, $signed = true, $length = 10)
  {
    $opts = array('length' => $length, 'signed' => $signed);
    if (!is_null($default)) {
      $opts['default'] = $default;
    } else {
      $opts['null'] = true;
    }

    return $this->add($col, static::COL_INT, $opts);
  }

  public function addBool($col)
  {
    return $this->add($col, static::COL_BOOL, array('default' => 0));
  }

  public function addChar($col, $length = 255)
  {
    return $this->add($col, static::COL_CHAR, array('length' => $length, 'null' => true));
  }

  public function addEnum($col, $options, $default = null)
  {
    $def = array('null' => true, 'length' => 255);
    if ($default) {
      $def['default'] = $default;
    }

    return $this->add($col, static::COL_ENUM, $def, $options);
  }

  public function addFloat($col, $length = 10, $decimals = 2, $signed = false)
  {
    return $this->add(
      $col,
      static::COL_FLOAT,
      array('precision' => $length, 'scale' => $decimals, 'signed' => $signed, 'default' => 0)
    );
  }

  public function addTime($col, $unix = true)
  {
    return $this->add($col, ($unix ? static::COL_TIMESTAMP : static::COL_DATETIME), array('null' => true));
  }

  public function addTimeCreated($col = 'created_at')
  {
    return $this->add($col, static::COL_TIMESTAMP, array('default' => 'CURRENT_TIMESTAMP'));
  }

  public function addTimeUpdated($col = 'updated_at')
  {
    return $this->add(
      $col,
      static::COL_TIMESTAMP,
      array('default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP')
    );
  }

  /**
   * Текстовое поле
   * $size - размерность поля или true для longtext
   */
  public function addText($col, $size = false)
  {
    $opts = array('null' => true);
    if ($size) {
      $opts['limit'] = $size === true ? static::LONGTEXT : $size;
    }

    return $this->add($col, static::COL_TEXT, $opts);
  }

  public function addForeignKey($col, $schema, $foreign_id = null, $on_delete = null, $on_update = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema, true);

    if (!$foreign_id = $foreign_id ?: $s->getPrimaryKey()) {
      Exception::ThrowError(Exception::PK_NOT_SET, $s->getName());
    }

    $this->foreign_keys[$col] = array(
      'column'     => $col,
      'schema'     => $s->getName(),
      'table'      => $s->getTable(),
      'foreign_id' => $foreign_id,
      'on_delete'  => $on_delete,
      'on_update'  => $on_update
    );

    return $this;
  }

  public function addIndex($column, $_ = null)
  {
    $arr  = func_get_args();
    $name = 'i_' . join('_', $arr);

    $this->indexes[$name] = array(
      'columns' => $arr,
      'name'    => $name,
      'type'    => static::INDEX_INDEX
    );

    return $this;
  }

  public function addUniqueIndex($column, $_ = null)
  {
    $arr  = func_get_args();
    $name = 'u_' . join('_', $arr);

    $this->indexes[$name] = array(
      'columns' => $arr,
      'name'    => $name,
      'type'    => static::INDEX_UNIQUE
    );

    return $this;
  }

  /** Добавить поле для хранения файлов */
  public function addFile($column)
  {
    return $this->add($column, static::COL_FILE, array('null' => true));
  }

  /** Добавить поле для хранения изображений */
  public function addImage($column, array $size_arr = null)
  {
    return $this->add($column, static::COL_IMAGE, array('null' => true), $size_arr);
  }

  /**
   * Связь один-к-одному
   * $col - столбец в таблице текущего объекта
   * $foreign_id - столбец в таблице получаемого объекта
   */
  public function addOneToOne($schema, $col = null, $foreign_id = null, $on_delete = null, $on_update = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema, true);
    $t = $s->getTable();

    if (!$foreign_id = $foreign_id ?: $s->getPrimaryKey()) {
      Exception::ThrowError(Exception::PK_NOT_SET, $s->getName());
    }

    $col = $col ?: StaticStringy::underscored($s->getItemClass(false) . '_id');

    $this->addInt($col, NULL, true, 11);
    $this->addForeignKey($col, $s, $foreign_id, $on_delete, $on_update);

    $this->relations[$t] = array(
      'type'       => static::RELATION_ONE_TO_ONE,
      'column'     => $col,
      'schema'     => $s,
      'foreign_id' => $foreign_id,
    );

    return $this;
  }

  /**
   * Связь многие-к-многим через внешнюю таблицу
   * Если внешняя таблица $join_table не указана - название формируется из названий двух таблиц
   * $foreign_col - столбец запрашиваемого объекта в объединяющей таблице
   * $my_col - столбец текущего объекта в объединяющей таблице
   * $foreign_id - столбец Primary Key в таблице запрашиваемого объекта
   * $my_id - столбец Primary Key в таблице текущего объекта
   */
  public function addManyToMany($schema, $join_table = null, $foreign_col = null, $my_col = null, $foreign_id = null, $my_id = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema, true);
    $t = $s->getTable();

    if (!$foreign_id = $foreign_id ?: $s->getPrimaryKey()) {
      Exception::ThrowError(Exception::PK_NOT_SET, $s->getName());
    }

    if ($join_table instanceof Schema) {
      $join_table = $join_table->getTable();
    }

    if (!$join_table) {
      $arr = array($this->getName(), $s->getName());
      asort($arr);
      $join_table = StaticStringy::underscored(join(' ', $arr));
    }

    $this->relations[$t] = array(
      'type'        => static::RELATION_MANY_TO_MANY,
      'column'      => $my_col ?: StaticStringy::underscored($this->getItemClass(false) . '_id'),
      'schema'      => $s,
      'foreign_id'  => $foreign_id,
      'foreign_col' => $foreign_col ?: StaticStringy::underscored($s->getItemClass(false) . '_id'),
      'my_id'       => $my_id ?: $this->getPrimaryKey(),
      'table'       => $join_table,
    );

    return $this;
  }

  /**
   * Связь один-к-многим.
   * $foreign_id - столбец в таблице запрашиваемого объекта
   * $col - столбец в таблице текущего объекта
   */
  public function addOneToMany($schema, $foreign_id = null, $col = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema, true);
    $t = $s->getTable();

    $this->relations[$t] = array(
      'type'       => static::RELATION_ONE_TO_MANY,
      'column'     => $col ?: $this->getPrimaryKey(),
      'schema'     => $s,
      'foreign_id' => $foreign_id ?: StaticStringy::underscored($this->getItemClass(false) . '_id'),
    );

    return $this;
  }

  /** @return Manager */
  public function getManager()
  {
    return $this->manager;
  }

  /** @return static */
  public function setManager(Manager $manager)
  {
    $this->manager = $manager;

    return $this;
  }

  /** Тип таблицы. Одна из констант TYPE_* */
  public function getType()
  {
    return $this->type;
  }

  /** Тип таблицы. Одна из констант TYPE_* */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /** Базовый класс, от которого наследуется Item */
  public function getItemBaseClass()
  {
    return $this->item_base_class;
  }

  /** Базовый класс, от которого наследуется Item */
  public function setItemBaseClass($item_base_class)
  {
    $this->item_base_class = $item_base_class;

    return $this;
  }

  /** Базовый класс, от которого наследуются Collection */
  public function getCollectionBaseClass()
  {
    return $this->collection_base_class;
  }

  /**
   * Базовый класс, от которого наследуются Collection
   * @return static
   */
  public function setCollectionBaseClass($collection_base_class)
  {
    $this->collection_base_class = $collection_base_class;

    return $this;
  }

  /** Первичный ключ */
  public function getPrimaryKey()
  {
    return $this->primary_key;
  }

  /** Первичный ключ */
  public function setPrimaryKey($primary_key)
  {
    $this->primary_key = $primary_key;

    return $this;
  }

  /** Имя таблицы */
  public function getTable()
  {
    return $this->table ?: StaticStringy::underscored($this->name);
  }

  /** Имя таблицы */
  public function setTable($table)
  {
    $this->table = $table;

    return $this;
  }

  /** Название схемы */
  public function getName()
  {
    return $this->name ?: StaticStringy::upperCamelize($this->table);
  }

  /** Название схемы */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /** Класс для Item */
  public function getItemClass($with_namespace = true)
  {
    $cl = $this->item_class ?: '\\' . StaticStringy::removeRight($this->getName(), 's');

    return $with_namespace ? $cl : array_pop(explode('\\', $cl));
  }

  /**
   * Класс для Item
   * @return static
   */
  public function setItemClass($item_class)
  {
    $this->item_class = $item_class;

    return $this;
  }

  /** Создание имени для миграции */
  public function makeMigrationName($name, $num = null)
  {
    return ($num ?: date('YmdHis')) . '_' . StaticStringy::slugify($name, '_') . '.php';
  }

  /** Генерация миграции */
  public function makeMigration($name)
  {
    $m = $this->getManager();
    $t = $m->getPrefix() . $this->getTable();

    $class = StaticStringy::upperCamelize($name);

    $head = "<?php\n\n"
      . "use Phinx\\Migration\\AbstractMigration;\n\n"
      . "class $class extends AbstractMigration\n"
      . "{\n";

    $pk       = $this->getPrimaryKey();
    $tbl_opts = 'array(\'id\' => ' . ($pk ? "'$pk'" : 'false') . ')';

    $tbl  = '    $tbl = $this->table(\'' . $t . '\', ' . $tbl_opts . ');' . "\n";
    $save = '    $tbl->save();' . "\n";
    $up   = $down = false;

    $drop = $upd = false;

    $tbl_exists = $this->checkTableExists();
    if ($tbl_exists) {
      $columns = $this->getActualColumns(true);

      $drop = array_diff($columns, array_keys($this->columns));
      $add  = array_diff(array_keys($this->columns), $columns);
    } else {
      $down = '    $tbl->drop();' . "\n";
      $add  = array_keys($this->columns);
    }

    if (!empty($add)) {
      foreach ($add as $col) {
        $up .= $this->makeAddExpr($col);
        if ($tbl_exists) {
          $down .= $this->makeDropExpr($col);
        }
      }
    }

    if (!empty($drop)) {
      foreach ($drop as $col) {
        if ($col != $this->getPrimaryKey()) {
          $up .= $this->makeDropExpr($col);
          $down .= "    // TODO: добавить инструкции для создания столбца $col\n";
        }
      }
    }

    foreach ($this->columns as $col => $arr) {
      if (empty($add) || !in_array($col, $add)) {
        $up .= $this->makeAddExpr($col, true);
      }
    }

    if (!empty($this->indexes)) {
      foreach ($this->indexes as $a) {
        $up .= $this->makeAddIndex($a, $tbl_exists);
      }
    }

    if (!empty($this->foreign_keys)) {
      foreach ($this->foreign_keys as $arr) {
        $up .= $this->makeFK($arr, $tbl_exists);
      }
    }

    return $head
    . "  public function up()\n"
    . "  {\n" . $tbl . $up . $save
    . "  }\n\n"
    . "  public function down()\n"
    . "  {\n" . $tbl . $down . ($tbl_exists ? $save : '')
    . "  }\n"
    . "}\n";
  }

  /** Генерация Item */
  public function makeItem($namespace = 'ORM')
  {
    $before = $after = $func = '';
    $fields_arr = array_keys($this->columns);

    // Если первичного ключа нет в списке полей
    if ($pk = $this->getPrimaryKey()) {
      if (!isset($this->columns[$pk])) {
        array_unshift($fields_arr, $pk);
        $func[] = $this->makeItemGetter($pk);
        $func[] = $this->makeItemSetter($pk);
      }
    }

    // Геттеры\Сеттеры
    foreach ($this->columns as $col => $def) {
      switch ($def['type']) {
        case self::COL_BOOL:
        case self::COL_INT:
          $this->makeItemInt($def, $func, $before, $after);
          break;

        case self::COL_ENUM:
          $this->makeItemEnum($def, $func, $before, $after);
          break;

        case self::COL_TIMESTAMP:
        case self::COL_DATETIME:
          $this->makeItemTime($def, $func, $before, $after);
          break;

        case self::COL_FLOAT:
          $this->makeItemFloat($def, $func, $before, $after);
          break;

        case self::COL_FILE:
          $this->makeItemFile($def, $func, $before, $after);
          break;

        case self::COL_IMAGE:
          $this->makeItemImage($def, $func, $before, $after);
          break;

        default:
          $this->makeItemChar($def, $func, $before, $after);
      }
    }

    // Связи
    if (!empty($this->relations)) {
      foreach ($this->relations as $rel) {
        switch ($rel['type']) {
          case static::RELATION_ONE_TO_ONE:
            $this->makeItemOneToOne($rel, $func, $before, $after);
            break;

          case static::RELATION_ONE_TO_MANY:
            $this->makeItemOneToMany($rel, $func, $before, $after);
            break;

          case static::RELATION_MANY_TO_MANY:
            $this->makeItemManyToMany($rel, $func, $before, $after);
            break;
        }
      }
    }

    // Генерация кода класса
    $str = "<?php\n\nnamespace $namespace;\n\n"
      . "use SQRT\\DB\\Exception;\n\n"
      . "/** Этот файл сгенерирован автоматически по схеме {$this->getName()} */\n"
      . "abstract class " . $this->getItemClass(false) . " extends " . $this->getItemBaseClass() . "\n"
      . "{\n"
      . ($before ? join("\n\n", $before) . "\n\n" : '')
      . "  protected function init()\n"
      . "  {\n"
      . "    \$this->setPrimaryKey(" . var_export($pk, true) . ");\n"
      . "    \$this->setTable('" . $this->getTable() . "');\n"
      . "    \$this->setFields(\n"
      . "      array(\n"
      . "        '" . join("',\n        '", $fields_arr) . "',\n"
      . "      )\n"
      . "    );\n"
      . "  }\n\n"
      . ($func ? join("\n\n", $func) . "\n" : '')
      . ($after ? "\n" . join("\n\n", $after) . "\n" : '')
      . "}\n";

    return $str;
  }

  protected function makeItemOneToOne($relation, &$func, &$before, &$after)
  {
    /** @var $schema Schema */
    $schema = $relation['schema'];
    $col    = $relation['column'];
    $fk     = $relation['foreign_id'];

    $schema_name = $schema->getName();
    $item        = $schema->getItemClass();
    $name        = StaticStringy::underscored($schema->getItemClass(false));
    $getter      = StaticStringy::camelize('get ' . $name);
    $setter      = StaticStringy::camelize('set ' . $name);

    $before[] = "  /** @var {$item} */\n"
      . "  protected \${$name};";

    $func[] = "  /** @return {$item} */\n"
      . "  public function {$getter}(\$reload = false)\n"
      . "  {\n"
      . "    if (!\$id = \$this->get('{$col}')) {\n"
      . "      return false;\n"
      . "    }\n\n"
      . "    if (is_null(\$this->{$name}) || \$reload) {\n"
      . "      \$c = \$this->getManager()->getCollection('{$schema_name}');\n\n"
      . "      \$this->{$name} = \$c->findOne(array('{$fk}' => \$id));\n"
      . "    }\n\n"
      . "    return \$this->{$name};\n"
      . "  }";
    $func[] = "  /** @return static */\n"
      . "  public function {$setter}({$item} \${$name})\n"
      . "  {\n"
      . "    \$this->{$name} = \${$name};\n\n"
      . "    return \$this->set('{$col}', \${$name}->get('{$fk}'));\n"
      . "  }";
  }

  protected function makeItemOneToMany($relation, &$func, &$before, &$after)
  {
    /** @var $schema Schema */
    $schema = $relation['schema'];
    $col    = $relation['column'];
    $fk     = $relation['foreign_id'];

    $schema_name = $schema->getName();
    $item        = $schema->getItemClass();
    $name        = StaticStringy::underscored($schema_name);
    $var         = $name . '_arr';
    $getter      = StaticStringy::camelize('get ' . $name);
    $setter      = StaticStringy::camelize('set ' . $name);

    $before[] = "  /** @var \\SQRT\\DB\\Collection|{$item}[] */\n"
      . "  protected \${$var};";

    $func[] = "  /** @return \\SQRT\\DB\\Collection|{$item}[] */\n"
      . "  public function {$getter}(\$reload = false)\n"
      . "  {\n"
      . "    \$c = \$this->getManager()->getCollection('{$schema_name}');\n\n"
      . "    if (is_null(\$this->{$var}) || \$reload) {\n"
      . "      \$this->{$var} = \$c->find(array('{$fk}' => \$this->get('{$col}')))->getIterator(true);\n"
      . "    }\n\n"
      . "    return \$c->setItems(\$this->{$var});\n"
      . "  }";

    $func[] = "  /** @return static */\n"
      . "  public function {$setter}(\${$var} = null)\n"
      . "  {\n"
      . "    \$this->{$var} = \${$var};\n\n"
      . "    return \$this;\n"
      . "  }";
  }

  protected function makeItemManyToMany($relation, &$func, &$before, &$after)
  {
    /** @var $schema Schema */
    $schema = $relation['schema'];
    $my_col = $relation['column'];
    $my_id  = $relation['my_id'];
    $table  = $relation['table'];

    $foreign_id  = $relation['foreign_id'];
    $foreign_col = $relation['foreign_col'];

    $schema_name = $schema->getName();
    $schema_tbl  = $schema->getTable();
    $item        = $schema->getItemClass();
    $name        = StaticStringy::underscored($schema_name);
    $one         = StaticStringy::underscored($schema->getItemClass(false));
    $var         = $name . '_arr';
    $getter      = StaticStringy::camelize('get ' . $name);
    $setter      = StaticStringy::camelize('set ' . $name);
    $adder       = StaticStringy::camelize('add ' . $one);
    $remover     = StaticStringy::camelize('remove ' . $one);
    $all_remover = StaticStringy::camelize('remove all ' . $schema_name);

    $before[] = "  /** @var \\SQRT\\DB\\Collection|{$item}[] */\n"
      . "  protected \${$var};";

    $before[] = "  protected \$tbl_{$name} = '{$table}';";

    $func[] = "  /** @return \\SQRT\\DB\\Collection|{$item}[] */\n"
      . "  public function {$getter}(\$reload = false)\n"
      . "  {\n"
      . "    \$m = \$this->getManager();\n"
      . "    \$c = \$m->getCollection('{$schema_name}');\n\n"
      . "    if (is_null(\$this->{$var}) || \$reload) {\n"
      . "      \$q = \$m->getQueryBuilder()\n"
      . "        ->select('{$schema_tbl} t')\n"
      . "        ->columns('t.*')\n"
      . "        ->join(\$this->tbl_{$name} . ' j', 't.{$foreign_id} = j.{$foreign_col}')\n"
      . "        ->where(array('j.{$my_col}' => \$this->get('{$my_id}')));\n"
      . "      \n"
      . "      \$this->{$var} = \$c->fetch(\$q)->getIterator(true);\n"
      . "    }\n\n"
      . "    return \$c->setItems(\$this->{$var});\n"
      . "  }";

    $func[] = "  public function {$adder}(\${$one})\n"
      . "  {\n"
      . "    \$id = \${$one} instanceof {$item} ? \${$one}->get('{$foreign_id}') : \${$one};\n\n"
      . "    \$m = \$this->getManager();\n"
      . "    \$q = \$m->getQueryBuilder()\n"
      . "      ->insert(\$this->tbl_{$name})\n"
      . "      ->setEqual('{$foreign_col}', \$id)\n"
      . "      ->setEqual('{$my_col}', \$this->get('{$my_id}'));\n"
      . "    \$m->query(\$q);\n\n"
      . "    return \$this;\n"
      . "  }";

    $func[] = "  public function {$remover}(\${$one})\n"
      . "  {\n"
      . "    \$id = \${$one} instanceof {$item} ? \${$one}->get('{$foreign_id}') : \${$one};\n\n"
      . "    \$m = \$this->getManager();\n"
      . "    \$q = \$m->getQueryBuilder()\n"
      . "      ->delete(\$this->tbl_{$name})\n"
      . "      ->where(array('{$foreign_col}' => \$id, '{$my_col}' => \$this->get('{$my_id}')));\n"
      . "    \$m->query(\$q);\n\n"
      . "    return \$this;\n"
      . "  }";

    $func[] = "  public function {$all_remover}()\n"
      . "  {\n"
      . "    \$m = \$this->getManager();\n"
      . "    \$q = \$m->getQueryBuilder()\n"
      . "      ->delete(\$this->tbl_{$name})\n"
      . "      ->where(array('{$my_col}' => \$this->get('{$my_id}')));\n"
      . "    \$m->query(\$q);\n\n"
      . "    return \$this;\n"
      . "  }";
  }

  /** Генерация коллекции */
  public function makeCollection($namespace = 'Collection')
  {
    $class = $this->getItemClass();

    return "<?php\n\nnamespace $namespace;\n\n"
    . "/**\n"
    . ' * Этот файл сгенерирован автоматически по схеме ' . $this->getName() . "\n"
    . " *\n"
    . ' * @method ' . $class . ' findOne($where = null) Найти и получить один объект' . "\n"
    . ' * @method ' . $class . ' make() Создать новый объект' . "\n"
    . ' * @method ' . $class . ' fetchObject(\PDOStatement $statement) Получение объекта из запроса' . "\n"
    . "*/\n"
    . "class " . $this->getName() . " extends " . $this->getCollectionBaseClass() . "\n"
    . "{\n"
    . "  protected function init()\n"
    . "  {\n"
    . "    \$this->setItemClass('$class');\n"
    . "    \$this->setTable('" . $this->getTable() . "');\n"
    . "  }\n"
    . "}\n";
  }

  protected function makeItemChar($def, &$func, &$before, &$after)
  {
    $col = $def['column'];

    $func[] = $this->makeItemGetter($col);
    $func[] = $this->makeItemSetter($col);
  }

  protected function makeItemFile($def, &$func, &$before, &$after)
  {
    $col    = $def['column'];
    $setter = Item::MakeSetterName($col);

    $this->makeItemFileGetters($func, $col);

    $func[] = "  /** Добавить файл \$file. \$name - оригинальное имя файла */\n"
      . "  public function {$setter}(\$file, \$name = null)\n"
      . "  {\n"
      . "    \$name     = \$name ?: pathinfo(\$file, PATHINFO_BASENAME);\n"
      . "    \$filename = \$this->makeFileName('{$col}', \$name);\n\n"
      . "    return \$this->processFile('{$col}', \$file, \$filename, \$name);\n"
      . "  }";
  }

  protected function makeItemImage($def, &$func, &$before, &$after)
  {
    $col      = $def['column'];
    $size_arr = $def['options'];
    $arr      = $size_arr ?: array(false);
    $setter   = Item::MakeSetterName($col);

    $process = false;
    foreach ($arr as $size) {
      $this->makeItemFileGetters($func, $col, true, $size);
      $s = var_export($size, true);
      $process .= "    \$filename = \$this->makeFileName('{$col}', \$name, {$s});\n";
      $process .= "    \$this->processImage('{$col}', \$tmp, \$filename, \$name, {$s});\n\n";
    }

    $func[] = "  /** Добавить изображение \$file. \$name - оригинальное имя файла */\n"
      . "  public function {$setter}(\$file, \$name = null)\n"
      . "  {\n"
      . "    \$name = \$name ?: pathinfo(\$file, PATHINFO_BASENAME);\n"
      . "    \$tmp  = \$this->getFilesPath() . \$this->makeFileName('{$col}', \$name, 'temp');\n\n"
      . "    \$this->copyFile(\$file, \$tmp);\n\n"
      . $process
      . "    unlink(\$tmp);\n\n"
      . "    return \$this;\n"
      . "  }";

    $after[] = "  /**\n"
      . "  * Метод для процессинга изображений {$col}.\n"
      . "  * Должен вернуть объект Image или файл будет сохранен без изменений\n  */\n"
      . "  protected function prepareImageFor" . StaticStringy::upperCamelize($col) . "(\$file, \$size)\n"
      . "  {\n"
      . "    \n"
      . "  }";

    if (!empty($size_arr)) {
      $const = $names = false;
      foreach ($size_arr as $size) {
        $c = StaticStringy::toUpperCase(StaticStringy::underscored($col.' size '.$size));
        $const[] = "  const {$c} = '{$size}';";
        $names[] = "    self::{$c} => '{$size}',";
      }

      $before[] = join("\n", $const);
      $before[] = "  protected static \${$col}_size_arr = array(\n" . join("\n", $names) . "\n  );";

      $name_for = 'GetNameFor' . StaticStringy::upperCamelize($col) . 'Size';
      $getter_arr = 'Get' . StaticStringy::upperCamelize($col) . 'SizeArr';

      $after[] = "  public static function {$getter_arr}()\n"
        . "  {\n"
        . "    return static::\${$col}_size_arr;\n"
        . "  }";
      $after[] = "  public static function {$name_for}(\$size)\n"
        . "  {\n"
        . "    \$a = static::{$getter_arr}();\n\n"
        . "    return isset(\$a[\$size]) ? \$a[\$size] : false;\n"
        . "  }";
    }
  }

  protected function makeItemFileGetters(&$func, $col, $is_image = false, $size = null)
  {
    $getter = Item::MakeGetterName($col);
    if ($size) {
      $getter .= StaticStringy::upperCamelize($size);
      $func[] = "  /** Свойство файла */\n"
        . "  public function {$getter}Param(\$param, \$default = false)\n"
        . "  {\n"
        . "    \$arr = \$this->getSerialized('{$col}');\n\n"
        . "    return isset(\$arr['{$size}'][\$param]) ? \$arr['{$size}'][\$param] : \$default;\n"
        . "  }";
    } else {
      $func[] = "  /** Свойство файла */\n"
        . "  public function {$getter}Param(\$param, \$default = false)\n"
        . "  {\n"
        . "    \$arr = \$this->getSerialized('{$col}');\n\n"
        . "    return isset(\$arr[\$param]) ? \$arr[\$param] : \$default;\n"
        . "  }";
    }

    $func[] = "  /** Публичный путь к файлу */\n"
      . "  public function {$getter}(\$default = false)\n"
      . "  {\n"
      . "    \$f = \$this->{$getter}Param('file');\n\n"
      . "    return !empty(\$f) ? \$this->getPublicPath() . \$f : \$default;\n"
      . "  }";
    $func[] = "  /** Путь к файлу на сервере */\n"
      . "  public function {$getter}Path(\$default = false)\n"
      . "  {\n"
      . "    \$f = \$this->{$getter}Param('file');\n\n"
      . "    return !empty(\$f) ? \$this->getFilesPath() . \$f : \$default;\n"
      . "  }";
    $func[] = "  /** @return \\SQRT\\URL */\n"
      . "  public function {$getter}Url(\$default = false)\n"
      . "  {\n"
      . "    \$f = \$this->{$getter}();\n\n"
      . "    return \$f ? new \\SQRT\\URL(\$f) : \$default;\n"
      . "  }";
    $func[] = "  /** Размер файла. \$human - человеческое представление */\n"
      . "  public function {$getter}Size(\$human = true)\n"
      . "  {\n"
      . "    if (!\$size = \$this->{$getter}Param('file')) {\n"
      . "      return false;\n"
      . "    }\n\n"
      . "    return \$human ? \$this->getHumanFileSize(\$size) : \$size;\n"
      . "  }";
    $func[] = "  /** Название файла */\n"
      . "  public function {$getter}Name(\$default = false)\n"
      . "  {\n"
      . "    return \$this->{$getter}Param('name', \$default);\n"
      . "  }";
    $func[] = "  /** Расширение файла */\n"
      . "  public function {$getter}Extension(\$default = false)\n"
      . "  {\n"
      . "    return \$this->{$getter}Param('extension', \$default);\n"
      . "  }";

    if ($is_image) {
      $func[] = "  /** Ширина изображения */\n"
        . "  public function {$getter}Width(\$default = false)\n"
        . "  {\n"
        . "    return \$this->{$getter}Param('width', \$default);\n"
        . "  }";
      $func[] = "  /** Высота изображения */\n"
        . "  public function {$getter}Height(\$default = false)\n"
        . "  {\n"
        . "    return \$this->{$getter}Param('height', \$default);\n"
        . "  }";
      $func[] = "  /** @return \\SQRT\\Tag\\Img */\n"
        . "  public function {$getter}Img(\$alt = null, \$attr = null, \$default = false)\n"
        . "  {\n"
        . "    \$f = \$this->{$getter}(\$default);\n\n"
        . "    return \$f ? new \\SQRT\\Tag\\Img(\$f, \$this->{$getter}Width(), \$this->{$getter}Height(), \$alt, \$attr) : false;\n"
        . "  }";
    }
  }

  protected function makeItemTime($def, &$func, &$before, &$after)
  {
    $col = $def['column'];

    $func[] = "  public function " . Item::MakeGetterName($col) . "(\$default = false, \$format = null)\n"
      . "  {\n"
      . "    return \$this->getAsDate('$col', \$default, \$format);\n"
      . "  }";

    $func[] = "  /** @return static */\n"
      . "  public function " . Item::MakeSetterName($col) . "(\$$col)\n"
      . "  {\n"
      . "    return \$this->setAsDate('$col', \$$col);\n"
      . "  }";
  }

  protected function makeItemFloat($def, &$func, &$before, &$after)
  {
    $col = $def['column'];

    $func[] = "  public function " . Item::MakeGetterName($col)
      . "(\$default = false, \$decimals = null, \$point = null, \$thousands = null)\n"
      . "  {\n"
      . "    return \$this->getAsFloat('{$col}', \$default, \$decimals, \$point, \$thousands);\n"
      . "  }";
    $func[] = $this->makeItemSetter($col);
  }

  protected function makeItemEnum($def, &$func, &$before, &$after)
  {
    $col        = $def['column'];
    $getter     = Item::MakeGetterName($col);
    $name_for   = 'GetNameFor' . StaticStringy::upperCamelize($col);
    $getter_arr = 'Get' . StaticStringy::upperCamelize($col) . 'Arr';
    $const      = $names = '';


    if (!empty($def['options'])) {
      foreach ($def['options'] as $v) {
        $c = strtoupper($col . '_' . $v);

        $const[] = "  const " . $c . " = '$v';";
        $names[] = "    self::" . $c . " => '$v',";
      }

      $before[] = join("\n", $const);
    }
    $before[] = "  protected static \${$col}_arr = array(\n" . join("\n", $names) . "\n  );";

    $func[] = $this->makeItemGetter($col);
    $func[] = "  public function {$getter}Name()\n"
      . "  {\n"
      . "    return static::{$name_for}(\$this->{$getter}());\n"
      . "  }";
    $func[] = "  /** @return static */\n"
      . "  public function " . Item::MakeSetterName($col) . "(\$$col)\n"
      . "  {\n"
      . "    if (!empty(\${$col}) && !static::{$name_for}(\${$col})) {\n"
      . "      Exception::ThrowError(Exception::ENUM_BAD_VALUE, '{$col}', \${$col});\n"
      . "    }\n\n"
      . "    return \$this->set('$col', \$$col);\n"
      . "  }";

    $after[] = "  public static function {$getter_arr}()\n"
      . "  {\n"
      . "    return static::\${$col}_arr;\n"
      . "  }";
    $after[] = "  public static function {$name_for}(\${$col})\n"
      . "  {\n"
      . "    \$a = static::{$getter_arr}();\n\n"
      . "    return isset(\$a[\${$col}]) ? \$a[\${$col}] : false;\n"
      . "  }";
  }

  protected function makeItemInt($def, &$func, &$before, &$after)
  {
    $col = $def['column'];

    $func[] = "  public function " . Item::MakeGetterName($col) . "(\$default = null)\n"
      . "  {\n"
      . "    return (int)\$this->get('$col', \$default);\n"
      . "  }";

    $func[] = "  /** @return static */\n"
      . "  public function " . Item::MakeSetterName($col) . "(\$$col)\n"
      . "  {\n"
      . "    return \$this->set('$col', (int)\$$col);\n"
      . "  }";
  }

  protected function makeItemGetter($col)
  {
    return "  public function " . Item::MakeGetterName($col) . "(\$default = null)\n"
    . "  {\n"
    . "    return \$this->get('$col', \$default);\n"
    . "  }";
  }

  protected function makeItemSetter($col)
  {
    return "  /** @return static */\n"
    . "  public function " . Item::MakeSetterName($col) . "(\$$col)\n"
    . "  {\n"
    . "    return \$this->set('$col', \$$col);\n"
    . "  }";
  }

  protected function makeAddExpr($col, $update = false)
  {
    $arr = $this->get($col);

    if ($arr['type'] == static::COL_ENUM) {
      $arr['type'] = static::COL_CHAR;
    }

    if (in_array($arr['type'], array(static::COL_FILE, static::COL_IMAGE))) {
      $arr['type'] = static::COL_TEXT;
    }

    $def = preg_replace('![\s]{2,}!', ' ', str_replace("\n", '', var_export($arr['definition'], true)));

    return '    $tbl->' . ($update ? 'change' : 'add') . 'Column("' . $col . '", "' . $arr['type'] . '"'
    . ($arr['definition'] ? ', ' . $def : '')
    . ');' . "\n";
  }

  protected function makeDropExpr($col)
  {
    return '    $tbl->removeColumn("' . $col . '");' . "\n";
  }

  /** Генерация внешнего ключа */
  protected function makeFK($arr, $table_exists)
  {
    $m    = $this->getManager();
    $s    = $arr['schema'] instanceof Schema ? $arr['schema'] : $m->getSchema($arr['schema']);
    $f_id = $arr['foreign_id'] ?: $s->getPrimaryKey();
    $opts = array();

    if ($arr['on_delete']) {
      $opts['delete'] = $arr['on_delete'];
    }

    if ($arr['on_update'] || $arr['on_delete']) {
      $opts['update'] = $arr['on_update'] ?: $arr['on_delete'];
    }

    $add = '    $tbl->addForeignKey("' . $arr['column'] . '", "'
      . $m->getPrefix() . $arr['table'] . '", "' . $f_id . '", '
      . str_replace("\n", '', var_export($opts, true))
      . ");\n";

    return $table_exists
      ? '    if (!$tbl->hasForeignKey(' . $arr['column'] . ')) {' . "\n  " . $add . "    }\n"
      : $add;
  }

  protected function makeAddIndex($arr, $table_exists)
  {
    $opt = false;
    if ($arr['type'] == static::INDEX_UNIQUE) {
      $opt = ', array("unique" => true)';
    }

    $cols = 'array("' . join('", "', $arr['columns']) . '")';
    $add  = '    $tbl->addIndex(' . $cols . $opt . ');' . "\n";

    return $table_exists
      ? '    if (!$tbl->hasIndex(' . $cols . ')) {' . "\n  " . $add . "    }\n"
      : $add;
  }

  /** Проверка, существует ли таблица */
  protected function checkTableExists()
  {
    $m = $this->getManager();

    $q = 'SHOW TABLES LIKE "' . $m->getPrefix() . $this->getTable() . '"';

    return (bool)$m->query($q)->rowCount();
  }

  /** Список столбцов в таблице */
  protected function getActualColumns($refresh = false)
  {
    if (is_null($this->actual_columns) || $refresh) {
      $m = $this->getManager();

      $q = 'SHOW COLUMNS FROM `' . $m->getPrefix() . $this->getTable() . '`';

      $this->actual_columns = false;

      if ($res = $m->query($q)->fetchAll(\PDO::FETCH_ASSOC)) {
        foreach ($res as $row) {
          $this->actual_columns[] = $row['Field'];
        }
      }
    }

    return $this->actual_columns;
  }

  /** Проверка, существует ли столбец в таблице */
  protected function checkColumnExists($column)
  {
    if (!$arr = $this->getActualColumns()) {
      return false;
    }

    return in_array($column, $arr);
  }

  /** Настройка схемы */
  protected function init()
  {

  }

  /** Настройка связей схемы, если нужно избежать рекурсии */
  protected function relations()
  {

  }
}
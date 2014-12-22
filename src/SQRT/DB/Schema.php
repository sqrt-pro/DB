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

  const FK_SET_NULL = 'SET NULL';
  const FK_CASCADE  = 'CASCADE';
  const FK_RESTRICT = 'RESTRICT';

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

  function __construct(Manager $manager)
  {
    $this->setManager($manager);
    $this->setName(__CLASS__);

    $this->init();
  }

  /** Заполнение таблицы данными по-умолчанию */
  public function fixture(Manager $manager)
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
    return $this->add(
      $col,
      static::COL_INT,
      array('length' => $length, 'default' => $default, 'signed' => $signed)
    );
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
    $def = array('null' => true);
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

  public function addText($col, $longtext = false)
  {
    return $this->add($col, static::COL_TEXT);
  }

  public function addForeignKey($col, $schema, $foreign_id = null, $on_delete = null, $on_update = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema);

    if (!$foreign_id = $foreign_id ?: $s->getPrimaryKey()) {
      // Exception
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

  /** Связь один-к-многим */
  public function addOneToMany($col, $schema, $foreign_id = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema);
    $t = $s->getTable();

    if (!$foreign_id = $foreign_id ?: $s->getPrimaryKey()) {
      // Exception
    }

    $this->addInt($col, 'NULL');
    $this->addForeignKey($col, $s, $foreign_id);

    $this->relations[$t] = array(
      'type'       => static::RELATION_ONE_TO_MANY,
      'column'     => $col,
      'schema'     => $s->getName(),
      'foreign_id' => $foreign_id
    );

    return $this;
  }

  /** Связь один-к-одному */
  public function addOneToOne($col, $schema, $foreign_id = null)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema);
    $t = $s->getTable();

    if (!$foreign_id = $foreign_id ?: $s->getPrimaryKey()) {
      // Exception
    }

    $this->addInt($col, 'NULL');
    $this->addForeignKey($col, $s, $foreign_id);

    $this->relations[$t] = array(
      'type'       => static::RELATION_ONE_TO_ONE,
      'column'     => $col,
      'schema'     => $s->getName(),
      'foreign_id' => $foreign_id
    );

    return $this;
  }

  /** Связь многие-к-многим через внешнюю таблицу */
  public function addManyToMany($schema, $my_col)
  {
    $m = $this->getManager();
    $s = $schema instanceof Schema ? $schema : $m->getSchema($schema);
    $t = $s->getTable();

    $this->relations[$t] = array(
      'type'       => static::RELATION_ONE_TO_ONE,
      'column'     => $my_col,
      'schema'     => $s->getName(),
      'foreign_id' => null,
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
    return $this->table;
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
    return $this->name;
  }

  /** Название схемы */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /** Класс для Item */
  public function getItemClass()
  {
    return $this->item_class ?: StaticStringy::removeRight($this->getName(), 's');
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
  public function makeMigrationName($name)
  {
    return date('YmdHis') . '_' . StaticStringy::slugify($name, '_') . '.php';
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

    if ($pk = $this->getPrimaryKey()) {
      if (!isset($this->columns[$pk])) {
        array_unshift($fields_arr, $pk);
        $func[] = $this->makeItemGetter($pk);
        $func[] = $this->makeItemSetter($pk);
      }
    }

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

        default:
          $this->makeItemChar($def, $func, $before, $after);
      }
    }

    $fields = join("',\n        '", $fields_arr);

    $str = "<?php\n\nnamespace $namespace;\n\n"
      . "use SQRT\\DB\\Exception;\n\n"
      . "/** Этот файл сгенерирован автоматически по схеме {$this->getName()} */\n"
      . "abstract class " . $this->getItemClass() . " extends " . $this->getItemBaseClass() . "\n"
      . "{\n"
      . ($before ? join("\n\n", $before) . "\n\n" : '')
      . "  protected function init()\n"
      . "  {\n"
      . "    \$this->setPrimaryKey(" . var_export($pk, true) . ");\n"
      . "    \$this->setTable('users');\n"
      . "    \$this->setFields(\n"
      . "      array(\n"
      . "        '$fields',\n"
      . "      )\n"
      . "    );\n"
      . "  }\n\n"
      . ($func ? join("\n\n", $func) : '')
      . ($after ? "\n\n" . join("\n\n", $after) . "\n" : '')
      . "}\n";

    return $str;
  }

  /** Генерация коллекции */
  public function makeCollection($namespace = 'Collection')
  {
    $class = $this->getItemClass();

    return "<?php\n\nnamespace $namespace;\n\n"
    . "/**\n"
    . ' * Этот файл сгенерирован автоматически по схеме ' . $this->getName() . "\n"
    . " *\n"
    . ' * @method \\' . $class . ' findOne($where = null) Найти и получить один объект' . "\n"
    . ' * @method \\' . $class . ' make() Создать новый объект' . "\n"
    . ' * @method \\' . $class . ' fetchObject(\PDOStatement $statement) Получение объекта из запроса' . "\n"
    . "*/\n"
    . "class " . $this->getName() . " extends " . $this->getCollectionBaseClass() . "\n"
    . "{\n"
    . "  protected function init()\n"
    . "  {\n"
    . "    \$this->setItemClass('\\$class');\n"
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
      $arr['type']                 = static::COL_CHAR;
      $arr['definition']['length'] = 255;
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

  protected function makeFK($arr, $table_exists)
  {
    $m    = $this->getManager();
    $s    = $arr['schema'] instanceof Schema ? $arr['schema'] : $m->getSchema($arr['schema']);
    $f_id = $arr['foreign_id'] ?: $s->getPrimaryKey();

    $add = '    $tbl->addForeignKey("' . $arr['column'] . '", '
      . '"' . $m->getPrefix() . $arr['table'] . '", "' . $f_id . '", '
      . 'array("delete" => "' . $arr['on_delete'] . '", "update" => "' . $arr['on_update'] . '"));' . "\n";

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
}
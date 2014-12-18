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
  protected $primary_key;
  protected $item_base_class = '\Base\Item';
  protected $type            = self::TYPE_INNODB;

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
    return $this->add($col, static::COL_CHAR, array('length' => $length));
  }

  public function addEnum($col, $options, $default = null)
  {
    return $this->add($col, static::COL_ENUM, array('default' => $default), $options);
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
    return $this->add($col, ($unix ? static::COL_TIMESTAMP : static::COL_DATETIME), array('default' => 'NULL'));
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

  public function makeMigrationName($name)
  {
    return date('YmdHis') . '_' . StaticStringy::slugify($name, '_') . '.php';
  }

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
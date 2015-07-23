<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_users', array('id' => 'id'));
    $tbl->addColumn("is_active", "boolean", array ( 'default' => 0,));
    $tbl->addColumn("age", "integer", array ( 'length' => 10, 'signed' => true, 'default' => 0,));
    $tbl->addColumn("name", "string", array ( 'length' => 255, 'null' => true,));
    $tbl->addColumn("type", "enum", array ( 'null' => true, 'length' => 255, 'values' => 'one,two',));
    $tbl->addColumn("access", "integer", array ( 'length' => 3, 'signed' => false, 'default' => 0,));
    $tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    $tbl->addColumn("text", "text", array ( 'null' => true, 'limit' => 16777216,));
    $tbl->addColumn("image", "text", array ( 'null' => true,));
    $tbl->addColumn("birthday", "date", array ( 'null' => true,));
    $tbl->addColumn("created_at", "timestamp", array ( 'default' => 'CURRENT_TIMESTAMP',));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_users', array('id' => 'id'));
    $tbl->drop();
  }
}

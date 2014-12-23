<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_users', array('id' => 'token'));
    $tbl->addColumn("age", "integer", array ( 'length' => 10, 'default' => 0, 'signed' => true,));
    $tbl->addIndex(array("age"));
    $tbl->addIndex(array("token", "age"), array("unique" => true));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_users', array('id' => 'token'));
    $tbl->drop();
  }
}

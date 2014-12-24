<?php

use Phinx\Migration\AbstractMigration;

class NewTags extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_tags', array('id' => 'id'));
    $tbl->addColumn("name", "string", array ( 'length' => 255, 'null' => true,));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_tags', array('id' => 'id'));
    $tbl->drop();
  }
}

<?php

use Phinx\Migration\AbstractMigration;

class NewAuthors extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_authors', array('id' => 'id'));
    $tbl->addColumn("name", "string", array ( 'length' => 255, 'null' => true,));
    $tbl->addColumn("birthday", "date", array ( 'null' => true,));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_authors', array('id' => 'id'));
    $tbl->drop();
  }
}

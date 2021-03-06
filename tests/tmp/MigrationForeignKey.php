<?php

use Phinx\Migration\AbstractMigration;

class ForeignKey extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_books', array('id' => 'id'));
    $tbl->addColumn("author_id", "integer", array ( 'length' => 10, 'signed' => true, 'default' => 0,));
    $tbl->addForeignKey("author_id", "test_authors", "id", array (  'delete' => 'RESTRICT',  'update' => 'CASCADE',));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_books', array('id' => 'id'));
    $tbl->drop();
  }
}

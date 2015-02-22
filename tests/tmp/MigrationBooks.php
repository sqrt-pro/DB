<?php

use Phinx\Migration\AbstractMigration;

class NewBooks extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_books', array('id' => 'id'));
    $tbl->addColumn("name", "string", array ( 'length' => 255, 'null' => true,));
    $tbl->addColumn("author_id", "integer", array ( 'length' => 11, 'signed' => true, 'null' => true,));
    $tbl->addColumn("redactor_id", "integer", array ( 'length' => 11, 'signed' => true, 'null' => true,));
    $tbl->addForeignKey("author_id", "test_authors", "id", array (  'delete' => 'RESTRICT',  'update' => 'CASCADE',));
    $tbl->addForeignKey("redactor_id", "test_authors", "id", array (  'delete' => 'RESTRICT',  'update' => 'CASCADE',));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_books', array('id' => 'id'));
    $tbl->drop();
  }
}

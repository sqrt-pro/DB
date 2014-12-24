<?php

use Phinx\Migration\AbstractMigration;

class NewBooksTags extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_books_tags', array('id' => false));
    $tbl->addColumn("book_id", "integer", array ( 'length' => 11, 'signed' => true, 'null' => true,));
    $tbl->addColumn("tag_custom_id", "integer", array ( 'length' => 11, 'signed' => true, 'null' => true,));
    $tbl->addForeignKey("book_id", "test_books", "id", array (  'delete' => 'CASCADE',  'update' => 'CASCADE',));
    $tbl->addForeignKey("tag_custom_id", "test_tags", "id", array ());
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_books_tags', array('id' => false));
    $tbl->drop();
  }
}

<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_pages', array('id' => 'id'));
    $tbl->addColumn("is_active", "boolean", array ( 'default' => 0,));
    $tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    $tbl->removeColumn("name");
    if (!$tbl->hasForeignKey("parent_id")) {
      $tbl->addForeignKey("parent_id", "test_pages", "id", array ());
    }
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_pages', array('id' => 'id'));
    $tbl->removeColumn("is_active");
    $tbl->removeColumn("price");
    // TODO: добавить инструкции для создания столбца name
    $tbl->save();
  }
}

<?php

use Phinx\Migration\AbstractMigration;

class MyMigration extends AbstractMigration
{
  public function up()
  {
    $tbl = $this->table('test_pages', array('id' => false));
    $tbl->addColumn("price", "float", array ( 'precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0,));
    $tbl->removeColumn("id");
    $tbl->changeColumn("name", "string", array ( 'length' => 255, 'null' => true,));
    $tbl->save();
  }

  public function down()
  {
    $tbl = $this->table('test_pages', array('id' => false));
    $tbl->removeColumn("price");
    // TODO: добавить инструкции для создания столбца id
    $tbl->save();
  }
}

<?php

namespace Repository;

/**
 * Этот файл сгенерирован автоматически по схеме Users
 *
 * @method \User[]|\Base\Collection find($where = null, $orderby = null, $onpage = null, $page = null) Получить коллекцию объектов
 * @method \User findOne($where = null, $orderby = null) Найти и получить один объект
 * @method \User make() Создать новый объект
 * @method \User fetchObject(\PDOStatement $statement) Получение объекта из запроса
*/
class Users extends \Base\Repository
{
  protected function init()
  {
    $this->setItemClass('\User');
    $this->setTable('users');
  }
}

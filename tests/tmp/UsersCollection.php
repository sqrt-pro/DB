<?php

namespace Collection;

/**
 * Этот файл сгенерирован автоматически по схеме Users
 *
 * @method \User[]|Users find($where = null, $orderby = null, $onpage = null, $page = null) Загрузить в коллекцию объекты
 * @method \User findOne($where = null) Найти и получить один объект
 * @method \User make() Создать новый объект
 * @method \User fetchObject(\PDOStatement $statement) Получение объекта из запроса
*/
class Users extends \Base\Collection
{
  protected function init()
  {
    $this->setItemClass('\User');
    $this->setTable('users');
  }
}

<?php

/**
 * Файл инициализации для запуска тестов
 *
 * Перед запуском сделать composer install
 * http://getcomposer.org/download/
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('TEST_MIGR', realpath(__DIR__ . '/../migrations'));
define('TEST_HOST', 'localhost');
define('TEST_USER', 'root');
define('TEST_PASS', '');
define('TEST_DB', 'test');

class TestItem extends \SQRT\DB\Item
{
  public function getId()
  {
    return $this->get('id');
  }

  /** Эквивалент генерируемой функции */
  public function setFile($file, $name = null)
  {
    $name     = $name ?: pathinfo($file, PATHINFO_BASENAME);
    $filename = $this->makeFileName('file', $name);

    return $this->processFile('file', $file, $filename, $name);
  }

  /** Эквивалент генерируемой функции */
  public function setPhoto($file, $name = null)
  {
    $name = $name ?: pathinfo($file, PATHINFO_BASENAME);
    $tmp  = $this->getFilesPath() . $this->makeFileName('photo', $name, 'temp');

    $this->copyFile($file, $tmp);

    $filename = $this->makeFileName('photo', $name, 'thumb');
    $this->processImage('photo', $tmp, $filename, $name, 'thumb');

    $filename = $this->makeFileName('photo', $name, 'big');
    $this->processImage('photo', $tmp, $filename, $name, 'big');

    unlink($tmp);

    return $this;
  }

  /**
   * Метод для процессинга изображений photo.
   * Должен вернуть объект Image или файл будет сохранен без изменений
   */
  protected function prepareImageForPhoto($file, $size)
  {
    $img = new \SQRT\Image($file);
    if ($size == 'thumb') {
      $img->cropResized(100, 100);
    } else {
      $img->resize(400, 300);
    }

    return $img;
  }

  protected function beforeSave()
  {
    $this->set('id', 10);
  }

  protected function afterSave()
  {
    $this->set('after_save', 1);
  }

  protected function beforeDelete()
  {
    $this->set('before_delete', 1);
  }

  protected function afterDelete()
  {
    $this->set('after_delete', 1);
  }

  protected function init()
  {
    $this->setPrimaryKey('id');
  }
}
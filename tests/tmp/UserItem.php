<?php

namespace ORM;

use SQRT\DB\Exception;

/** Этот файл сгенерирован автоматически по схеме Users */
abstract class User extends \Base\Item
{
  const TYPE_NEW = 'new';
  const TYPE_OLD = 'old';

  protected static $type_arr = array(
    self::TYPE_NEW => 'new',
    self::TYPE_OLD => 'old',
  );

  const LEVEL_GUEST = 1;
  const LEVEL_OWNER = 2;
  const LEVEL_ADMIN = 4;

  protected static $level_arr = array(
    self::LEVEL_GUEST => 'guest',
    self::LEVEL_OWNER => 'owner',
    self::LEVEL_ADMIN => 'admin',
  );

  const PHOTO_SIZE_THUMB = 'thumb';
  const PHOTO_SIZE_BIG = 'big';

  protected static $photo_size_arr = array(
    self::PHOTO_SIZE_THUMB => 'thumb',
    self::PHOTO_SIZE_BIG => 'big',
  );

  protected function init()
  {
    $this->setPrimaryKey('id');
    $this->setTable('users');
    $this->setFields(
      array(
        'id',
        'is_active',
        'type',
        'level',
        'age',
        'name',
        'price',
        'created_at',
        'pdf',
        'avatar',
        'photo',
      )
    );
  }

  public function getId($default = null)
  {
    return $this->get('id', $default);
  }

  /** @return static */
  public function setId($id)
  {
    return $this->set('id', $id);
  }

  public function getIsActive($default = null)
  {
    return (int)$this->get('is_active', $default);
  }

  /** @return static */
  public function setIsActive($is_active)
  {
    return $this->set('is_active', is_null($is_active) ? null : (int)$is_active);
  }

  public function getType($default = null)
  {
    return $this->get('type', $default);
  }

  public function getTypeName()
  {
    return static::GetNameForType($this->getType());
  }

  /** @return static */
  public function setType($type)
  {
    if (!empty($type) && !static::GetNameForType($type)) {
      Exception::ThrowError(Exception::ENUM_BAD_VALUE, 'type', $type);
    }

    return $this->set('type', $type);
  }

  public function isTypeNew()
  {
    return $this->getType() == static::TYPE_NEW;
  }

  public function isTypeOld()
  {
    return $this->getType() == static::TYPE_OLD;
  }

  public function getLevel($default = null)
  {
    return $this->get('level', $default);
  }

  public function hasLevel($level)
  {
    return $this->bitCheck('level', $level);
  }

  /** @return static */
  public function addLevel($level)
  {
    if (!empty($level) && !static::GetNameForLevel($level)) {
      Exception::ThrowError(Exception::ENUM_BAD_VALUE, 'level', $level);
    }

    return $this->bitAdd('level', $level);
  }

  /** @return static */
  public function removeLevel($level)
  {
    if (!empty($level) && !static::GetNameForLevel($level)) {
      Exception::ThrowError(Exception::ENUM_BAD_VALUE, 'level', $level);
    }

    return $this->bitRemove('level', $level);
  }

  public function hasLevelGuest()
  {
    return $this->hasLevel(static::LEVEL_GUEST);
  }

  public function hasLevelOwner()
  {
    return $this->hasLevel(static::LEVEL_OWNER);
  }

  public function hasLevelAdmin()
  {
    return $this->hasLevel(static::LEVEL_ADMIN);
  }

  public function getAge($default = null)
  {
    return (int)$this->get('age', $default);
  }

  /** @return static */
  public function setAge($age)
  {
    return $this->set('age', is_null($age) ? null : (int)$age);
  }

  public function getName($default = null)
  {
    return $this->get('name', $default);
  }

  /** @return static */
  public function setName($name)
  {
    return $this->set('name', $name);
  }

  public function getPrice($default = false, $decimals = null, $point = null, $thousands = null)
  {
    return $this->getAsFloat('price', $default, $decimals, $point, $thousands);
  }

  /** @return static */
  public function setPrice($price)
  {
    return $this->set('price', $price);
  }

  public function getCreatedAt($default = false, $format = null)
  {
    return $this->getAsDate('created_at', $default, $format);
  }

  /** @return static */
  public function setCreatedAt($created_at)
  {
    return $this->setAsDate('created_at', $created_at);
  }

  /** Свойство файла */
  public function getPdfParam($param, $default = false)
  {
    $arr = $this->getSerialized('pdf');

    return isset($arr[$param]) ? $arr[$param] : $default;
  }

  /** Публичный путь к файлу */
  public function getPdf($default = false)
  {
    $f = $this->getPdfParam('file');

    return !empty($f) ? $this->getPublicPath() . $f : $default;
  }

  /** Путь к файлу на сервере */
  public function getPdfPath($default = false)
  {
    $f = $this->getPdfParam('file');

    return !empty($f) ? $this->getFilesPath() . $f : $default;
  }

  /** @return \SQRT\URL */
  public function getPdfUrl($default = false)
  {
    $f = $this->getPdf();

    return $f ? new \SQRT\URL($f) : $default;
  }

  /** Размер файла. $human - человеческое представление */
  public function getPdfSize($human = true)
  {
    if (!$size = $this->getPdfParam('size')) {
      return false;
    }

    return $human ? $this->getHumanFileSize($size) : $size;
  }

  /** Название файла */
  public function getPdfName($default = false)
  {
    return $this->getPdfParam('name', $default);
  }

  /** Расширение файла */
  public function getPdfExtension($default = false)
  {
    return $this->getPdfParam('extension', $default);
  }

  /** Добавить файл $file. $name - оригинальное имя файла */
  public function setPdf($file, $name = null)
  {
    $name     = $name ?: pathinfo($file, PATHINFO_BASENAME);
    $filename = $this->makeFileName('pdf', $name);

    return $this->processFile('pdf', $file, $filename, $name);
  }

  /** Свойство файла */
  public function getAvatarParam($param, $default = false)
  {
    $arr = $this->getSerialized('avatar');

    return isset($arr[$param]) ? $arr[$param] : $default;
  }

  /** Публичный путь к файлу */
  public function getAvatar($default = false)
  {
    $f = $this->getAvatarParam('file');

    return !empty($f) ? $this->getPublicPath() . $f : $default;
  }

  /** Путь к файлу на сервере */
  public function getAvatarPath($default = false)
  {
    $f = $this->getAvatarParam('file');

    return !empty($f) ? $this->getFilesPath() . $f : $default;
  }

  /** @return \SQRT\URL */
  public function getAvatarUrl($default = false)
  {
    $f = $this->getAvatar();

    return $f ? new \SQRT\URL($f) : $default;
  }

  /** Размер файла. $human - человеческое представление */
  public function getAvatarSize($human = true)
  {
    if (!$size = $this->getAvatarParam('size')) {
      return false;
    }

    return $human ? $this->getHumanFileSize($size) : $size;
  }

  /** Название файла */
  public function getAvatarName($default = false)
  {
    return $this->getAvatarParam('name', $default);
  }

  /** Расширение файла */
  public function getAvatarExtension($default = false)
  {
    return $this->getAvatarParam('extension', $default);
  }

  /** Ширина изображения */
  public function getAvatarWidth($default = false)
  {
    return $this->getAvatarParam('width', $default);
  }

  /** Высота изображения */
  public function getAvatarHeight($default = false)
  {
    return $this->getAvatarParam('height', $default);
  }

  /** @return \SQRT\Tag\Img */
  public function getAvatarImg($alt = null, $attr = null, $default = false)
  {
    $f = $this->getAvatar($default);

    return $f ? new \SQRT\Tag\Img($f, $this->getAvatarWidth(), $this->getAvatarHeight(), $alt, $attr) : false;
  }

  /** Добавить изображение $file. $name - оригинальное имя файла */
  public function setAvatar($file, $name = null)
  {
    $name = $name ?: pathinfo($file, PATHINFO_BASENAME);
    $tmp  = $this->getFilesPath() . $this->makeFileName('avatar', $name, 'temp');

    $this->copyFile($file, $tmp);

    $filename = $this->makeFileName('avatar', $name, false);
    $this->processImage('avatar', $tmp, $filename, $name, false);

    unlink($tmp);

    return $this;
  }

  /** Свойство файла */
  public function getPhotoThumbParam($param, $default = false)
  {
    $arr = $this->getSerialized('photo');

    return isset($arr['thumb'][$param]) ? $arr['thumb'][$param] : $default;
  }

  /** Публичный путь к файлу */
  public function getPhotoThumb($default = false)
  {
    $f = $this->getPhotoThumbParam('file');

    return !empty($f) ? $this->getPublicPath() . $f : $default;
  }

  /** Путь к файлу на сервере */
  public function getPhotoThumbPath($default = false)
  {
    $f = $this->getPhotoThumbParam('file');

    return !empty($f) ? $this->getFilesPath() . $f : $default;
  }

  /** @return \SQRT\URL */
  public function getPhotoThumbUrl($default = false)
  {
    $f = $this->getPhotoThumb();

    return $f ? new \SQRT\URL($f) : $default;
  }

  /** Размер файла. $human - человеческое представление */
  public function getPhotoThumbSize($human = true)
  {
    if (!$size = $this->getPhotoThumbParam('size')) {
      return false;
    }

    return $human ? $this->getHumanFileSize($size) : $size;
  }

  /** Название файла */
  public function getPhotoThumbName($default = false)
  {
    return $this->getPhotoThumbParam('name', $default);
  }

  /** Расширение файла */
  public function getPhotoThumbExtension($default = false)
  {
    return $this->getPhotoThumbParam('extension', $default);
  }

  /** Ширина изображения */
  public function getPhotoThumbWidth($default = false)
  {
    return $this->getPhotoThumbParam('width', $default);
  }

  /** Высота изображения */
  public function getPhotoThumbHeight($default = false)
  {
    return $this->getPhotoThumbParam('height', $default);
  }

  /** @return \SQRT\Tag\Img */
  public function getPhotoThumbImg($alt = null, $attr = null, $default = false)
  {
    $f = $this->getPhotoThumb($default);

    return $f ? new \SQRT\Tag\Img($f, $this->getPhotoThumbWidth(), $this->getPhotoThumbHeight(), $alt, $attr) : false;
  }

  /** Свойство файла */
  public function getPhotoBigParam($param, $default = false)
  {
    $arr = $this->getSerialized('photo');

    return isset($arr['big'][$param]) ? $arr['big'][$param] : $default;
  }

  /** Публичный путь к файлу */
  public function getPhotoBig($default = false)
  {
    $f = $this->getPhotoBigParam('file');

    return !empty($f) ? $this->getPublicPath() . $f : $default;
  }

  /** Путь к файлу на сервере */
  public function getPhotoBigPath($default = false)
  {
    $f = $this->getPhotoBigParam('file');

    return !empty($f) ? $this->getFilesPath() . $f : $default;
  }

  /** @return \SQRT\URL */
  public function getPhotoBigUrl($default = false)
  {
    $f = $this->getPhotoBig();

    return $f ? new \SQRT\URL($f) : $default;
  }

  /** Размер файла. $human - человеческое представление */
  public function getPhotoBigSize($human = true)
  {
    if (!$size = $this->getPhotoBigParam('size')) {
      return false;
    }

    return $human ? $this->getHumanFileSize($size) : $size;
  }

  /** Название файла */
  public function getPhotoBigName($default = false)
  {
    return $this->getPhotoBigParam('name', $default);
  }

  /** Расширение файла */
  public function getPhotoBigExtension($default = false)
  {
    return $this->getPhotoBigParam('extension', $default);
  }

  /** Ширина изображения */
  public function getPhotoBigWidth($default = false)
  {
    return $this->getPhotoBigParam('width', $default);
  }

  /** Высота изображения */
  public function getPhotoBigHeight($default = false)
  {
    return $this->getPhotoBigParam('height', $default);
  }

  /** @return \SQRT\Tag\Img */
  public function getPhotoBigImg($alt = null, $attr = null, $default = false)
  {
    $f = $this->getPhotoBig($default);

    return $f ? new \SQRT\Tag\Img($f, $this->getPhotoBigWidth(), $this->getPhotoBigHeight(), $alt, $attr) : false;
  }

  /** Добавить изображение $file. $name - оригинальное имя файла */
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

  public static function GetTypeArr()
  {
    return static::$type_arr;
  }

  public static function GetNameForType($type)
  {
    $a = static::GetTypeArr();

    return isset($a[$type]) ? $a[$type] : false;
  }

  public static function GetLevelArr()
  {
    return static::$level_arr;
  }

  public static function GetNameForLevel($level)
  {
    $a = static::GetLevelArr();

    return isset($a[$level]) ? $a[$level] : false;
  }

  /**
  * Метод для процессинга изображений avatar.
  * Должен вернуть объект Image или файл будет сохранен без изменений
  */
  protected function prepareImageForAvatar($file, $size)
  {
    
  }

  /**
  * Метод для процессинга изображений photo.
  * Должен вернуть объект Image или файл будет сохранен без изменений
  */
  protected function prepareImageForPhoto($file, $size)
  {
    
  }

  public static function GetPhotoSizeArr()
  {
    return static::$photo_size_arr;
  }

  public static function GetNameForPhotoSize($size)
  {
    $a = static::GetPhotoSizeArr();

    return isset($a[$size]) ? $a[$size] : false;
  }
}

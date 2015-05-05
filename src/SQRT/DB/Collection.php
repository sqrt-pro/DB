<?php

namespace SQRT\DB;

use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection
{
  /** @var Manager */
  protected $manager;

  /** @return Manager */
  public function getManager()
  {
    return $this->manager;
  }

  /** @param Manager $manager */
  public function setManager($manager)
  {
    $this->manager = $manager;
  }

  public function isNotEmpty()
  {
    return $this->count() > 0;
  }
}
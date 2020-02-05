<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed key_code
 * @property mixed world
 * @property mixed owners_computed
 * @property mixed owners_included
 * @property mixed owners_generated
 * @property mixed owners_excluded
 */
class IndexOwners extends Model
{
  protected $table = 'Index_owners';
  protected $fillable = ['key_code', 'world'];

  public function getKeyCode()
  {
    return $this->key_code;
  }
  
  public function setKeyCode($Key)
  {
    $this->key_code = $Key;
  }

  public function getWorld()
  {
    return $this->world;
  }
  
  public function setWorld($Key)
  {
    $this->world = $Key;
  }

  public function getOwnersGenerated()
  {
    return $this->owners_generated;
  }
  
  public function setOwnersGenerated($aOwners)
  {
    $this->owners_generated = json_encode($aOwners);
  }

  public function getOwnersExcluded()
  {
    return $this->owners_excluded;
  }
  
  public function setOwnersExcluded($aOwners)
  {
    $this->owners_excluded = json_encode($aOwners);
  }

  public function getOwnersIncluded()
  {
    return $this->owners_included;
  }
  
  public function setOwnersIncluded($aOwners)
  {
    $this->owners_included = json_encode($aOwners);
  }

  public function getOwnersComputed()
  {
    return $this->owners_computed;
  }
  
  public function setOwnersComputed($aOwners)
  {
    $this->owners_computed = json_encode($aOwners);
  }
}

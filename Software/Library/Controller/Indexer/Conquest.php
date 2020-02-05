<?php

namespace Grepodata\Library\Controller\Indexer;

use Illuminate\Database\Eloquent\Collection;

class Conquest
{

  /**
   * @param $Id
   * @return
   */
  public static function first($Id)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('id', '=', $Id)
      ->first();
  }

  /**
   * @param $Index
   * @param $TownName
   * @return
   */
  public static function latestByTownName($Index, $TownName)
  {
    return $oConquest = \Grepodata\Library\Model\Indexer\Conquest::where('town_name', '=', $TownName, 'and')
      ->where('index_key', '=', $Index)
      ->orderBy('date', 'desc')
      ->first();
  }

  /**
   * @return Collection
   */
  public static function allByIndex(\Grepodata\Library\Model\Indexer\IndexInfo $oIndex)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('index_key', '=', $oIndex->key_code, 'and')
      ->orderBy('date', 'desc')
      ->get();
  }

}
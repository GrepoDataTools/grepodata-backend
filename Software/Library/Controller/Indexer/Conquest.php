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
   * @param $TownId
   * @param $Index
   * @param int $Limit
   * @return \Grepodata\Library\Model\Indexer\Conquest[]
   */
  public static function allByTownId($TownId, $Index, $Limit=50)
  {
    return $oConquest = \Grepodata\Library\Model\Indexer\Conquest::where('town_id', '=', $TownId, 'and')
      ->where('index_key', '=', $Index)
      ->orderBy('first_attack_date', 'desc')
      ->limit($Limit)
      ->get();
  }

  /**
   * @param \Grepodata\Library\Model\Indexer\IndexInfo $oIndex
   * @param int $Limit
   * @return \Grepodata\Library\Model\Indexer\Conquest[]
   */
  public static function allByIndex(\Grepodata\Library\Model\Indexer\IndexInfo $oIndex, $Limit = 100)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('index_key', '=', $oIndex->key_code, 'and')
      ->orderBy('first_attack_date', 'desc')
      ->limit($Limit)
      ->get();
  }

}
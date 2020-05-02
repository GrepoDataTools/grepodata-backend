<?php

namespace Grepodata\Library\Controller\Indexer;

use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class Conquest
{

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\Indexer\Conquest
   */
  public static function first($Id)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('id', '=', $Id)
      ->first();
  }

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\Indexer\Conquest
   */
  public static function firstOrFail($Id)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param $Uid
   * @return \Grepodata\Library\Model\Indexer\Conquest
   */
  public static function firstByUid($Uid)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('uid', '=', $Uid)
      ->firstOrFail();
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

  /**
   * @param \Grepodata\Library\Model\Indexer\IndexInfo $oIndex
   * @param int $Limit
   * @return \Grepodata\Library\Model\Indexer\Conquest[]
   */
  public static function allByIndexUnresolved(\Grepodata\Library\Model\Indexer\IndexInfo $oIndex, $Limit = 30)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::where('index_key', '=', $oIndex->key_code)
      ->whereNull('new_owner_player_id')
      ->orderBy('first_attack_date', 'desc')
      ->limit($Limit)
      ->get();
  }

  /**
   * @param World $oWorld
   * @param int $Limit
   * @return \Grepodata\Library\Model\Indexer\Conquest[]
   */
  public static function allByWorldUnresolved(World $oWorld, $Limit = 30)
  {
    return \Grepodata\Library\Model\Indexer\Conquest::select(['Index_conquest.*'])
      ->join('Index_info', 'Index_info.key_code', '=', 'Index_conquest.index_key')
      ->where('Index_info.world', '=', $oWorld->grep_id, 'and')
      ->whereNull('Index_conquest.new_owner_player_id')
      ->orderBy('Index_conquest.first_attack_date', 'desc')
      ->limit($Limit)
      ->get();
  }

}
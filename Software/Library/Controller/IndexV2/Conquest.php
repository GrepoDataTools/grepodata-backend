<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\World;

class Conquest
{

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\IndexV2\Conquest
   */
  public static function first($Id)
  {
    return \Grepodata\Library\Model\IndexV2\Conquest::where('id', '=', $Id)
      ->first();
  }

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\IndexV2\Conquest
   */
  public static function firstOrFail($Id)
  {
    return \Grepodata\Library\Model\IndexV2\Conquest::where('id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param $Uid
   * @return \Grepodata\Library\Model\IndexV2\Conquest
   */
  public static function firstByUid($Uid)
  {
    // TODO
    return \Grepodata\Library\Model\IndexV2\Conquest::where('uid', '=', $Uid)
      ->firstOrFail();
  }

  /**
   * @param $TownId
   * @param $World
   * @param int $Limit
   * @return \Grepodata\Library\Model\IndexV2\Conquest[]
   */
  public static function allByTownIdByWorld($TownId, $World, $Limit=50)
  {
    return \Grepodata\Library\Model\IndexV2\Conquest::where('town_id', '=', $TownId, 'and')
      ->where('world', '=', $World)
      ->orderBy('first_attack_date', 'desc')
      ->limit($Limit)
      ->get();
  }

  /**
   * @param \Grepodata\Library\Model\Indexer\IndexInfo $oIndex
   * @param int $Limit
   * @return \Grepodata\Library\Model\IndexV2\Conquest[]
   */
  public static function allByIndex(\Grepodata\Library\Model\Indexer\IndexInfo $oIndex, $From = 0, $Limit = 100)
  {
    // TODO
    return \Grepodata\Library\Model\IndexV2\Conquest::where('index_key', '=', $oIndex->key_code, 'and')
      ->orderBy('first_attack_date', 'desc')
      ->offset($From)
      ->limit($Limit)
      ->get();
  }

  /**
   * @param World $oWorld
   * @param int $Limit
   * @return \Grepodata\Library\Model\IndexV2\Conquest[]
   */
  public static function allByWorldUnresolved(World $oWorld, $Limit = 30)
  {
    return \Grepodata\Library\Model\IndexV2\Conquest::where('world', '=', $oWorld->grep_id, 'and')
      ->whereNull('new_owner_player_id')
      ->orderBy('first_attack_date', 'desc')
      ->limit($Limit)
      ->get();
  }

}

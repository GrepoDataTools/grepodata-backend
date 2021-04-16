<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\IndexV2\ConquestOverview;
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
    return ConquestOverview::where('uid', '=', $Uid)
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
   * @param $oUser
   * @param $ConquestId
   * @return ConquestOverview[]
   */
  public static function getByUserByConquestId($oUser, $ConquestId)
  {
    return ConquestOverview::select(['*'])
      ->leftJoin('Indexer_conquest', 'Indexer_conquest.id', '=', 'Indexer_conquest_overview.conquest_id')
      ->leftJoin('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_conquest_overview.index_key')
      ->where('Indexer_conquest.id', '=', $ConquestId, 'and')
      ->where('Indexer_roles.user_id', '=', $oUser->id)
      ->orderBy('Indexer_conquest_overview.num_attacks_counted', 'desc')
      ->first();
  }

  /**
   * @param IndexInfo $oIndex
   * @param int $Limit
   * @return ConquestOverview[]
   */
  public static function allByIndex(IndexInfo $oIndex, $From = 0, $Limit = 100)
  {
    return ConquestOverview::select(['*'])
      ->leftJoin('Indexer_conquest', 'Indexer_conquest.id', '=', 'Indexer_conquest_overview.conquest_id')
      ->where('Indexer_conquest_overview.index_key', '=', $oIndex->key_code, 'and')
      ->orderBy('Indexer_conquest.first_attack_date', 'desc')
      ->offset($From)
      ->limit($Limit)
      ->get();
  }

  /**
   * @param IndexInfo $oIndex
   * @return mixed
   */
  public static function countByIndex(IndexInfo $oIndex)
  {
    return ConquestOverview::where('index_key', '=', $oIndex->key_code)
      ->count();
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

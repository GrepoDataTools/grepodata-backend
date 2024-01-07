<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\IndexV2\ConquestOverview;
use Grepodata\Library\Model\World;
use Illuminate\Support\Str;

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
   * @return \Grepodata\Library\Model\IndexV2\ConquestOverview
   */
  public static function firstByUid($Uid)
  {
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
    // Even though Indexer_conquest_overview.conquest_id may match to multiple records, security is maintained due to the Indexer_roles.user_id check.
    // In case there are duplicates, the overview with the most num_attacks_counted is returned; this ensures we always return the most complete overview.
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

  /**
   * @param $World
   * @param $MaxDate
   * @return ConquestOverview[]
   */
  public static function allRecentByWorld($World, $MaxDate)
  {
    // first_attack_date is not in UTC but in server time! so watch out what MaxDate you use.
    $query = ConquestOverview::select(['*'])
      ->leftJoin('Indexer_conquest', 'Indexer_conquest.id', '=', 'Indexer_conquest_overview.conquest_id')
      ->where('Indexer_conquest.world', '=', $World)
      ->where('Indexer_conquest_overview.published', '=', 1)
      ->where('Indexer_conquest.first_attack_date','<=', $MaxDate)
      ->where('Indexer_conquest.first_attack_date','>', $MaxDate->copy()->subHours(48))
      ->orderBy('Indexer_conquest_overview.num_attacks_counted', 'desc');
    //$sql = Str::replaceArray('?', $query->getBindings(), $query->toSql());
    return $query->get();
    //select * from `Indexer_conquest_overview` left join `Indexer_conquest` on `Indexer_conquest`.`id` = `Indexer_conquest_overview`.`conquest_id` where `Indexer_conquest`.`world` = "nl108" and `Indexer_conquest_overview`.`published` = 1 and `Indexer_conquest`.`first_attack_date` <= "2023-11-04 00:00:00" and `Indexer_conquest`.`first_attack_date` > "2023-11-02 00:00:00" order by `Indexer_conquest_overview`.`num_attacks_counted` desc
  }

}

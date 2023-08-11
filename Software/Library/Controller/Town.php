<?php

namespace Grepodata\Library\Controller;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Town
{

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Town Town
   */
  public static function first($Id, $World)
  {
    return \Grepodata\Library\Model\Town::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param \Grepodata\Library\Model\Island oIsland
   * @return \Grepodata\Library\Model\Town[]
   */
  public static function allByIsland(\Grepodata\Library\Model\Island $oIsland)
  {
    return \Grepodata\Library\Model\Town::where('world', '=', $oIsland->world, 'and')
      ->where('island_x', '=', $oIsland->island_x, 'and')
      ->where('island_y', '=', $oIsland->island_y)
      ->get();
  }

  /**
   * @param $World
   * @param $UpdateLimit
   * @return \Grepodata\Library\Model\Town[]
   */
  public static function allByWorldAndUpdate($World, $UpdateLimit)
  {
    return \Grepodata\Library\Model\Town::where('world', '=', $World, 'and')
      ->where('updated_at', '>', $UpdateLimit)
      ->get();
  }

  /**
   * @param $World
   * @return \Grepodata\Library\Model\Town[]
   */
  public static function allByWorld($World)
  {
    return \Grepodata\Library\Model\Town::where('world', '=', $World, 'and')
      ->get();
  }

  /**
   * @param $World
   * @param $TownName
   * @param $PlayerId
   * @return bool | \Grepodata\Library\Model\Town Town
   */
  public static function firstByNameByPlayerId($World, $TownName, $PlayerId)
  {
    return \Grepodata\Library\Model\Town::where('name', '=', $TownName, 'and')
      ->where('player_id', '=', $PlayerId, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param $World
   * @param $Id
   * @return bool | \Grepodata\Library\Model\Town Town
   */
  public static function firstById($World, $Id)
  {
    return \Grepodata\Library\Model\Town::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Town Town
   */
  public static function firstOrFail($Id, $World)
  {
    return \Grepodata\Library\Model\Town::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->firstOrFail();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Town | Model Town
   */
  public static function firstOrNew($Id, $World)
  {
    return \Grepodata\Library\Model\Town::firstOrNew(array(
      'grep_id' => $Id,
      'world'   => $World
    ));
  }

  /**
   * @param $World
   * @param $x_min
   * @param $x_max
   * @param $y_min
   * @param $y_max
   * @return bool|Collection|\Grepodata\Library\Model\Town[]
   */
  public static function allInRange($World, $x_min, $x_max, $y_min, $y_max)
  {
    // TODO: cache in Redis, requests will be duplicated and data does not change frequently
    return \Grepodata\Library\Model\Town::where('world', '=', $World, 'and')
      ->where('island_x','>=', $x_min)
      ->where('island_x','<=', $x_max)
      ->where('island_y','>=', $y_min)
      ->where('island_y','<=', $y_max)
      ->cursor();
  }

  /**
   * @param $World
   * @param $Id
   * @return bool|Collection|\Grepodata\Library\Model\Town[]
   */
  public static function allByPlayer($Id, $World)
  {
    return \Grepodata\Library\Model\Town::where('world', '=', $World, 'and')
      ->where('player_id','=', $Id)
      ->orderBy('name', 'asc')
      ->get();
  }

  /**
   * @param $World
   * @param $Id
   * @return bool|Collection|\Grepodata\Library\Model\Town[]
   */
  public static function allByAlliance($Id, $World)
  {
//    $aPlayers = Player::allByAlliance($World, $Id);
//    $aTowns = array();
//    foreach ($aPlayers as $oPlayer) {
//      $aTownList = self::allByPlayer($oPlayer->grep_id, $World);
//      foreach ($aTownList as $oTown) {
//        $aTowns[] = $oTown->getMinimalFields();
//      }
//    }
//
//    return $aTowns;
//    return \Grepodata\Library\Model\Town::select(['Town.grep_id', 'Town.name', 'Town.points'])
    return \Grepodata\Library\Model\Town::select(['Town.*'])
      ->join('Alliance', 'Alliance.world', '=', 'Town.world')
      ->join('Player', 'Player.alliance_id', '=', 'Alliance.grep_id')
      ->where('Town.world', '=', $World, 'and')
      ->where('Town.player_id','=', 'Player.grep_id', 'and')
      ->where('Alliance.grep_id','=', $Id)
      ->get();
  }

  /**
   * @param $aParams
   * @param int $Limit
   * @return Collection
   */
  public static function search($aParams, $Limit = 30)
  {
    if (isset($aParams['query']) && isset($aParams['world'])) {
      return \Grepodata\Library\Model\Town::where('name', 'LIKE', '%'.$aParams['query'].'%', 'and')
        ->where('world', '=', $aParams['world'])
        ->orderBy('points', 'desc')
        ->limit($Limit)
        ->get();
    } else if (isset($aParams['query'])) {
      return \Grepodata\Library\Model\Town::where('name', 'LIKE', '%'.$aParams['query'].'%', 'and')
        ->orderBy('points', 'desc')
        ->limit($Limit)
        ->get();
    }
    return false;
  }
}

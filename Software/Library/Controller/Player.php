<?php

namespace Grepodata\Library\Controller;

use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class Player
{

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Player Player
   */
  public static function first($Id, $World)
  {
    return \Grepodata\Library\Model\Player::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param $World
   * @param $Name
   * @return \Grepodata\Library\Model\Player Player
   */
  public static function firstByName($World, $Name)
  {
    return \Grepodata\Library\Model\Player::where('world', '=', $World, 'and')
      ->where('name', '=', $Name)
      ->first();
  }

  /**
   * @param $World
   * @param $Id
   * @return \Grepodata\Library\Model\Player Player
   */
  public static function firstById($World, $Id)
  {
    return \Grepodata\Library\Model\Player::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Player Player
   */
  public static function firstOrFail($Id, $World)
  {
    return \Grepodata\Library\Model\Player::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->firstOrFail();
  }

  /**
   * @param $Id
   * @param $Server
   * @return \Grepodata\Library\Model\Player Player
   */
  public static function firstByIdAndServer($Id, $Server)
  {
    return \Grepodata\Library\Model\Player::where('grep_id', '=', $Id, 'and')
      ->where('world', 'LIKE', $Server.'%')
      ->firstOrFail();
  }

  /**
   * @param $World
   * @return Collection|\Grepodata\Library\Model\Player[]
   */
  public static function allActiveByWorld($World)
  {
    return \Grepodata\Library\Model\Player::where('world', '=', $World, 'and')
      ->where('active', '=', 1)
      ->get();
  }

  /**
   * @param $World
   * @param $UpdateLimit
   * @return Collection|\Grepodata\Library\Model\Player[]
   */
  public static function allByWorldAndUpdate($World, $UpdateLimit)
  {
    return \Grepodata\Library\Model\Player::where('world', '=', $World, 'and')
      ->where('data_update', '>', $UpdateLimit)
      ->get();
  }

  /**
   * @param $World, $Id
   * @return Collection|\Grepodata\Library\Model\Player[]
   */
  public static function allByAlliance($World, $Id)
  {
    return \Grepodata\Library\Model\Player::where('world', '=', $World,'and')
      ->where('alliance_id', '=', $Id)
      ->get();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Player | Model Player
   */
  public static function firstOrNew($Id, $World)
  {
    return \Grepodata\Library\Model\Player::firstOrNew(array(
      'grep_id' => $Id,
      'world'   => $World
    ));
  }

  /**
   * Copy att and def records to att_old/def_old for each players in a given world
   * @param $World
   * @throws \Exception
   */
  public static function resetAttDefScores($World)
  {
    $aResult = \Grepodata\Library\Model\Player::where('world', '=', $World, 'and')
      ->where('active', '=', 1)
      ->update([
        'att_old' => DB::raw("`att`"),
        'def_old' => DB::raw("`def`"),
      ]);
    // This OR makes sure that records are only updated IFF they have been changed. but time gained is minimal so we dont use it
//    ->where(function ($query) {
//    $query->where('att_old', '!=', DB::raw("`att`"))
//      ->orWhere('def_old', '!=', DB::raw("`def`"));
//    })
    if ($aResult <= 0) {
      throw new \Exception('CRITICAL: Invalid result after resetting player att/def scores: ' . $aResult);
    }
  }

  /**
   * @param \Grepodata\Library\Model\World $oWorld
   * @throws \Exception
   */
  public static function processHistoryRecords($oWorld)
  {
    $oCursor = \Grepodata\Library\Model\Player::where('world', '=', $oWorld->grep_id, 'and')
      ->where('active', '=', 1)
      ->cursor();

    $HistoryDate = World::getHistoryDate($oWorld);
    $aAllianceNames = array();
    /** @var \Grepodata\Library\Model\Player $oPlayer */
    foreach ($oCursor as $oPlayer) {
      try {
        if ($oPlayer->towns > 0) {
          PlayerHistory::addHistoryRecordFromPlayer($oPlayer, $oWorld, $aAllianceNames, $HistoryDate);
        }
      } catch (\Illuminate\Database\QueryException $e){
        $errorCode = $e->errorInfo[1];
        if($errorCode == 1062){
          throw new \Exception('Duplicate date entry for player history record!');
        }
        Logger::warning("Unhandled exception when processing player history update: " . $e->getMessage());
      } catch (\Exception $e) {
        Logger::warning('Error processing history record for player ' . $oPlayer->grep_id . '. ('.$e->getMessage().')');
      }
    }
  }

  /**
   * @param $aParams
   * @return Collection|\Grepodata\Library\Model\Player[] | bool
   */
  public static function search($aParams)
  {
    $Limit = 30;
    if (isset($aParams['size']) && $aParams['size']!=null && $aParams['size']!='' && $aParams['size']!=0) {
      $Limit = $aParams['size'];
    }
    if (isset($aParams['query']) && isset($aParams['world'])) {
      return \Grepodata\Library\Model\Player::where('name', 'LIKE', '%'.$aParams['query'].'%', 'and')
        ->where('world', '=', $aParams['world'])
        ->orderBy('points', 'desc')
        ->limit($Limit)
        ->get();
    }
    if (isset($aParams['query'])) {
      return \Grepodata\Library\Model\Player::where('name', 'LIKE', '%'.$aParams['query'].'%')
        ->orderBy('points', 'desc')
        ->limit($Limit)
        ->get();
    }
    return false;
  }
}
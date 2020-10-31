<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Alliance
{

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Alliance Alliance
   */
  public static function first($Id, $World)
  {
    return \Grepodata\Library\Model\Alliance::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->first();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Alliance Alliance
   */
  public static function firstOrFail($Id, $World)
  {
    return \Grepodata\Library\Model\Alliance::where('grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->firstOrFail();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Alliance | Model Alliance
   */
  public static function firstOrNew($Id, $World)
  {
    return \Grepodata\Library\Model\Alliance::firstOrNew(array(
      'grep_id' => $Id,
      'world'   => $World
    ));
  }

  /**
   * @param $World
   * @return Collection|\Grepodata\Library\Model\Alliance[]
   */
  public static function allByWorld($World)
  {
    return \Grepodata\Library\Model\Alliance::where('world', '=', $World)
      ->get();
  }

  /**
   * @param $World
   * @param $UpdateLimit
   * @return Collection|\Grepodata\Library\Model\Alliance[]
   */
  public static function allByWorldAndUpdate($World, $UpdateLimit)
  {
    return \Grepodata\Library\Model\Alliance::where('world', '=', $World, 'and')
      ->where('updated_at', '>', $UpdateLimit)
      ->get();
  }

  /**
   * @param \Grepodata\Library\Model\World $oWorld
   * @throws \Exception
   */
  public static function processHistoryRecords($oWorld)
  {
    $DayLimit = Carbon::now()->subDays(14);
    /** @var \Grepodata\Library\Model\Alliance $oAlliance */
    foreach (\Grepodata\Library\Model\Alliance::where('world', '=', $oWorld->grep_id, 'and')->where('updated_at', '>', $DayLimit)->cursor() as $oAlliance) {
      try {
        AllianceHistory::addHistoryRecordFromAlliance($oAlliance, $oWorld);
      } catch (\Illuminate\Database\QueryException $e){
        $errorCode = $e->errorInfo[1];
        if($errorCode == 1062){
          throw new \Exception('Duplicate date entry for alliance history record!');
        }
      } catch (\Exception $e) {
        Logger::warning('Error processing history record for alliance ' . $oAlliance->grep_id . '. ('.$e->getMessage().')');
      }
    }
  }

  /**
   * @param $aParams
   * @param int $Limit
   * @return Collection
   */
  public static function search($aParams, $Limit = 30)
  {
    if (isset($aParams['query']) && isset($aParams['world'])) {
      return \Grepodata\Library\Model\Alliance::where('name', 'LIKE', '%'.$aParams['query'].'%', 'and')
        ->where('world', '=', $aParams['world'])
        ->orderBy('points', 'desc')
        ->limit($Limit)
        ->get();
    }
    if (isset($aParams['query'])) {
      return \Grepodata\Library\Model\Alliance::where('name', 'LIKE', '%'.$aParams['query'].'%')
        ->orderBy('points', 'desc')
        ->limit($Limit)
        ->get();
    }
    return false;
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Player[]
   */
  public static function getAllianceMembers($Id, $World)
  {
    return \Grepodata\Library\Model\Player::where('alliance_id', '=', $Id, 'and')
      ->where('world', '=', $World, 'and')
      ->where('active', '=', 1)
      ->orderBy('points', 'desc')
      ->get();
  }
}
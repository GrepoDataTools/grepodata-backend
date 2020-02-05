<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlayerHistory
{

  /**
   * @param $Id
   * @param $World
   * @return \Illuminate\Database\Eloquent\Collection Player history records
   */
  public static function getPlayerHistory($Id, $World, $Limit = 30)
  {
    if ($Limit > 0) {
      return \Grepodata\Library\Model\PlayerHistory::where('grep_id', '=', $Id, 'and')
        ->where('world', '=', $World)
        ->orderBy('created_at', 'desc')
        ->limit($Limit)
        ->get();
    } else {
      return \Grepodata\Library\Model\PlayerHistory::where('grep_id', '=', $Id, 'and')
        ->where('world', '=', $World)
        ->orderBy('created_at', 'desc')
        ->get();
    }
  }

  public static function addHistoryRecordFromPlayer(\Grepodata\Library\Model\Player $oPlayer, \Grepodata\Library\Model\World $oWorld, &$aAllianceNames, $HistoryDate)
  {
    $oPlayerHistory = new \Grepodata\Library\Model\PlayerHistory();
    $oPlayerHistory->grep_id  = $oPlayer->grep_id;
    $oPlayerHistory->world    = $oPlayer->world;
    $oPlayerHistory->date     = $HistoryDate;
    $oPlayerHistory->alliance_id = $oPlayer->alliance_id;
    $oPlayerHistory->points   = $oPlayer->points;
    $oPlayerHistory->rank     = $oPlayer->rank;
    $oPlayerHistory->att      = ($oPlayer->att != null) ? $oPlayer->att : '0';
    $oPlayerHistory->def      = ($oPlayer->def != null) ? $oPlayer->def : '0';
    $oPlayerHistory->towns    = $oPlayer->towns;

    // Add alliance name
    $AllianceName = '';
    if ($oPlayer->alliance_id != '' && $oPlayer->alliance_id != null && $oPlayer->alliance_id != 0) {
      try {
        if ($Name = $aAllianceNames[$oPlayer->alliance_id] ?? null) {
          $AllianceName = $Name;
        } else {
          $oAlliance = \Grepodata\Library\Controller\Alliance::firstOrFail($oPlayer->alliance_id, $oPlayer->world);
          $AllianceName = $oAlliance->name;
          $aAllianceNames[$oPlayer->alliance_id] = $AllianceName;
        }
      } catch (ModelNotFoundException $e) {} //Ignore optional alliance name fail
    }
    $oPlayerHistory->alliance_name = $AllianceName;

    // Save new record
    $oPlayerHistory->save();
  }

}
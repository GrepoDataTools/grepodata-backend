<?php

namespace Grepodata\Library\Controller;

class AllianceHistory
{

  /**
   * @param $Id
   * @param $World
   * @return \Illuminate\Database\Eloquent\Collection Alliance history records
   */
  public static function getAllianceHistory($Id, $World, $Limit = 30)
  {
    if ($Limit > 0) {
      return \Grepodata\Library\Model\AllianceHistory::where('grep_id', '=', $Id, 'and')
        ->where('world', '=', $World)
        ->orderBy('created_at', 'desc')
        ->limit($Limit)
        ->get();
    } else {
      return \Grepodata\Library\Model\AllianceHistory::where('grep_id', '=', $Id, 'and')
        ->where('world', '=', $World)
        ->orderBy('created_at', 'desc')
        ->get();
    }
  }

  public static function addHistoryRecordFromAlliance(\Grepodata\Library\Model\Alliance $oAlliance, \Grepodata\Library\Model\World $oWorld)
  {
    $oAllianceHistory = new \Grepodata\Library\Model\AllianceHistory();
    $oAllianceHistory->grep_id  = $oAlliance->grep_id;
    $oAllianceHistory->world    = $oAlliance->world;
    $oAllianceHistory->date     = World::getHistoryDate($oWorld);
    $oAllianceHistory->points   = $oAlliance->points;
    $oAllianceHistory->rank     = $oAlliance->rank;
    $oAllianceHistory->att      = ($oAlliance->att != null) ? $oAlliance->att : '0';
    $oAllianceHistory->def      = ($oAlliance->def != null) ? $oAlliance->def : '0';
    $oAllianceHistory->towns    = $oAlliance->towns;
    $oAllianceHistory->members  = $oAlliance->members;
    $oAllianceHistory->domination_percentage = $oAlliance->domination_percentage;
    $oAllianceHistory->save();
  }

}
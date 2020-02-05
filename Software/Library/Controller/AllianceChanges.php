<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;

class AllianceChanges
{

  /**
   * @param $Id
   * @param $World
   * @return \Illuminate\Database\Eloquent\Collection Alliance changes
   */
  public static function getChangesByPlayerId($Id, $World, $From = 0, $Size = 10)
  {
    return \Grepodata\Library\Model\AllianceChanges::where('player_grep_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->orderBy('created_at', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Illuminate\Database\Eloquent\Collection Alliance changes
   */
  public static function getChangesByAllianceId($Id, $World, $From = 0, $Size = 10)
  {
    return \Grepodata\Library\Model\AllianceChanges::where(function ($query) use ($Id) {
      $query->where('new_alliance_grep_id', '=', $Id)
        ->orWhere('old_alliance_grep_id', '=', $Id);
    })
      ->where('world', '=', $World)
      ->orderBy('created_at', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * @param $World
   * @param int $From
   * @param int $Size
   * @return \Illuminate\Database\Eloquent\Collection Alliance changes
   */
  public static function getLatestChangesByWorld($World, $From = 0, $Size = 30)
  {
    return \Grepodata\Library\Model\AllianceChanges::where('world', '=', $World)
      ->orderBy('created_at', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * @param $World
   * @param int $From
   * @param int $Size
   * @return \Illuminate\Database\Eloquent\Collection Alliance changes
   */
  public static function getBiggestChangesByWorld($World, $From = 0, $Size = 30)
  {
    return \Grepodata\Library\Model\AllianceChanges::where('world', '=', $World)
      ->where('created_at', '>', Carbon::now()->subHours(24))
      ->orderBy('player_points', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * @param $World
   * @param $Date
   * @param int $From
   * @param int $Size
   * @return \Illuminate\Database\Eloquent\Collection Alliance changes
   */
  public static function getBiggestChangesByDate($World, $Date, $From = 0, $Size = 30)
  {
    return \Grepodata\Library\Model\AllianceChanges::where('world', '=', $World, 'and')
      ->where('created_at', '>', Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($Date)), 'UTC')->setTime(0,0,0), 'and')
      ->where('created_at', '<', Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($Date)), 'UTC')->setTime(0,0,0)->addHours(24))
      ->orderBy('player_points', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * Build new alliance change
   * @param \Grepodata\Library\Model\Player $oPlayer
   * @param \Grepodata\Library\Model\World $oWorld
   * @param $NewAllianceId
   * @param $OldAllianceId
   * @param $aAllianceData
   */
  public static function addAllianceChange(\Grepodata\Library\Model\Player $oPlayer, \Grepodata\Library\Model\World $oWorld, $NewAllianceId, $OldAllianceId, $aAllianceData)
  {
    $oAllianceChange = new \Grepodata\Library\Model\AllianceChanges();
    $oAllianceChange->world           = $oWorld->grep_id;
    $oAllianceChange->player_grep_id  = $oPlayer->grep_id;
    $oAllianceChange->player_name     = $oPlayer->name;
    $oAllianceChange->player_rank     = $oPlayer->rank;
    $oAllianceChange->player_points   = $oPlayer->points;

    //Find old alliance
    $OldId = 0;
    $OldName = '';
    $OldRank = 0;
    $OldPoints = 0;
    if ($OldAllianceId != null && $OldAllianceId != '0' && $OldAllianceId != 0) {
      $oOldAlliance = Alliance::first($OldAllianceId, $oWorld->grep_id);
      if ($oOldAlliance != null && $oOldAlliance != false) {
        $OldId = $OldAllianceId;
        $OldName = (isset($oOldAlliance->name) ? $oOldAlliance->name : '');
        $OldRank = (isset($oOldAlliance->rank) ? $oOldAlliance->rank : 0);
        $OldPoints = (isset($oOldAlliance->points) ? $oOldAlliance->points : 0);
      } else {
        // use newest alliance data
        if ($aAlliance = $aAllianceData[$OldAllianceId] ?? null) {
          $OldId = $aAlliance['grep_id'];
          $OldName = $aAlliance['name'];
          $OldRank = $aAlliance['rank'];
          $OldPoints = $aAlliance['points'];
        }
      }
    }
    $oAllianceChange->old_alliance_grep_id = $OldId;
    $oAllianceChange->old_alliance_name    = $OldName;
    $oAllianceChange->old_alliance_rank    = $OldRank;
    $oAllianceChange->old_alliance_points  = $OldPoints;

    //Find new alliance
    $NewId = 0;
    $NewName = '';
    $NewRank = 0;
    $NewPoints = 0;
    if ($NewAllianceId != null && $NewAllianceId != '0' && $NewAllianceId != 0) {
      $oNewAlliance = Alliance::first($NewAllianceId, $oWorld->grep_id);
      if ($oNewAlliance != null && $oNewAlliance != false) {
        $NewId = $NewAllianceId;
        $NewName = (isset($oNewAlliance->name) ? $oNewAlliance->name : '');
        $NewRank = (isset($oNewAlliance->rank) ? $oNewAlliance->rank : 0);
        $NewPoints = (isset($oNewAlliance->points) ? $oNewAlliance->points : 0);
      } else {
        // use newest alliance data
        if ($aAlliance = $aAllianceData[$NewAllianceId] ?? null) {
          $NewId = $aAlliance['grep_id'];
          $NewName = $aAlliance['name'];
          $NewRank = $aAlliance['rank'];
          $NewPoints = $aAlliance['points'];
        }
      }
    }
    $oAllianceChange->new_alliance_grep_id = $NewId;
    $oAllianceChange->new_alliance_name    = $NewName;
    $oAllianceChange->new_alliance_rank    = $NewRank;
    $oAllianceChange->new_alliance_points  = $NewPoints;

    $oAllianceChange->save();
  }

}
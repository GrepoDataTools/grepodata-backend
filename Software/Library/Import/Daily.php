<?php

namespace Grepodata\Library\Import;

use Carbon\Carbon;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\AllianceChanges;
use Grepodata\Library\Controller\Player;

use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\World;

class Daily
{

  /**
   * @param World $oWorld
   * @return bool
   * @throws \Exception
   */
  public static function DataImportDaily(World $oWorld)
  {
    Import::EnsureIndex();

    // get endpoint data
    $aPlayerData = InnoData::loadPlayerData($oWorld->grep_id);
    if (!is_array($aPlayerData) || sizeof($aPlayerData)<=0) {
      // will be empty if max retries are reached
      Logger::warning("Stopping daily import: empty player data retrieved from endpoint");
      return false;
    }
    try {
      // Save players to disk
      LocalData::setLocalPlayerData($oWorld->grep_id, $aPlayerData);
    } catch (\Exception $e) {
      Logger::warning("Error saving player data to disk: ".$e->getMessage());
    }

    $aAllianceData = InnoData::loadAllianceData($oWorld->grep_id);
    if (!is_array($aAllianceData) || sizeof($aAllianceData)<=0) {
      // will be empty if max retries are reached
      Logger::warning("Stopping daily import: empty alliance data retrieved from endpoint");
      return false;
    }

    $TotalPlayers = sizeof($aPlayerData);
    $TotalAlliances = sizeof($aAllianceData);
    Logger::silly("Downloaded data for world " . $oWorld->grep_id . ": ".$TotalPlayers." Players, ".$TotalAlliances." Alliances.");

    // Load database status
    $aPlayers = Player::allActiveByWorld($oWorld->grep_id);
    $DatabasePlayers = $aPlayers->count();
    Logger::silly("Loaded " . $DatabasePlayers . " active players from database.");

    if ($TotalPlayers < (.90 * $DatabasePlayers)) {
      Logger::warning("Remote data size too small! Database count: " . $DatabasePlayers . ", remote count: " . $TotalPlayers);
    }

    // handle player data
    $NumNew = 0;
    $NumSkipped = 0;
    $NumUpdated = 0;
    $NumDeleted = 0;
    foreach ($aPlayers as $oPlayer) {
      try {
        if ($aData = $aPlayerData[$oPlayer->grep_id] ?? null) {
          $aPlayerData[$oPlayer->grep_id]['checked'] = true;

          if (($oPlayer->rank < 500 && $oPlayer->rank != $aData['rank'])
            || $oPlayer->alliance_id != $aData['alliance_id']
            || $oPlayer->points != $aData['points']
            || $oPlayer->towns != $aData['towns']
            || $oPlayer->name != $aData['name']
          ) {
            // Update
            $NumUpdated++;

            // Check max rank + towns
            if ($oPlayer->rank_max == null
              || ($oPlayer->rank < $aData['rank'] && $oPlayer->rank <= $oPlayer->rank_max)) {
              $oPlayer->rank_max = $oPlayer->rank != null ? $oPlayer->rank : $aData['rank'];
              $oPlayer->rank_date = Carbon::now();
            }
            if ($oPlayer->towns_max == null
              || ($oPlayer->towns > $aData['towns'] && $oPlayer->towns >= $oPlayer->towns_max)) {
              $oPlayer->towns_max = $oPlayer->towns != null ? $oPlayer->towns : $aData['towns'];
              $oPlayer->towns_date = Carbon::now();
            }

            try {
              // Save time since last town point gained
              if ((int) $oPlayer->points < (int) $aData['points']) {
                $oPlayer->town_point_date = Carbon::now();
              }
            } catch (\Exception $e) {}

            try {
              //Detect alliance change
              if (isset($aData['alliance_id']) && isset($oPlayer->alliance_id) && ((string) $aData['alliance_id'] != (string) $oPlayer->alliance_id)) {
                AllianceChanges::addAllianceChange($oPlayer, $oWorld, $aData['alliance_id'], $oPlayer->alliance_id, $aAllianceData);
              }
            } catch (\Exception $e) {}

            // Detect ghost event (new points is substantially lower than old points)
            if (
              (int) $aData['points'] < 5000 &&
              (int) $oPlayer->points > 50000
            ) {
              $oPlayer->ghost_alliance = $oPlayer->alliance_id;
              $oPlayer->ghost_time = Carbon::now();
            }

            // Reactivate ghosted players if they started playing again (this usually happens with players that ghost in the early game)
            if (
              $oPlayer->is_ghost == 1 &&
              $aData['points'] > 5000
            ) {
              Logger::warning('reactivated ghost '.$oPlayer->grep_id.' '.$oWorld->grep_id);
              $oPlayer->is_ghost = null;
              $oPlayer->ghost_alliance = null;
              $oPlayer->ghost_time = null;
            }

            foreach ($aData as $Key => $Value) {
              if ($Value == null) {
                $Value = '';
              }
              $oPlayer->$Key = $Value;
            }
            $oPlayer->data_update = Carbon::now();
            $oPlayer->save();

          } else {
            $NumSkipped++;
          }
        } else {
          // Player is no longer listed in server data > no longer playing: soft delete
          $oPlayer->points = 0;
          $oPlayer->active = false; // setting active to false will prevent the ES import from reindexing the player
          $oPlayer->save();
          $NumDeleted++;

          Import::DeletePlayer($oPlayer);  // player is deleted from ES to hide them from search results & ranking
        }

      } catch (\Exception $e) {
        Logger::warning("Exception while updating player with id " . (isset($aData['grep_id'])?$aData['grep_id']:'?') . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }
    }

    // Add new players
    foreach ($aPlayerData as $aData) {
      try {
        if (isset($aData['checked'])) {
          continue;
        }
        $NumNew++;
        $oPlayer = Player::firstOrNew($aData['grep_id'], $oWorld->grep_id);
        foreach ($aData as $Key => $Value) {
          if ($Value == null) $Value = '';
          $oPlayer->$Key = $Value;
        }
        $oPlayer->active = true;
        $oPlayer->att_point_date = Carbon::now();
        $oPlayer->town_point_date = Carbon::now();
        $oPlayer->data_update = Carbon::now();
        $oPlayer->save();
      } catch (\Exception $e) {
        Logger::warning("Exception while adding new player with id " . (isset($aData['grep_id'])?$aData['grep_id']:'?') . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }
    }
    unset($aPlayerData);
    unset($aPlayers);

    if ($NumSkipped + $NumUpdated + $NumDeleted != $DatabasePlayers) {
      Logger::warning("Player import: Update count mismatch");
    }
    Logger::silly("Finished updating players for world " .$oWorld->grep_id. ". Total: ".$TotalPlayers.", Skipped: ".$NumSkipped.", Updated: ".$NumUpdated.", New: " . $NumNew.", Deleted: " . $NumDeleted);

    // Load previous alliance import
    $aPreviousAllianceData = LocalData::getLocalAllianceData($oWorld->grep_id);

    // handle alliance data
    $NumNew = 0;
    $NumSkipped = 0;
    $NumUpdated = 0;
    foreach ($aAllianceData as $aData) {
      try {
        $bUpdate = false;
        $aPrevious = null;

        if ($aPreviousAllianceData != false && $aAlliance = $aPreviousAllianceData[$aData['grep_id']] ?? null) {
          $aPrevious = $aAlliance;
        }

        if ($aPrevious == null) {
          $bUpdate = true;
          $NumNew++;
        } else if (
          $aPrevious['points'] != $aData['points']
          || $aPrevious['members'] != $aData['members']
          || $aPrevious['towns'] != $aData['towns']
          || $aPrevious['name'] != $aData['name']
          || $aPrevious['rank'] != $aData['rank']
        ) {
          $bUpdate = true;
          $NumUpdated++;
        } else {
          $bUpdate = false;
          $NumSkipped++;
        }

        if ($bUpdate === true) {
          $oAlliance = Alliance::firstOrNew($aData['grep_id'], $oWorld->grep_id);
          foreach ($aData as $Key => $Value) {
            $oAlliance->$Key = $Value;
          }
          $oAlliance->save();
        }
      } catch (\Exception $e) {
        Logger::warning("Exception while updating alliance with id " . (isset($aData['grep_id'])?$aData['grep_id']:'?') . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }

    }

    // Save new data to disk for next import
    LocalData::setLocalAllianceData($oWorld->grep_id, $aAllianceData);
    unset($aAllianceData);
    unset($aPreviousAllianceData);

    if ($NumSkipped + $NumNew + $NumUpdated != $TotalAlliances) {
      Logger::warning("Alliance import: Update count mismatch");
    }
    Logger::silly("Finished updating alliances for world " .$oWorld->grep_id. ". Total: ".$TotalAlliances.", Skipped: ".$NumSkipped.", Updated: ".$NumUpdated.", New: " . $NumNew);


    return true;
  }

}

<?php

namespace Grepodata\Library\Import;

use Carbon\Carbon;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Island;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\PlayerScoreboard;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\TownGhost;
use Grepodata\Library\Controller\TownOffset;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Linked;
use Grepodata\Library\Model\World;

class Towns
{

  public static function DataImportIslands(World $oWorld)
  {
    $oIsland = Island::firstByWorld($oWorld->grep_id);
    if ($oIsland !== null && $oIsland !== false) {
      // Is indexed
      return true;
    }
    Logger::warning("Importing islands for world: " . $oWorld->grep_id);

    // get endpoint data
    $aIslandData = InnoData::loadIslandData($oWorld->grep_id);
    $Total = sizeof($aIslandData);
    Logger::silly("Downloaded island data for world " . $oWorld->grep_id . ": ".$Total." Islands.");

    // handle island data
    $num = 0;
    $batchSize = 5000;
    $aRows = array();
    $now = Carbon::now()->toDateTimeString();
    foreach ($aIslandData as $aData) {
      try {
        $num++;
        $aData['world'] = $oWorld->grep_id;
        $aData['created_at'] = $now;
        $aData['updated_at'] = $now;
        $aRows[] = $aData;

        if (sizeof($aRows) > 0 && sizeof($aRows) % $batchSize == 0) {
          // batch insert
          $batch_insert = \Grepodata\Library\Model\Island::insert($aRows);
          $aRows = array();
          if ($batch_insert === false) {
            Logger::warning("island batch insert result is false");
          }
        }
      } catch (\Exception $e) {
        Logger::warning("Exception while updating island with id " . (isset($aData['grep_id'])?$aData['grep_id']:'?') . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }
    }
    Logger::silly("Updated " . $num . " islands");

    unset($aIslandsData);
    return true;
  }

  public static function DataImportTowns(World $oWorld, $aUnconfirmedLinks = array(), $aTownOffsets = null)
  {
    // get endpoint data
    $aTownData = InnoData::loadTownData($oWorld->grep_id);
    $Total = sizeof($aTownData);
    Logger::silly("Downloaded town data for world " . $oWorld->grep_id . ": ".$Total." Towns.");

    // Load last execution
    $aPreviousData = LocalData::getLocalTownData($oWorld->grep_id);
    Logger::silly("Loaded local town data for world " . $oWorld->grep_id . ": ".sizeof($aPreviousData)." Towns.");

    // Hashmaps
    $aIslandCache = array();
    if ($aTownOffsets == null) {
      $aTownOffsets = TownOffset::getAllAsHasmap();
    }

    // handle town data
    $NumNew = 0;
    $NumSkipped = 0;
    $NumUpdated = 0;
    $aGhostedPlayers = array();
    foreach ($aTownData as $aData) {
      try {
        $bNew = false;
        $bUpdate = false;
        $aPrevious = null;

        if ($aPreviousData != false) {
          $aPrevious = $aPreviousData[$aData['grep_id']] ?? null;
        }

        if ($aPrevious == null) {
          $bNew = true;
          $bUpdate = true;
          $NumNew++;
        } else if (
             $aPrevious['points'] != $aData['points']
          || $aPrevious['player_id'] != $aData['player_id']
          || $aPrevious['name'] != $aData['name']
        ) {
          $bUpdate = true;
          $NumUpdated++;

          if ($aData['player_id'] == 0 && $aPrevious['player_id'] > 0) {
            // player ghosted. trigger ghost event
            try {
              $GhostedPlayer = $aPrevious['player_id'];
              if (key_exists($GhostedPlayer, $aGhostedPlayers)) {
                $aGhostedPlayers[$GhostedPlayer] += 1;
              } else {
                $aGhostedPlayers[$GhostedPlayer] = 1;
              }

              $oGhostTown = new \Grepodata\Library\Model\TownGhost();
              $oGhostTown->world = $oWorld->grep_id;
              $oGhostTown->grep_id = $aPrevious['grep_id'];
              $oGhostTown->player_id = $aPrevious['player_id'];
              $oGhostTown->island_x = $aPrevious['island_x'];
              $oGhostTown->island_y = $aPrevious['island_y'];
              $oGhostTown->name = $aPrevious['name'];
              $oGhostTown->points = $aPrevious['points'];
              $oGhostTown->save();

            } catch (\Exception $e) {
              Logger::warning("Error handling ghost town event ". $oWorld->grep_id." ".$aData['grep_id']. " - ".$e->getMessage());
            }

          }
        } else {
          $bUpdate = false;
          $NumSkipped++;
        }

        if ($bUpdate === true) {
          $oTown = Town::firstOrNew($aData['grep_id'], $oWorld->grep_id);
          foreach ($aData as $Key => $Value) {
            $oTown->$Key = $Value;
          }

          // Calculate absolute coordinates for new town
          if ($bNew) {

            // Get island
            $IslandId = $oTown->island_x . "_" . $oTown->island_y . "_" . $oTown->world;
            if (key_exists($IslandId, $aIslandCache)) {
              $oIsland = $aIslandCache[$IslandId];
            } else {
              $oIsland = Island::firstByXY($oTown->island_x, $oTown->island_y, $oTown->world);
              $aIslandCache[$IslandId] = $oIsland;
            }

            if ($oIsland == false) {
              Logger::warning("New town import; island not found: [$oTown->grep_id] -> $oTown->island_x, $oTown->island_y, $oTown->world");
            } else {

              // Get offset
              $OffsetKey = TownOffset::getKeyForTown($oTown, $oIsland);
              $oOffset = $aTownOffsets[$OffsetKey];

              // Calculate coordinates
              $aCoordinates = Island::getAbsoluteTownCoordinates($oIsland, $oOffset);
              $oTown->absolute_x = $aCoordinates[0];
              $oTown->absolute_y = $aCoordinates[1];
              $oTown->island_type = $oIsland->island_type;
            }

          }

          $oTown->save();

          // Check unconfirmed links
//          try {
//            /** @var Linked $oLinked */
//            foreach ($aUnconfirmedLinks as $oLinked) {
//              if ($oTown->player_id == $oLinked->player_id
//                && $oTown->name == $oLinked->town_token
//                && substr($oTown->world, 0, 2) == $oLinked->server) {
//                // Account link confirmed!
//                Logger::warning("Account link confirmed ".$oLinked->id);
//                \Grepodata\Library\Controller\IndexV2\Linked::setConfirmed($oLinked);
//              }
//            }
//          } catch (\Exception $e) {
//            Logger::warning("Town import: Error updating account link" . $e->getMessage());
//          }
        }
      } catch (\Exception $e) {
        Logger::warning("Exception while updating town with id " . (isset($aData['grep_id'])?$aData['grep_id']:'?') . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }
    }
    unset($aPreviousData);

    // Parse ghosted players
    try {
      foreach ($aGhostedPlayers as $PlayerId => $NumTowns) {
        $Player = null;
        $Alliance = null;
        try {
          $Player = Player::firstOrFail($PlayerId, $oWorld->grep_id);
          $Player->is_ghost = true;
          if (is_null($Player->ghost_alliance)) {
            $Player->ghost_alliance = $Player->alliance_id ?? 0;
            $Player->ghost_time = Carbon::now();
          }
          $Player->save();
        } catch (\Exception $e) {
          Logger::warning("Error handling ghost player event ". $oWorld->grep_id." ".$PlayerId. " - ".$e->getMessage());
        }
      }

    } catch (\Exception $e) {
      Logger::warning("Error handling ghost events ". $oWorld->grep_id." - ".$e->getMessage());
    }

    if ($NumSkipped + $NumNew + $NumUpdated != $Total) {
      Logger::warning("Town import: Update count mismatch");
    }
    Logger::silly("Town update stats for world " .$oWorld->grep_id. ". Total: ".$Total.", Skipped: ".$NumSkipped.", Updated: ".$NumUpdated.", New: " . $NumNew);

    // Save new data to disk for next import
    LocalData::setLocalTownData($oWorld->grep_id, $aTownData);

    unset($aTownData);
    unset($aPreviousData);
    return true;
  }

}

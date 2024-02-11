<?php

namespace Grepodata\Library\Import;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\TownOffset;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\TownGhost;
use Grepodata\Library\Model\World;

class Towns
{

  public static function DataImportTowns(World $oWorld, $aTownOffsets = null)
  {
    // get endpoint data
    $aTownData = InnoData::loadTownData($oWorld->grep_id);
    $Total = sizeof($aTownData);
    Logger::silly("Downloaded town data for world " . $oWorld->grep_id . ": ".$Total." Towns.");

    try {
      // Save new data to disk for other imports
      LocalData::setLocalTownData($oWorld->grep_id, $aTownData);
    } catch (\Exception $e) {
      Logger::warning("Error saving town data to disk: ".$e->getMessage());
    }

    // Load database status
    $aTowns = Town::allByWorld($oWorld->grep_id);
    $DatabaseTowns = $aTowns->count();
    Logger::silly("Loaded " . $DatabaseTowns . " towns from database.");

    if ($Total < (.90 * $DatabaseTowns)) {
      Logger::warning("Remote town data size too small! Database count: " . $DatabaseTowns . ", remote count: " . $Total);
    }
    if ($Total < (.50 * $DatabaseTowns)) {
      Logger::error("Remote town data size mismatch! Database count: " . $DatabaseTowns . ", remote count: " . $Total . ". Stopping town import for world ".$oWorld->grep_id);
      return false;
    }

    // Prepare hashmaps
    if ($aTownOffsets == null) {
      $aTownOffsets = TownOffset::getAllAsHasmap();
    }
    $aIslandData = InnoData::loadIslandData($oWorld->grep_id);
    $aIslandTypeMap = array();
    foreach ($aIslandData as $aIsland) {
      $aIslandTypeMap[$aIsland['island_x']."_".$aIsland['island_y']] = $aIsland['island_type'];
    }
    unset($aIslandData);

    // handle town data
    $NumNew = 0;
    $NumSkipped = 0;
    $NumUpdated = 0;
    $NumDeleted = 0;
    $NumGhosts = 0;
    $aGhostedPlayers = array();
    foreach ($aTowns as $oTown) {
      try {
        // Find database town in server town data
        if ($aData = $aTownData[$oTown->grep_id] ?? null) {
          $aTownData[$oTown->grep_id]['checked'] = true;

          if ($oTown->player_id != $aData['player_id']
            || $oTown->points != $aData['points']
            || $oTown->name != $aData['name']
          ) {
            // Update town data
            $NumUpdated++;
            foreach ($aData as $Key => $Value) {
              $oTown->$Key = $Value;
            }
            $oTown->save();

            // Check if town is ghost town
            if ($aData['player_id'] == 0 && $oTown->player_id) {
              // player ghosted. trigger ghost event
              $NumGhosts++;
              try {
                $GhostedPlayer = $oTown->player_id;
                if (key_exists($GhostedPlayer, $aGhostedPlayers)) {
                  $aGhostedPlayers[$GhostedPlayer] += 1;
                } else {
                  $aGhostedPlayers[$GhostedPlayer] = 1;
                }

                $oGhostTown = new TownGhost();
                $oGhostTown->world = $oWorld->grep_id;
                $oGhostTown->grep_id = $oTown->grep_id;
                $oGhostTown->player_id = $oTown->player_id;
                $oGhostTown->island_x = $oTown->island_x;
                $oGhostTown->island_y = $oTown->island_y;
                $oGhostTown->name = $oTown->name;
                $oGhostTown->points = $oTown->points;
                $oGhostTown->save();
              } catch (\Exception $e) {
                Logger::warning("Error handling ghost town event ". $oWorld->grep_id." ".$oTown->grep_id. " - ".$e->getMessage());
              }
            }
          } else {
            // No data updates; skip
            $NumSkipped++;
          }
        } else {
          // Town is no longer listed in server data > delete town
          $oTown->delete();
          $NumDeleted++;

          Import::DeleteTown($oTown);  // town is deleted from ES to hide it from search results & ranking
        }

      } catch (\Exception $e) {
        Logger::warning("Exception while updating town with id " . $oTown->grep_id . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }
    }

    // Add new towns
    foreach ($aTownData as $aData) {
      try {
        if (isset($aData['checked'])) {
          continue;
        }
        $NumNew++;

        // Create new town object
        $oTown = Town::firstOrNew($aData['grep_id'], $oWorld->grep_id);
        foreach ($aData as $Key => $Value) {
          $oTown->$Key = $Value;
        }

        // Calculate absolute coordinates for new town
        try {
          if ($oTown->island_x == null || $oTown->island_y == null || $oTown->island_i == null) {
            throw new Exception("Invalid data for new town. " . json_encode($oTown));
          }

          // Get island
          $IslandId = $oTown->island_x . "_" . $oTown->island_y;
          if (key_exists($IslandId, $aIslandTypeMap)) {
            $IslandType = $aIslandTypeMap[$IslandId];
            $oTown->island_type = $IslandType;

            // Get offset
            $OffsetKey = TownOffset::getKeyForTown($oTown);
            $oOffset = $aTownOffsets[$OffsetKey];

            // Calculate coordinates
            $aCoordinates = TownOffset::getAbsoluteTownCoordinates($oTown, $oOffset);
            $oTown->absolute_x = $aCoordinates[0];
            $oTown->absolute_y = $aCoordinates[1];
          } else {
            Logger::warning("New town import; island not found: [$oTown->grep_id] -> $oTown->island_x, $oTown->island_y, $oTown->world");
          }
        } catch (\Exception $e) {
          Logger::error("New town import; unexpected error: ". $e->getMessage() . "[".$e->getTraceAsString()."]");
        }

        // Save new town
        $oTown->save();
      } catch (\Exception $e) {
        Logger::warning("Exception while adding new town with id " . ($aData['grep_id'] ?? '?') . " (world ".$oWorld->grep_id.") [".$e->getMessage()."]");
      }
    }

    // Parse ghosted players
    try {
      foreach ($aGhostedPlayers as $PlayerId => $NumTowns) {
        $Player = null;
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

    unset($aIslandTypeMap);
    unset($aTownData);
    unset($aTowns);

    return true;
  }

}

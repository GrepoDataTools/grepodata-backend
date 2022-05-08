<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Roles;
use Illuminate\Database\Capsule\Manager as DB;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started index enhancer");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 20*60);

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
if ($worlds === false) {
  Logger::error("Terminating execution of index enhancer: Error retrieving active worlds from database.");
  Common::endExecution(__FILE__);
}

/** @var \Grepodata\Library\Model\World $oWorld */
foreach ($worlds as $oWorld) {
  try {
    // Check commands 'php SCRIPTNAME[=0] INDEX[=1]'
    if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

    Logger::silly("Start intel enhancement for world ".$oWorld->grep_id
      .". Memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    $ChangedOwner = 0;
    $Updated = 0;
    $Total = 0;
    $TownNameUpdated = 0;
    $AllianceUpdated = 0;
    $NamesUpdated = 0;
    $SkippedMissingTowns = 0;
    $MissingPlayers = 0;
    $GhostTowns = 0;

    // Get all intel records for this world
    $aIntelCursor = \Grepodata\Library\Controller\IndexV2\Intel::worldCursor($oWorld, true);

    // Load local towns & players
    $aLocalTowns = LocalData::getLocalTownData($oWorld->grep_id);
    $aLocalPlayers = LocalData::getLocalPlayerData($oWorld->grep_id);

    if (!$aLocalTowns || !is_array($aLocalTowns) || count($aLocalTowns) <= 0) {
      Logger::warning("Missing local town data for intel enhancement");
      continue;
    }

    if (!$aLocalPlayers || !is_array($aLocalPlayers) || count($aLocalPlayers) <= 0) {
      Logger::warning("Missing local player data for intel enhancement");
      continue;
    }

    // Iterate over all intel
    foreach ($aIntelCursor as $oCity) {
      $Total++;

      // Update towns
      try {

        if (is_null($oCity->town_id) || $oCity->parsing_failed == true) {
          // we can skip unparsed intel
          continue;
        }

        // Get latest town data from local file
        if (key_exists($oCity->town_id, $aLocalTowns)) {
          $oTown = $aLocalTowns[$oCity->town_id];
        } else {
          // town no longer exists, skip update
          $SkippedMissingTowns++;
          continue;
        }

        if ($oTown == null || $oTown['grep_id'] == null) {
          // town data missing, skip update
          $SkippedMissingTowns++;
          continue;
        }

        // Check if town still belongs to player
        if ($oTown['player_id'] == 0) {
          // Town is now a ghost town, keep intel active
          $GhostTowns++;
          continue;
        } else {
          $bSave = false;

          // Check town owner
          if ($oCity->player_id != $oTown['player_id']) {
            // Town changed owner. Keep intel that changed owner and indicate this in the client (e.g. flag 'changed_owner=>true')
            $oCity->player_id = $oTown['player_id'];
            $oCity->is_previous_owner_intel = true;
            $bSave = true;
            $ChangedOwner++;
          }

          // Update town name
          if ($oCity->town_name != $oTown['name']) {
            $oCity->town_name = $oTown['name'];
            $TownNameUpdated++;
            $bSave = true;
          }

          // Get player
          $oPlayer = null;
          if (key_exists($oCity->player_id, $aLocalPlayers)) {
            $oPlayer = $aLocalPlayers[$oCity->player_id];
          } else {
            // Player can be missing if account was reset; intel should stay active
            $MissingPlayers++;
          }

          // Update alliance id
          if ($oPlayer !== null && $oCity->alliance_id != $oPlayer['alliance_id']) {
            $oCity->alliance_id = $oPlayer['alliance_id'];
            $AllianceUpdated++;
            $bSave = true;
          }

          // Update player name
          if ($oPlayer !== null && $oCity->player_name != $oPlayer['name']) {
            $oCity->player_name = $oPlayer['name'];
            $NamesUpdated++;
            $bSave = true;
          }

          // Save changes if needed
          if ($bSave === true) {
            $Updated++;
            $oCity->save();
          }
        }

      } catch (\Exception $e) {
        Logger::warning("Error updating intel towns for world " . $oWorld->grep_id . " (".$e->getMessage().")");
      }
    }

    // Check if we can resolve an ongoing siege
    try {
      $aSieges = \Grepodata\Library\Controller\IndexV2\Conquest::allByWorldUnresolved($oWorld, 500);
      $Now = $oWorld->getServerTime();
      foreach ($aSieges as $oConquest) {
        $LastAttack = Carbon::parse($oConquest->first_attack_date, $oWorld->php_timezone);
        if ($LastAttack == null) continue;

        $aTownConquests = \Grepodata\Library\Controller\Conquest::getTownConquests($oConquest->town_id, $oWorld->grep_id);
        $bFoundMatch = false;
        if (!empty($aTownConquests)) {
          foreach ($aTownConquests as $oTownConquest) {
            $oConquestTime = Carbon::parse($oTownConquest->time)->setTimezone($oWorld->php_timezone);

            $DiffToSiege = $LastAttack->diffInMinutes($oConquestTime, false);
            if ($DiffToSiege > 0 && $DiffToSiege < 20*60) {
              // Conquest is within 20 hours AFTER the last registered attack on the siege
              // assume that the siege was successful and the town now has a new owner
              Logger::silly("Resolved ongoing conquest via IndexEnhancer; town has a new owner. conquest_id: " . $oConquest->id);
              $oConquest->new_owner_player_id = $oTownConquest->n_p_id;
              $oConquest->save();
              $bFoundMatch = true;
              break;
            }

          }
        }

        if ($bFoundMatch == false && $Now->diffInHours($LastAttack) > (24*7)) {
          // Last attack was 7 days ago, probable a temple conquest. end it
          Logger::silly("Resolved ongoing conquest via IndexEnhancer; town conquest timed out after 7 days (temple?). conquest_id: " . $oConquest->id);
          $oConquest->new_owner_player_id = 1;
          $oConquest->save();
        } else if ($bFoundMatch == false && $Now->diffInHours($LastAttack) > 24) {
          // Found no overlapping conquest and siege must be over by now
          // Siege likely failed so new owner = old owner
          // CS was likely killed but that report was not indexed
          Logger::silly("Resolved ongoing conquest via IndexEnhancer; town conquest timed out after 24hrs. conquest_id: " . $oConquest->id);
          $oConquest->new_owner_player_id = $oConquest->player_id;
          $oConquest->save();
        }
      }

    } catch (\Exception $e) {
      Logger::warning("Error parsing ongoing sieges for world " . $oWorld->grep_id . ": " . $e->getMessage());
    }

    Logger::debugInfo("Processed ".$Total." intel records for world ".$oWorld->grep_id
      ." - Num updated: $Updated (town: $TownNameUpdated, alliance: $AllianceUpdated, player: $NamesUpdated), "
      ." - Errors: (missing town: $SkippedMissingTowns, missing players: $MissingPlayers), "
      ."new owner: $ChangedOwner");

    unset($aLocalTowns);
    unset($aLocalPlayers);
    unset($aIntelCursor);

  } catch (\Exception $e) {
    Logger::error("Error enhancing intel for world " . $oWorld->grep_id . " (".$e->getMessage().")");
    continue;
  }

}

// Deprecated:
// Remove duplicate roles (caused by rare race condition on V1 (implicit) import)
try {
  $aDuplicates = (array) DB::select( DB::raw("
        SELECT Indexer_roles.user_id, Indexer_roles.index_key, COUNT(*)
        FROM Indexer_roles
        GROUP BY Indexer_roles.user_id, Indexer_roles.index_key
        HAVING COUNT(*) > 1;
        "));
  if ($aDuplicates && count($aDuplicates)>0) {
    Logger::warning("Cleaning " . count($aDuplicates) . " duplicate index roles ");
    foreach ($aDuplicates as $aDuplicate) {
      $aDuplicate = (array) $aDuplicate;
      $uid = $aDuplicate['user_id'];
      $index = $aDuplicate['index_key'];

      /** @var Roles[] $aDuplicateRoles */
      $aDuplicateRoles = Roles::where('user_id', '=', $uid)->where('index_key', '=', $index)->get();

      $oKeepRole = null;
      foreach ($aDuplicateRoles as $oRole) {
        // First eval
        if ($oKeepRole == null) {
          $oKeepRole = $oRole;
          continue;
        }

        // Check access level;
        $role_id_keep = array_search($oKeepRole->role, \Grepodata\Library\Controller\IndexV2\Roles::numbered_roles);
        $role_id = array_search($oRole->role, \Grepodata\Library\Controller\IndexV2\Roles::numbered_roles);
        if ($role_id > $role_id_keep) {
          // Keep higher role (e.g. owner is higher than member)
          $oKeepRole = $oRole;
        } else if ($role_id == $role_id_keep) {
          // Same access level; check last created
          if ($oRole->id > $oKeepRole->id) {
            $oKeepRole = $oRole;
          }
        }
        // else: lower access level, ignore

      }

      if ($oKeepRole != null) {
        foreach ($aDuplicateRoles as $oRole) {
          if ($oRole->id != $oKeepRole->id) {
            Logger::debugInfo("Deleting duplicate role for user ".$uid. " on index ".$index." deleted role: ".json_encode($oRole));
            $oRole->delete();
          }
        }
      }

    }
  }
} catch (\Exception $e) {
  Logger::error("Error cleaning duplicate roles (".$e->getMessage().")");
}

Logger::debugInfo("Finished successful execution of index enhancer.");
Common::endExecution(__FILE__, $Start);

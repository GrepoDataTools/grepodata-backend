<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\IndexV2\Intel;

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

    // Get all intel for this world
    $aIntelRecords = \Grepodata\Library\Controller\IndexV2\Intel::allByWorld($oWorld);

    $aCachedTowns = array();
    $aCachedPlayers = array();
    foreach ($aIntelRecords as $oCity) {
      // Update towns
      try {
        // TODO: use local cached town file to skip database requests?
        if (key_exists($oCity->town_id, $aCachedTowns)) {
          $oTown = $aCachedTowns[$oCity->town_id];
        } else {
          $oTown = Town::first($oCity->town_id, $oWorld->grep_id);
          $aCachedTowns[$oCity->town_id] = $oTown;
        }

        // Check if town still belongs to player
        if ($oTown !== null && $oTown->player_id == 0) {
          // Town is now a ghost town, keep intel active
          continue;
        } else if ($oTown !== null && $oCity->player_id != $oTown->player_id) {
          // Town changed owner. Delete intel
          // TODO: keep intel that changed owner and somehow indicate this in the client (e.g. flag 'changed_owner=>true')
          $oCity->delete();
        } else {
          $bSave = false;

          // Update town name
          if ($oTown !== null && $oCity->town_name != $oTown->name) {
            $oCity->town_name = $oTown->name;
            $bSave = true;
          }

          // Get player
          // TODO: use local cached player file to skip database requests?
          if (key_exists($oCity->player_id, $aCachedPlayers)) {
            $oPlayer = $aCachedPlayers[$oCity->player_id];
          } else {
            $oPlayer = Player::first($oCity->player_id, $oWorld->grep_id);
            $aCachedPlayers[$oCity->player_id] = $oPlayer;
          }

          // Update alliance id
          if ($oPlayer != null && $oCity->alliance_id != $oPlayer->alliance_id) {
            $oCity->alliance_id = $oPlayer->alliance_id;
            $bSave = true;
          }

          // Update player name
          if ($oPlayer !== null && $oCity->player_name != $oPlayer->name) {
            $oCity->player_name = $oPlayer->name;
            $oCity->save();
          }

          // Save changes
          if ($bSave === true) {
            $oCity->save();
          }
        }

      } catch (\Exception $e) {}
    }

      // TODO: check if we can resolve an ongoing siege
//      try {
//        $aSieges = Conquest::allByIndexUnresolved($oIndex, 50);
//        $Now = $oWorld->getServerTime();
//        foreach ($aSieges as $oConquest) {
//          $LastAttack = Carbon::parse($oConquest->first_attack_date, $oWorld->php_timezone);
//          if ($LastAttack == null) continue;
//
//          $aTownConquests = \Grepodata\Library\Controller\Conquest::getTownConquests($oConquest->town_id, $oIndex->world);
//          $bFoundMatch = false;
//          if (!empty($aTownConquests)) {
//            foreach ($aTownConquests as $oTownConquest) {
//              $oConquestTime = Carbon::parse($oTownConquest->time)->setTimezone($oWorld->php_timezone);
//
//              $DiffToSiege = $LastAttack->diffInMinutes($oConquestTime, false);
//              if ($DiffToSiege > 0 && $DiffToSiege < 20*60) {
//                // Conquest is within 20 hours AFTER the last registered attack on the siege
//                // assume that the siege was successful and the town now has a new owner
//                Logger::silly("Resolved ongoing conquest via IndexEnhancer; town has a new owner. conquest_id: " . $oConquest->id);
//                $oConquest->new_owner_player_id = $oTownConquest->n_p_id;
//                $oConquest->save();
//                $bFoundMatch = true;
//                break;
//              }
//
//            }
//          }
//
//          if ($bFoundMatch == false && $Now->diffInHours($LastAttack) > 24) {
//            // Found no overlapping conquest and siege must be over by now
//            // Siege likely failed so new owner = old owner
//            // CS was likely killed but that report was not indexed
//            Logger::silly("Resolved ongoing conquest via IndexEnhancer; town conquest timed out after 24hrs. conquest_id: " . $oConquest->id);
//            $oConquest->new_owner_player_id = $oConquest->player_id;
//            $oConquest->save();
//          }
//        }
//
//      } catch (\Exception $e) {
//        Logger::warning("Error parsing ongoing sieges for index " . $oIndex->key_code . ": " . $e->getMessage());
//      }

    unset($aCachedTowns);
    unset($aCachedPlayers);

  } catch (\Exception $e) {
    Logger::error("Error enhancing intel for world " . $oWorld->grep_id . " (".$e->getMessage().")");
    continue;
  }

}

Logger::debugInfo("Finished successful execution of index enhancer.");
Common::endExecution(__FILE__, $Start);

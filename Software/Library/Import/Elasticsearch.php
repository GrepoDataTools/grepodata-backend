<?php

namespace Grepodata\Library\Import;

use Carbon\Carbon;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\World;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Elasticsearch\Import;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Elasticsearch
{

  public static function DataImportElasticsearch(World $oWorld, $bForceAllObjects = false)
  {
    Logger::silly("Running Elasticsearch import for " . $oWorld->grep_id);
    Import::EnsureIndex();
    Import::EnsureTownIndex();

    $IndexErrors = 0;
    $IndexedDocuments = 0;
    $aAllianceNames = array();

    // Import alliances for this world
    if ($bForceAllObjects) {
      $aAlliances = Alliance::allByWorld($oWorld->grep_id);
    } else {
      $DateLimit = Carbon::now()->subDays(16); // If updated within last 16 days
      $aAlliances = Alliance::allByWorldAndUpdate($oWorld->grep_id, $DateLimit);
    }
    foreach ($aAlliances as $oAlliance) {
      try {
        Import::SaveAlliance($oAlliance);
        $IndexedDocuments++;

        $aAllianceNames[$oAlliance->grep_id] = $oAlliance->name;
      } catch (\Exception $e) {$IndexErrors++;}
    }

    unset($aAlliances);

    // Import players for this world
    $aPlayers = Player::allActiveByWorld($oWorld->grep_id);
    $aBatch = array();
    $BatchSize = 50;
    foreach ($aPlayers as $oPlayer) {
      try {
        $AllianceName = '';
        if ($oPlayer->alliance_id != '' && $oPlayer->alliance_id != 0) {
          try {
            if ($Name = $aAllianceNames[$oPlayer->alliance_id] ?? null) {
              $AllianceName = $Name;
            } else {
              $oAlliance = Alliance::firstOrFail($oPlayer->alliance_id, $oWorld->grep_id);
              $AllianceName = $oAlliance->name;
              $aAllianceNames[$oAlliance->grep_id] = $oAlliance->name;
            }
          } catch (ModelNotFoundException $e) {}
        }

        // Add batch item
        $aBatch[] = array(
          'player' => $oPlayer,
          'alliance_name' => $AllianceName,
        );
        $IndexedDocuments++;
        if (count($aBatch) % $BatchSize === 0) {
          try {
            Import::SavePlayerBatch($aBatch);
          } catch (\Exception $e) {
            Logger::warning("ES batch error: " . $e->getMessage());
          }
          $aBatch = array();
        }

      } catch (\Exception $e) {$IndexErrors++;}
    }
    if (count($aBatch) > 0) {
      Import::SavePlayerBatch($aBatch);
    }
    unset($aBatch);
    unset($aAllianceNames);
    unset($aPlayers);

    // Import towns
    if ($bForceAllObjects) {
      $aTowns = Town::allByWorld($oWorld->grep_id);
    } else {
      $DateLimit = Carbon::now()->subDays(3); // If updated within last 3 days
      $aTowns = Town::allByWorldAndUpdate($oWorld->grep_id, $DateLimit);
    }
    $aBatch = array();
    $BatchSize = 50;
    foreach ($aTowns as $oTown) {
      try {
        // Add batch item
        $aBatch[] = $oTown;
        $IndexedDocuments++;
        if (count($aBatch) % $BatchSize === 0) {
          try {
            Import::SavePlayerBatch($aBatch);
          } catch (\Exception $e) {
            Logger::warning("ES batch error: " . $e->getMessage());
          }
          $aBatch = array();
        }

      } catch (\Exception $e) {$IndexErrors++;}
    }
    unset($aBatch);
    unset($aTowns);

    // Log error if there are too many errors (1 in 1000 is max allowed error rate)
    if ($IndexedDocuments/1000 < $IndexErrors) {
      Logger::error("Too many errors while indexing elasticsearch data. (Indexed documents: ".$IndexedDocuments.", Errors: ".$IndexErrors.")");
      return false;
    }

    Logger::silly("Finished Elasticsearch import for " . $oWorld->grep_id . ". Indexed: " . $IndexedDocuments . ", Errors: " . $IndexErrors);
    return true;
  }

}
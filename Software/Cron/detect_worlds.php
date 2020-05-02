<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Cron\WorldData;
use Grepodata\Library\Import\Daily;
use Grepodata\Library\Import\Elasticsearch;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Import\Towns;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started world detector");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 20*60);

$aServers = World::getServers();

foreach ($aServers as $Server) {

  // Check new world
  $oWorld = World::getPreviousWorld($Server);

  if ($oWorld == null || $oWorld == false) {
    Logger::error("Server detector was unable to get latest world for server " . $Server);
    continue;
  }

  // Increment world id
  $PreviousWorldNum = substr($oWorld->grep_id, 2);

  for ($i = 1; $i < 5; $i++) {
    $WorldNum = $PreviousWorldNum + $i;
    $WorldNum = $Server . $WorldNum;
    try {
      $oExistingWorld = World::getWorldById($WorldNum);
      // world exists, skip to next world
      if (!empty($oExistingWorld) || $oExistingWorld->grep_id == null) {
        Logger::debugInfo("World already exists: " . $WorldNum);
        continue;
      }
    } catch (\Exception $e) {
      // world does not exist, we can continue checking
      Logger::debugInfo("Checking new world with id: " . $WorldNum);
    }

    // Test endpoint
    if (InnoData::testEndpoint($WorldNum)) {
      $aPlayerData = InnoData::loadPlayerData($WorldNum);
      if (count($aPlayerData) <= 5) {
        Logger::warning("New world has less then 5 players. skipping new world.");
        continue;
      }
      unset($aPlayerData);

      // new world!
      Logger::warning("New world detected: " . $WorldNum);
      $oNewWorld = new \Grepodata\Library\Model\World();
      $oNewWorld->grep_id = $WorldNum;
      $oNewWorld->php_timezone = $oWorld->php_timezone;
      $oNewWorld->name = $WorldNum;
      $oNewWorld->stopped = 1;
      $oNewWorld->last_reset_time = $oWorld->last_reset_time;
      $oNewWorld->grep_server_time = $oWorld->grep_server_time;
      $oNewWorld->etag = $oWorld->etag;
      $oNewWorld->save();

      // Handle imports for new world
      try {
        Logger::debugInfo('Starting daily import for new world.');
        if (Daily::DataImportDaily($oNewWorld) !== true) {
          throw new \Exception("Daily import failed for new world.");
        }
        Logger::debugInfo('Daily import completed. Starting hourly import for new world.');
        if (Hourly::DataImportHourly($oNewWorld, true) !== true) {
          throw new \Exception("Hourly import failed for new world.");
        }
        Logger::debugInfo('Hourly import completed. Starting towns import for new world.');
        if (Towns::DataImportTowns($oNewWorld) !== true) {
          throw new \Exception("Towns import failed for new world.");
        }
        Logger::debugInfo('Towns import completed. Starting islands import for new world.');
        if (Towns::DataImportIslands($oNewWorld) !== true) {
          throw new \Exception("Islands import failed for new world.");
        }
        Logger::debugInfo('Towns import completed. Starting elasticsearch import for new world.');
        if (Elasticsearch::DataImportElasticsearch($oNewWorld) !== true) {
          throw new \Exception("Elasticsearch import failed for new world.");
        }

        // Done
        Logger::error('Successful import of new world. Opening world status.');
        $oNewWorld->stopped = 0;
        $oNewWorld->save();
      } catch (\Exception $e) {
        Logger::error('New world import failed with status: '. $e->getMessage());
      }
    }
  }

  // Check world names
  try {
    $aWorlds = \Grepodata\Library\Model\World::where('stopped', '=', 0, 'and')
      ->where('grep_id', 'LIKE', '%'.$Server.'%')
      ->whereRaw('grep_id = name')
      ->orderBy('grep_id', 'desc')
      ->get();

    if (sizeof($aWorlds) <= 0) {
      continue;
    }

    $aWorldsData = WorldData::loadServerWorlds($Server);

    /** @var \Grepodata\Library\Model\World $oWorld */
    foreach ($aWorlds as $oWorld) {
      $bFound = false;

      /** @var \DOMElement[] $aColumns */
      foreach ($aWorldsData as $aColumns) {
        if ($aColumns->length !== 11) {
          continue;
        }

        /** @var \DOMElement $World */
        $World = $aColumns[1]->getElementsByTagName('a')[0];
        $Name = $World->textContent;
        if (is_null($World)) continue;
        $WorldUrl = $World->getAttribute('href');
        $Code = $Server . substr($WorldUrl, strrpos($WorldUrl, '/')+1);

        if ($oWorld->grep_id === $Code && !is_null($Name) && $Name != '') {
          Logger::warning("Found matching name for new world: $Code = $Name");
          $oWorld->name = $Name;
          $oWorld->save();
          $bFound = true;
          continue;
        }
      }

      if ($bFound === false) {
        Logger::warning("Unable to find world name for world: " . $oWorld->grep_id);
      }
    }
  } catch (\Exception $e) {
    Logger::error("Error finding world names: " . $e->getMessage());
  }
}

Logger::debugInfo("Finished successful execution of server detector.");
Common::endExecution(__FILE__, $Start);
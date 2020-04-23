<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started hourly data import");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 2, false); // Override is set to 2 minutes, simultaneous runs are allowed but will not throw an error.

// Find worlds to process
$aWorlds = Common::getAllActiveWorlds();
if ($aWorlds === false) {
  Logger::error("Terminating execution of hourly import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

$UpdatedCount = 0;
$TotalCount = 0;
foreach ($aWorlds as $oWorld) {
  $TotalCount++;

  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

  try {
    //$WorldDayStart = $oWorld->getLastUtcResetTime();
    Logger::debugInfo("Processing world " . $oWorld->grep_id . ". Server time: ".$oWorld->getServerTime()->format('Y-m-d H:i:s'));
    //Logger::debugInfo("Hourly import memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    $bUpdated = Hourly::DataImportHourly($oWorld);
    if ($bUpdated == true) {
      $UpdatedCount++;
    }

  } catch (\Exception $e) {
    Logger::error("Error processing hourly import for world " . $oWorld . " (".$e->getMessage().")");
    continue;
  }
}

Logger::debugInfo("Finished hourly import. Updated ". $UpdatedCount."/".$TotalCount . " worlds, time elapsed: " . gmdate('H:i:s', Carbon::now()->diffInSeconds($Start)));
Common::endExecution(__FILE__, $Start);
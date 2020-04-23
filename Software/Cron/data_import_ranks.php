<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started rank data import");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 20*60, true);

// Find worlds to process
$aWorlds = Common::getAllActiveWorlds();
if ($aWorlds === false) {
  Logger::error("Terminating execution of rank import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

foreach ($aWorlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

  try {
    Logger::debugInfo("Processing world " . $oWorld->grep_id);
    Logger::debugInfo("Rank import memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    Hourly::DataImportRanks($oWorld);
  } catch (\Exception $e) {
    Logger::error("Error processing rank import for world " . $oWorld . " (".$e->getMessage().")");
    continue;
  }
}

Logger::debugInfo("Finished rank import.");
Common::endExecution(__FILE__, $Start);
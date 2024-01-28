<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\IndexV2\Linked;
use Grepodata\Library\Controller\TownOffset;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Towns;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

ini_set('memory_limit', '2G');

Logger::enableDebug();
Logger::debugInfo("Started daily town data import");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 12*60);

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
if ($worlds === false) {
  Logger::error("Terminating execution of daily town import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

// Get unconfirmed link requests
$aUnconfirmedLinks = array();
try {
  $aUnconfirmedLinksRaw = Linked::getAllUnconfirmed();

  // Check expiration
  $Limit = Carbon::now();
  $Limit = $Limit->subDays(30);
  /** @var \Grepodata\Library\Model\IndexV2\Linked $oLinked */
  foreach ($aUnconfirmedLinksRaw as $oLinked) {
    if ($oLinked->created_at < $Limit) {
      // Account link request expired
      Logger::warning("Account link request expired for user ".$oLinked->user_id);
      $oLinked->delete();
    } else {
      $aUnconfirmedLinks[] = $oLinked;
    }
  }
} catch (\Exception $e) {
  Logger::error("Town import: Unable to retrieve unconfirmed account links");
}

// Get town offset hashmap
$aTownOffsets = TownOffset::getAllAsHasmap();

foreach ($worlds as $world) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$world->grep_id) continue;

  try {
    Logger::debugInfo("Processing world " . $world->grep_id);
    Logger::debugInfo("Towns import memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    Towns::DataImportTowns($world, $aUnconfirmedLinks, $aTownOffsets);

    Logger::silly("Finished updating towns.");

  } catch (\Exception $e) {
    Logger::error("Error processing daily town import for world " . $world . " (".$e->getMessage().")");
    continue;
  }

}

Logger::debugInfo("Finished successful execution of daily town import.");
Common::endExecution(__FILE__, $Start);

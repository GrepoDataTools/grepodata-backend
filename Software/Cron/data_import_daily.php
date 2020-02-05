<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Daily;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started daily data import");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 6*60);

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
if ($worlds === false) {
  Logger::error("Terminating execution of daily import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

foreach ($worlds as $world) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$world->grep_id) continue;

  try {
    Logger::debugInfo("Processing world " . $world->grep_id);

    Daily::DataImportDaily($world);

  } catch (\Exception $e) {
    Logger::error("Error processing daily import for world " . $world . " (".$e->getMessage().")");
    continue;
  }

}

Logger::debugInfo("Finished successful execution of daily import.");
Common::endExecution(__FILE__, $Start);
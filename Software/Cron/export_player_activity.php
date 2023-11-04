<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Import\Daily;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started player activity export");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 6*60);

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
if ($worlds === false) {
  Logger::error("Terminating execution of activity export: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

$NumElasticsearchErrors = 0;
$bForceNewConnection = false;
foreach ($worlds as $world) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$world->grep_id) continue;

  try {
    Logger::debugInfo("Processing world " . $world->grep_id);
    Logger::debugInfo("Activity export memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    // Execute
    Hourly::ExportPlayerActivity($world);

  } catch (\Exception $e) {
    Logger::error("Error processing player activity export for world " . $world->grep_id . " (".$e->getMessage().")");
    continue;
  }

}

Logger::debugInfo("Finished successful execution of player activity export.");
Common::endExecution(__FILE__, $Start);

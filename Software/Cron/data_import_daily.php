<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Elasticsearch\Import;
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

$NumElasticsearchErrors = 0;
$bForceNewConnection = false;
foreach ($worlds as $world) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$world->grep_id) continue;

  try {
    Logger::debugInfo("Processing world " . $world->grep_id);
    Logger::debugInfo("Daily import memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    // Check if ES is alive
    $bIndexEnsured = false;
    $NumEsChecks = 0;
    while ($bIndexEnsured == false && $NumEsChecks < 5) {
      try {
        if ($NumElasticsearchErrors > 10) {
          throw new \Exception("ElasicsearchErrorRate > 10");
        }
        if ($NumEsChecks > 5) {
          throw new \Exception("ElasicsearchCurrentChecks > 5");
        }

        $NumEsChecks += 1;
        $IndexName = '';
        $bIndexEnsured = Import::EnsureIndex($IndexName, $bForceNewConnection);
        $bForceNewConnection = false;

      } catch (\Exception $e) {
        $bForceNewConnection = true;
        $NumElasticsearchErrors += 1;
        if (strpos($e->getMessage(), 'No alive nodes found in your cluster') !== false) {
          // Give ES a chance to reboot (systemctl should kick in)
          Logger::error("Daily import pausing for 30 seconds: no alive nodes in ES cluster");
          sleep(30);
        } else {
          Logger::error("CRITICAL: aborting daily import; elasticsearch is offline. " . $e->getMessage());
          Common::endExecution(__FILE__, $Start);
        }
      }
    }

    // Execute
    Daily::DataImportDaily($world);

  } catch (\Exception $e) {
    Logger::error("Error processing daily import for world " . $world->grep_id . " (".$e->getMessage().")");
    continue;
  }

}

Logger::debugInfo("Finished successful execution of daily import.");
Common::endExecution(__FILE__, $Start);

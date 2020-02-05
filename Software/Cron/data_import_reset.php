<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;

require(__DIR__ . '/../config.php');

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

Logger::enableDebug();
Logger::debugInfo("Started daily reset checker");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 2*60, false);

// Find worlds to process
$aWorlds = Common::getAllActiveWorlds();
if ($aWorlds === false) {
  Logger::error("Terminating execution of reset checker: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

foreach ($aWorlds as $oWorld) {
  try {

    // Get last reset time from world object!
    $LastProcessedReset = $oWorld->last_reset_time;

    // if last processed reset was >24 hours ago: start reset
    // Note: a 2 hour delay is added to allow the data to catch up with the inno API
    if (strtotime("-26 hour") > strtotime($LastProcessedReset)) {
      Logger::debugInfo('Processing daily reset for ' . $oWorld->grep_id . '. Last reset: ' . $LastProcessedReset);

      // Check stopped worlds (experimental)
      if (strtotime("-2 day") > strtotime($oWorld->grep_server_time)) {
        Logger::warning('Detected out of sync world: ' . $oWorld . '. Stopping world tomorrow');
      }
      if (strtotime("-3 day") > strtotime($oWorld->grep_server_time)) {
        Logger::error('Stopping world: ' . $oWorld . '. 3+ days of no data!');
        $oWorld->stopped = 1;
        $oWorld->save();
        continue;
      }

      // Update player att/def columns
      Player::resetAttDefScores($oWorld->grep_id);

      // Save reset status
      $oWorld->last_reset_time = $oWorld->getLastUtcResetTime();
      $oWorld->save();

      // Save player history
      Player::processHistoryRecords($oWorld);

      // Save alliance history
      Alliance::processHistoryRecords($oWorld);

    }


  } catch (\Exception $e) {
    Logger::error("CRITICAL: Error processing daily reset for world " . $oWorld . " (".$e->getMessage().")");
    continue;
  }
}

Logger::debugInfo("Finished successful execution of daily reset checker.");
Common::endExecution(__FILE__, $Start);
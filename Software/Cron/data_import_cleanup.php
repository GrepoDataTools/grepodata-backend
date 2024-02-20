<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Intel;
use Grepodata\Library\Model\Operation_log;
use Grepodata\Library\Model\Player;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started data cleanup");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 3*60);

function checkResponse($PlayersDeleted, $Type) {
  if ($PlayersDeleted===null || $PlayersDeleted===false || $PlayersDeleted < 0) {
    Logger::error("Invalid response for delete player history [".$Type."]: " . $PlayersDeleted);
    return 0;
  } else {
    Logger::debugInfo("Removed ".$PlayersDeleted." old player history records with type [".$Type."].");
    return $PlayersDeleted;
  }
}

// check player history
try {
  Logger::debugInfo("Processing player history cleanup");
  $PlayersDeleted = 0;

  $DaysAgo30 = Carbon::now()->subDays(30);
  $DaysAgo60 = Carbon::now()->subDays(60);
  $DaysAgo90 = Carbon::now()->subDays(90);
  $DaysAgo180 = Carbon::now()->subDays(300);

  // === Rank > 1000 [keep only last 30 days]
  // DELETE older than 30 if rank > 1000
  $PlayersDeleted += checkResponse(
    \Grepodata\Library\Model\PlayerHistory::where('created_at', '<', $DaysAgo30, 'and')
      ->where('rank', '>', 1000)
      ->delete(),
    '30days-rank1000');

  // === rank > 500 [keep only last 60 days]
  // DELETE older then 60 if rank > 300
  $PlayersDeleted += checkResponse(
    \Grepodata\Library\Model\PlayerHistory::where('created_at', '<', $DaysAgo60, 'and')
      ->where('rank', '>', 500)
      ->delete(),
    '60days-rank500');


  // === Rank > 150 [keep last 90 days in full, keep every 5 days for the last 180 days and keep every first of month always]
  // DELETE older then 90 if rank > 150
  $PlayersDeleted += checkResponse(
    \Grepodata\Library\Model\PlayerHistory::where('created_at', '<', $DaysAgo90, 'and')
      ->where('rank', '>', 150)
      ->delete(),
    '90days-rank150');

  // Keep partial old records for top 150 players
  // First of month
  $PlayersDeleted += checkResponse(
    \Grepodata\Library\Model\PlayerHistory::where('created_at', '<', $DaysAgo180, 'and')
      ->whereRaw("DAY(created_at) > 10")
      ->delete(),
    '180days-firstofmonth');

  // keep Only mondays
  $PlayersDeleted += checkResponse(
    \Grepodata\Library\Model\PlayerHistory::where('created_at', '<', $DaysAgo90, 'and')
      ->whereRaw("DAYOFWEEK(created_at) != 3")
      ->delete(),
    '90days-5days');


  Logger::debugInfo("Removed a total of ".$PlayersDeleted." old player history records.");
} catch (\Exception $e) {
  Logger::error("CRITICAL: Error processing cleanup (".$e->getMessage().")");
}

// Alliance history
try {
  $AlliancesDeleted = \Grepodata\Library\Model\AllianceHistory::where('created_at', '<', Carbon::now()->subDays(90), 'and')
      ->where('rank', '>', 50)
      ->delete();
  if ($AlliancesDeleted===null || $AlliancesDeleted===false || $AlliancesDeleted < 0) {
    Logger::error("Invalid response for delete alliance history: " . $AlliancesDeleted);
  } else {
    Logger::debugInfo("Removed ".$AlliancesDeleted." old alliance history records.");
  }
} catch (\Exception $e) {
  Logger::error("Exception while deleting alliance history: " . $e->getMessage());
}

// inactive players
try {
  $PlayersDeleted = Player::where('active', '=', 0, 'and')
    ->where('rank', '>', 300, 'and')
    ->where('data_update', '<', Carbon::now()->subDays(90))
    ->delete();
  if ($PlayersDeleted===null || $PlayersDeleted===false || $PlayersDeleted < 0) {
    Logger::error("Invalid response for delete inactive players: " . $PlayersDeleted);
  } else {
    Logger::debugInfo("Removed ".$PlayersDeleted." inactive player records.");
  }
} catch (\Exception $e) {
  Logger::error("Exception while deleting inactive players: " . $e->getMessage());
}

// Logs
try {
  Logger::debugInfo("Processing log records");
  $LogsDeleted = Operation_log::where('level', '>', '2', 'and')
    ->where('level', '<', '10', 'and')
    ->where('created_at', '<', Carbon::now()->subDays(14))
    ->delete();
  $LogsDeleted += Operation_log::where('level', '>', '1', 'and')
    ->where('level', '<', '10', 'and')
    ->where('created_at', '<', Carbon::now()->subDays(90))
    ->delete();
  Logger::debugInfo("Deleted " . $LogsDeleted . " old log records.");
} catch (\Exception $e) {
  Logger::error("CRITICAL: Error cleaning old log messages. " . $e->getMessage());
}

// Index report info
try {
  Logger::debugInfo("Processing indexer report info records");

//  $LogsDeleted = Report::where('city_id', '>', '0', 'and')
//    ->where('report_json', '!=', '', 'and')
//    ->where('updated_at', '<', Carbon::now()->subDays(7))
//    ->update(['report_json' => '', 'report_info' => '']);

  $LogsDeleted = Intel::where('parsing_failed', '!=', true, 'and')
    ->where('report_info', '!=', '', 'and')
    ->where('updated_at', '<', Carbon::now()->subDays(7))
    ->update(['report_info' => '']);

  $LogsDeleted += Intel::where('parsing_failed', '!=', true, 'and')
    ->where('report_json', '!=', '', 'and')
    ->where('updated_at', '<', Carbon::now()->subDays(14))
    ->update(['report_json' => '']);

  $LogsDeleted += Intel::where('updated_at', '<', Carbon::now()->subDays(31))
    ->where('report_json', '!=', '', 'and')
    ->where('report_info', '!=', '', 'and')
    ->update(['report_json' => '', 'report_info' => '']);

  Logger::debugInfo("Updated " . $LogsDeleted . " old report info records.");
} catch (\Exception $e) {
  Logger::error("CRITICAL: Error cleaning indexer report info records: " . $e->getMessage());
}

Logger::debugInfo("Finished execution of data cleaner.");
Common::endExecution(__FILE__, $Start);

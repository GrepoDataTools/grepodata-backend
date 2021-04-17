<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Elasticsearch\Diff;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\World;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started diff processing");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 20*60);

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
if ($worlds === false) {
  Logger::error("Terminating execution of daily import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

$WorldCount = count($worlds);
Logger::warning("Diff worlds: retrieved $WorldCount active worlds from database.");

//$QueryDateLimit = Carbon::now()->subDays(14); // If updated within last 14 days

///** @var $oWorld World */
//foreach ($worlds as $oWorld) {
//  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
//  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;
//
//  try {
//    Logger::debugInfo("Processing diffs for world ".$oWorld->grep_id.".");
//    Logger::debugInfo("Diff script memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");
//
//    // Loop players for this world
//    $aPlayers = Player::allByWorldAndUpdate($oWorld->grep_id, $QueryDateLimit);
//    /** @var \Grepodata\Library\Model\Player $oPlayer */
//    foreach ($aPlayers as $oPlayer) {
//      try {
//        $aHeatmap = Diff::GetAttDiffHeatmapByPlayer($oPlayer);
//
//        if (is_array($aHeatmap) && sizeof($aHeatmap)>=0) {
//          $oPlayer->heatmap = json_encode($aHeatmap);
//          $oPlayer->save();
//        }
//
//      } catch (\Exception $e) {}
//    }
//    unset($aPlayers);
//  } catch (\Exception $e) {
//    Logger::error("Error processing diffs for world " . $oWorld->grep_id . ": " . $e->getMessage());
//  }
//}

try {
  // Cleanup
  Logger::debugInfo("Cleaning old diffs.");
  $Cleaned = Diff::CleanAttDiffRecords("-5 week");
  Logger::debugInfo("Cleaned att diff records: ".json_encode($Cleaned).".");
  $Cleaned = Diff::CleanDefDiffRecords("-5 week");
  Logger::debugInfo("Cleaned def diff records: ".json_encode($Cleaned).".");
} catch (\Exception $e) {
  Logger::error("Error cleaning diffs: " . $e->getMessage());
}

Logger::debugInfo("Finished successful execution of diff processor.");
Common::endExecution(__FILE__, $Start);

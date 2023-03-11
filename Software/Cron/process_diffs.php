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

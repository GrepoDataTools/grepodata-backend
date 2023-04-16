<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\IndexV2\DailyReport;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Elasticsearch\Commands;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started command cleanup");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 3*60);

// delete command history
try {
  $NumCleaned = Commands::CleanCommands();
  Logger::debugInfo("Cleaned commands: ". $NumCleaned);
  DailyReport::increment_persisted_property('persist_property_commands_deleted', $NumCleaned);
} catch (\Exception $e) {
  Logger::error("CRITICAL: Error cleaning indexer report info records: " . $e->getMessage());
}

Logger::debugInfo("Finished execution of command cleaner.");
Common::endExecution(__FILE__, $Start);

<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\IndexV2\Intel;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\DailyReport;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\Indexer\Stats;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started index stat builder");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 3*60);

// Get persisted properties
$oPersistedTowns = DailyReport::where('type', '=', 'persist_property_intel_towns_deleted')->first();
$oPersistedIntel = DailyReport::where('type', '=', 'persist_property_intel_raw_deleted')->first();
$oPersistedShared = DailyReport::where('type', '=', 'persist_property_intel_shared_deleted')->first();

$oStats = new Stats();

// Total reports
$oStats->reports = \Grepodata\Library\Model\IndexV2\Intel::count()
  + ($oPersistedIntel && !empty($oPersistedIntel->data) ? (int) $oPersistedIntel->data:0);

// Total unique towns
$oStats->town_count = \Grepodata\Library\Model\IndexV2\Intel::distinct()->count('town_id')
  + ($oPersistedTowns && !empty($oPersistedTowns->data) ? (int) $oPersistedTowns->data:0);

// Total shared records
$oStats->shared_count = \Grepodata\Library\Model\IndexV2\IntelShared::count()
  + ($oPersistedShared && !empty($oPersistedShared->data) ? (int) $oPersistedShared->data:0);

// Total users
$oStats->user_count = \Grepodata\Library\Model\User::count();

// Total teams created
$oStats->index_count = IndexInfo::count();

$Hours24 = Carbon::now()->subHours(24);
// Unique uploaders in the last 24 hours
$oStats->users_today = \Grepodata\Library\Model\IndexV2\IntelShared::where('created_at', '>', $Hours24)
  ->distinct()
  ->count('user_id');

// Teams active in the last 24 hours
$oStats->teams_today = IndexInfo::where('updated_at', '>', $Hours24)->count();

// Reports indexed in the last 24 hours
$oStats->reports_today = \Grepodata\Library\Model\IndexV2\Intel::where('created_at', '>', $Hours24)->count();

// Save
$oStats->save();

Logger::debugInfo("Finished successful execution of stat builder.");
Common::endExecution(__FILE__, $Start);

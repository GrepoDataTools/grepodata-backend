<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\IndexV2\Intel;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
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

$oStats = new Stats();
$oStats->reports = \Grepodata\Library\Model\IndexV2\Intel::count();
$oStats->town_count = \Grepodata\Library\Model\IndexV2\Intel::distinct()->count('town_id');
$oStats->player_count = \Grepodata\Library\Model\User::count();
$oStats->alliance_count = \Grepodata\Library\Model\IndexV2\IntelShared::count();
$oStats->spy_count = \Grepodata\Library\Model\IndexV2\Intel::where('report_type', '=', 'spy')->count();
$oStats->def_count = \Grepodata\Library\Model\IndexV2\Intel::where('report_type', '=', 'support')->count();
$oStats->att_count = \Grepodata\Library\Model\IndexV2\Intel::where('report_type', '!=', 'spy')->where('report_type', '!=', 'support')->count();
$oStats->fire_count = \Grepodata\Library\Model\IndexV2\Intel::whereNotNull('fireships')->where('fireships', '!=', '')->count();
$oStats->myth_count = \Grepodata\Library\Model\IndexV2\Intel::where('mythical_units', '!=', '[]')->count();
$oStats->index_count = IndexInfo::count();
$oStats->save();

Logger::debugInfo("Finished successful execution of stat builder.");
Common::endExecution(__FILE__, $Start);

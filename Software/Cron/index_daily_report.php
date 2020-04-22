<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\DailyReport;
use Grepodata\Library\Model\Indexer\Stats;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started index daily reporter");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 3*60);

/**
 * This script gathers usage counts from various sources for analytics
 */

use Illuminate\Database\Capsule\Manager as DB;

// Count total indexed reports
$aIndexedPerDay = DB::select( DB::raw("
select count(*) as count, date(created_at) as date 
from Index_report_hash 
where created_at >= date_sub(curdate(), interval 1 year) 
and DATE(created_at) <> DATE(NOW())
group by date"
));
/** @var DailyReport $oReport */
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_reports_per_day'));
$oReport->title = "Total number of indexed reports per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// Count total indexes used per day
$aIndexedPerDay = DB::select( DB::raw("
select count(distinct(index_key)) as count, date(created_at) as date 
from Index_report_hash 
where created_at >= date_sub(curdate(), interval 1 year) 
and DATE(created_at) <> DATE(NOW())
group by date"
));
/** @var DailyReport $oReport */
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_unique_indexes_per_day'));
$oReport->title = "Number of unique indexes used per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// Count total indexing players per day
$aIndexedPerDay = DB::select( DB::raw("
select count(distinct(player_id)) as count, date(created_at) as date 
from Index_report_hash 
where created_at >= date_sub(curdate(), interval 1 year) 
and DATE(created_at) <> DATE(NOW())
group by date"
));
/** @var DailyReport $oReport */
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_unique_uploaders_per_day'));
$oReport->title = "Number of unique uploaders per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// Count total indexed reports from inbox
$aIndexedPerDay = DB::select( DB::raw("
select count(*) as count, date(created_at) as date
from Index_report_hash
where created_at >= date_sub(curdate(), interval 1 year)
and DATE(created_at) <> DATE(NOW())
and index_type = 'inbox'
group by date"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_inbox_per_day'));
$oReport->title = "Number of inbox reports indexed per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// Count total indexed reports from forum
$aIndexedPerDay = DB::select( DB::raw("
select count(*) as count, date(created_at) as date
from Index_report_hash
where created_at >= date_sub(curdate(), interval 1 year)
and DATE(created_at) <> DATE(NOW())
and index_type = 'forum'
group by date"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_forum_per_day'));
$oReport->title = "Number of forum reports indexed per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// index stats agg
$aStats = DB::select( DB::raw("
SELECT reports, spy_count, att_count, def_count, index_count, created_at
FROM `Index_stats` 
WHERE HOUR(created_at) = 0 
AND created_at >= date_sub(curdate(), interval 1 year)
ORDER BY `Index_stats`.`created_at` ASC"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_stats_agg'));
$oReport->title = "Stats aggregation";
$oReport->data = json_encode($aStats);
$oReport->save();

// index error rate
$aStats = DB::select( DB::raw("
select count(*) as count, date(created_at) as date 
from Index_report 
where created_at >= date_sub(curdate(), interval 1 year) 
and city_id = 0 
group by date"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_error_rate'));
$oReport->title = "Indexer report error rate";
$oReport->data = json_encode($aStats);
$oReport->save();

// Service
$aStats = DB::select( DB::raw("
select count(*) as count, date(created_at) as date 
from Operation_log
where created_at >= date_sub(curdate(), interval 1 year) 
and level <= 2 
group by date"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_generic_warning_rate'));
$oReport->title = "GrepoData warning rate";
$oReport->data = json_encode($aStats);
$oReport->save();

// Worlds
$aStats = DB::select( DB::raw("
select substr(world,1,2) as country, count(*) as count
from Index_info
where created_at >= date_sub(curdate(), interval 3 month) 
group by substr(world,1,2)
order by count DESC"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_active_server_bins'));
$oReport->title = "Active indexes by country";
$oReport->data = json_encode($aStats);
$oReport->save();

// Script version
$aStats = DB::select( DB::raw("
SELECT script_version, count(*) as count, date(created_at) as date 
FROM `Index_report` 
WHERE created_at >= date_sub(curdate(), interval 1 year) 
GROUP BY date, script_version"
));
$aData = array();
foreach ($aStats as $oStat) {
  if (!isset($aData[$oStat->date])) {
    $aData[$oStat->date] = array(
      'date' => $oStat->date
    );
  }
  $aData[$oStat->date][$oStat->script_version] = $oStat->count;
}
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_script_version'));
$oReport->title = "Indexer script version";
$oReport->data = json_encode(array_values($aData));
$oReport->save();

Logger::debugInfo("Finished successful execution of daily reporter.");
Common::endExecution(__FILE__, $Start);
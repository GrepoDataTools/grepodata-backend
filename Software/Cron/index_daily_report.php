<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\DailyReport;

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

// Count total indexed reports from inbox
$aIndexedPerDay = DB::select( DB::raw("
select count(*) as count, date(created_at) as date
from Indexer_intel
where created_at >= date_sub(curdate(), interval 1 year)
and DATE(created_at) <> DATE(NOW())
and source_type = 'inbox'
group by date"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_inbox_per_day'));
$oReport->title = "Number of inbox reports indexed per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// Count total indexed reports from forum
$aIndexedPerDay = DB::select( DB::raw("
select count(*) as count, date(created_at) as date
from Indexer_intel
where created_at >= date_sub(curdate(), interval 1 year)
and DATE(created_at) <> DATE(NOW())
and source_type = 'forum'
group by date"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_num_forum_per_day'));
$oReport->title = "Number of forum reports indexed per day";
$oReport->data = json_encode($aIndexedPerDay);
$oReport->save();

// index stats agg (use a static increment for V1 count)
$aStats = DB::select( DB::raw("
SELECT 
	reports, 
	index_count,
	user_count,
   users_today,
   users_week,
   users_month,
   teams_today,
   teams_week,
   teams_month,
   reports_today,
    created_at
FROM `Index_stats` 
WHERE HOUR(created_at) <= 1 
AND created_at >= date_sub(curdate(), interval 1 year)
AND created_at >= '2021-04-23'
ORDER BY `Index_stats`.`created_at` ASC"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_stats_agg'));
$oReport->title = "Stats aggregation";
$oReport->data = json_encode($aStats);
$oReport->save();

// index error rate
$aStats = DB::select( DB::raw("
SELECT date, sum(count) as count FROM (
  select count(*) as count, date(created_at) as date 
  from Indexer_intel
  where created_at >= date_sub(curdate(), interval 3 month) 
  and parsing_error = 1 
  group by date
  
  UNION
  select 0 as count, date(created_at) as date
  from Indexer_intel
  where created_at >= date_sub(curdate(), interval 3 month)
  group by date
) as total
group by date
"
));
$oReport = DailyReport::firstOrNew(array('type' => 'indexer_error_rate'));
$oReport->title = "Indexer report error rate";
$oReport->data = json_encode($aStats);
$oReport->save();

// Service
$aStats = DB::select( DB::raw("
select count(*) as count, date(created_at) as date 
from Operation_log
where created_at >= date_sub(curdate(), interval 3 month) 
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
from Index_overview
where updated_at >= date_sub(curdate(), interval 3 month) 
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
FROM `Indexer_intel` 
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

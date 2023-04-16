<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\DailyReport;
use Illuminate\Database\Capsule\Manager as DB;

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

try {

  // Count total indexed reports from inbox
  Logger::silly("Count total indexed reports from inbox");
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
  Logger::silly("Count total indexed reports from forum");
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
  Logger::silly("index stats agg (use a static increment for V1 count)");
  $aStats = DB::select( DB::raw("
  SELECT 
    reports, 
    index_count,
    shared_count,
    user_count,
    users_today,
    users_week,
    users_month,
    teams_today,
    teams_week,
    teams_month,
    commands_count,
    commands_today,
    commands_users_today,
    commands_users_week,
    commands_users_month,
    commands_teams_today,
    commands_teams_week,
    commands_teams_month,
    reports_today,
    created_at
  FROM `Index_stats` 
  WHERE HOUR(created_at) <= 1 
  AND created_at >= date_sub(curdate(), interval 1 year)
  ORDER BY `Index_stats`.`created_at` ASC"
  ));
  $oReport = DailyReport::firstOrNew(array('type' => 'indexer_stats_agg'));
  $oReport->title = "Stats aggregation";
  $oReport->data = json_encode($aStats);
  $oReport->save();

  // index error rate
  Logger::silly("index error rate");
  $aStats = DB::select( DB::raw("
  SELECT date, sum(count) as count FROM (
    select count(*) as count, date(created_at) as date 
    from Indexer_intel
    where created_at >= date_sub(curdate(), interval 3 week) 
    and parsing_error = 1 
    group by date
    
    UNION
    select 0 as count, date(created_at) as date
    from Indexer_intel
    where created_at >= date_sub(curdate(), interval 3 week)
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
  Logger::silly("Service");
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

  // Hourly new reports in last week
  Logger::silly("Hourly new reports in last week");
  $aStats = DB::select( DB::raw("
  SELECT date_format( created_at, '%Y-%m-%d %H:00:00' ) as datehour, count(*) as count, count(distinct indexed_by_user_id) as usercount
  FROM `Indexer_intel`
  where created_at >= date_sub(curdate(), interval 1 week)
  and created_at < curdate()
  group by datehour
  order by datehour DESC
  "
  ));
  $oReport = DailyReport::firstOrNew(array('type' => 'indexer_hourly_new_reports'));
  $oReport->title = "New reports indexed per hour";
  $oReport->data = json_encode($aStats);
  $oReport->save();

  // Worlds
  Logger::silly("Worlds");
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

//  // Script version
//  Logger::silly("Script version");
//  $aStats = DB::select( DB::raw("
//  SELECT script_version, count(*) as count, date(created_at) as date
//  FROM `Indexer_intel`
//  WHERE created_at >= date_sub(curdate(), interval 1 year)
//  GROUP BY date, script_version"
//  ));
//  $aData = array();
//  foreach ($aStats as $oStat) {
//    if (!isset($aData[$oStat->date])) {
//      $aData[$oStat->date] = array(
//        'date' => $oStat->date
//      );
//    }
//    $aData[$oStat->date][$oStat->script_version] = $oStat->count;
//  }
//  $oReport = DailyReport::firstOrNew(array('type' => 'indexer_script_version'));
//  $oReport->title = "Indexer script version";
//  $oReport->data = json_encode(array_values($aData));
//  $oReport->save();

} catch (\Exception $e) {
  Logger::error("Index daily reporting failed: " . $e->getMessage() . " - " . $e->getTraceAsString());
}

Logger::debugInfo("Finished successful execution of daily reporter.");
Common::endExecution(__FILE__, $Start);

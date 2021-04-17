<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;

\Grepodata\Library\Logger\Logger::debugInfo("Scrolling");

//$Limit = Carbon::now();
//$Limit = $Limit->subDays(30);
//foreach (\Grepodata\Library\Model\Indexer\ReportId::where('created_at', '>', $Limit)->cursor() as $oHash) {
//  if ($oHash->index_report_id > 0 && $oHash->player_id > 0) {
//    $oReport = \Grepodata\Library\Model\Indexer\Report::where('id', '=', $oHash->index_report_id)->first();
//    if ($oReport !== false && $oReport->type === 'inbox') {
//      $oHash->index_type = 'inbox';
//    }
//  }
//}

\Grepodata\Library\Logger\Logger::debugInfo("Done");
$t=2;

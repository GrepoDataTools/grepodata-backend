<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Controller\World;

//$hash = "980666181";
//$index = "jjfk1pnd";
//$oReportHash = \Grepodata\Library\Model\Indexer\ReportId::where('index_key', '=', $index, 'and')
//  ->where('report_id', '=', $hash)
//  ->first();
//$oReport = \Grepodata\Library\Model\Indexer\Report::where('id', '=', $oReportHash->index_report_id)->first();

//$Id="1124539";
$Id="1124570";
$oReport = \Grepodata\Library\Model\IndexV2\Intel::where('id', '=', $Id)->first();

try {
  $html = \Grepodata\Library\Indexer\Helper::JsonToHtml($oReport, true);
  $url = \Grepodata\Library\Indexer\Helper::reportToImage($html, $oReport, 'testing');
} catch (Exception $e) {
  $t=2;
}
$t=2;

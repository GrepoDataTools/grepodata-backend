<?php

namespace Grepodata\Cron;

use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Indexer\ForumParser;
use Grepodata\Library\Indexer\InboxParser;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\Report;
use Grepodata\Library\Model\Indexer\ReportId;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

/** @var Report $Report */
$hash_id = "-413308736";
$ReportId = ReportId::where('report_id','=',$hash_id)->first();
$Report = Report::where('id','=',$ReportId->index_report_id)->first();


// TODO:

//$Fingerprint = "07e58f8c4c7ebf7d8cc70b3b0cde5215";
//$Report = Report::where('fingerprint','=',$Fingerprint)->first();

//$Report = Report::where('id','=','721724')->first();

$CityId = $Report->city_id;
$aReportData = (array) json_decode($Report->report_json, true);


// ==== Retry for index
//$index = "";
//$player_id='345915';
//$Reports = Report::where('index_code','=',$index,'and')->where('city_id','=',0)->get();
//foreach ($Reports as $Report) {
//  try {
////  $t=ForumParser::ParseReport($Report->index_code, $aReportData, '', '', 'de');
//    $CityId = $Report->city_id;
//    $aReportData = (array) json_decode($Report->report_json, true);
//    $t=InboxParser::ParseReport($Report->index_code, $aReportData, $Report->report_poster, $player_id,'',$Report->fingerprint);
//  } catch (\Exception $e) {
//    $f=2;
//  }
//}

$Index = IndexInfo::firstOrFail($Report->index_code);

$aParsed = Common::debugIndexer($Report, $Index);

$a=2;

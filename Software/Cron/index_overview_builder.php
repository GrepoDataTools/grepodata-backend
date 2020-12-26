<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\IndexV2\IndexOverview;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started index overview builder");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 1*60, false);

// Find worlds to process
/** @var IndexInfo $aIndex $aIndex */
$aIndex = Common::getAllActiveIndexes();
if ($aIndex === false) {
  Logger::error("Terminating execution of index overview builder: Error retrieving index keys from database.");
  Common::endExecution(__FILE__);
}

/** @var IndexInfo $oIndex */
$Count = 0;
foreach ($aIndex as $oIndex) {
  try {
    if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='') {
      if ($argv[1]===$oIndex->key_code) {
        IndexOverview::buildIndexOverview($oIndex);
      }
    } else {
      if ($oIndex->new_report === 1) {
        IndexOverview::buildIndexOverview($oIndex);
        $Count++;
        $oIndex->new_report = 0;
        $oIndex->save();
      }
    }


  } catch (\Exception $e) {
    Logger::error("Error building overview for index " . $oIndex->key_code . ": " . $e->getMessage());
  }
}

Logger::debugInfo("Finished successful execution of index overview builder for ".$Count." indexes.");
Common::endExecution(__FILE__, $Start);
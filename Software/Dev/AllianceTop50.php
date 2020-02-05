<?php

namespace Grepodata\Cron;

use Grepodata\Library\Controller\AllianceHistory;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

$world_id = "nl66";

//$oWorld = World::getWorldById($world_id);

$aHistories = \Grepodata\Library\Model\AllianceHistory::where('world', '=', $world_id, 'and')
  ->where('rank','<',60)
  ->orderBy('created_at', 'desc')
  ->get();

$aGroups = array();
foreach ($aHistories as $aHistory) {
  if ($aHistory->members < 1) {
    continue;
  }
  if (key_exists($aHistory->date, $aGroups)) {
    $aGroups[$aHistory->date][] = $aHistory->points;
  } else {
    $aGroups[$aHistory->date] = array($aHistory->points);
  }
}

$aPoints = array();
foreach ($aGroups as $Date => $aGroup) {
  arsort($aGroup);
  $aPoints[$Date] = array_sum(array_slice($aGroup, 0, 50));
}

$fp = fopen('file.csv', 'w');

foreach ($aPoints as $Date => $points) {
  fputcsv($fp, array($Date, $points));
}

fclose($fp);

$a=2;
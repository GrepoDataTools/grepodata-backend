<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\Stats;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started index stat builder");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 3*60);

// Find worlds to process
$aIndex = Common::getAllActiveIndexes();
if ($aIndex === false) {
  Logger::error("Terminating execution of stat builder: Error retrieving index keys from database.");
  Common::endExecution(__FILE__);
}

$Total_reports = 0;
$Index_count = sizeof($aIndex);
$Town_count = 0;
$Player_count = 0;
$Alliance_count = 0;
$Spy_count = 0;
$Att_count = 0;
$Def_count = 0;
$Fire_count = 0;
$Myth_count = 0;

foreach ($aIndex as $oIndex) {
  // Get all cities
  $Key = $oIndex->key_code;
  $World = $oIndex->world;
  $aCityRecords = CityInfo::allByKey($Key);

  // Found
  $aFoundTowns = array();
  $aFoundPlayers = array();
  $aFoundAlliances = array();

  $Total_reports = $Total_reports + sizeof($aCityRecords);
  foreach ($aCityRecords as $oCity) {

    // Update town
    try {

      if ($oCity['report_type'] === 'spy') {
        $Spy_count++;
      } else if ($oCity['report_type'] === 'support') {
        $Def_count++;
      } else {
        $Att_count++;
      }

      if (!in_array($oCity->town_id, $aFoundTowns)) {
        $aFoundTowns[] = $oCity->town_id;
        $Town_count++;
      }
      if (!in_array($oCity->player_id, $aFoundPlayers)) {
        $aFoundPlayers[] = $oCity->player_id;
        $Player_count++;
      }
      if (!in_array($oCity->alliance_id, $aFoundAlliances)) {
        $aFoundAlliances[] = $oCity->alliance_id;
        $Alliance_count++;
      }
      
      // Fire/myth count
      // Fireships
      if (isset($oCity['fireships']) && $oCity['fireships'] !== null && $oCity['fireships'] !== "" && sizeof($oCity['fireships'])>0) {
        $bInvalid = false;
        try {
          $num = $oCity['fireships'];
          if (strpos($num, '(') !== false) {
            $num = substr($num, 0, strpos($num, '('));
          }
          if ($num <= 11) {
            $bInvalid = true;
          }
        } catch (Exception $e) {}

        if (!$bInvalid) {
          $Fire_count++;
        }
      }

      // Myths
      $Myths = json_decode($oCity['mythical_units']);
      if (isset($Myths) && $Myths !== null && $Myths !== "" && sizeof($Myths)>0) {
        $bHasMyth = false;
        foreach ($Myths as $Type => $Myth) {
          if ($Myth !== "") {
            $bHasMyth = true;
          }
        }
        if ($bHasMyth) {
          $Myth_count++;
        }
      }
      
    } catch (\Exception $e) {}
  }

}

$oStats = new Stats();
$oStats->reports = $Total_reports;
$oStats->town_count = $Town_count;
$oStats->player_count = $Player_count;
$oStats->alliance_count = $Alliance_count;
$oStats->spy_count = $Spy_count;
$oStats->att_count = $Att_count;
$oStats->def_count = $Def_count;
$oStats->fire_count = $Fire_count;
$oStats->myth_count = $Myth_count;
$oStats->index_count = $Index_count;
$oStats->save();

Logger::debugInfo("Finished successful execution of stat builder.");
Common::endExecution(__FILE__, $Start);
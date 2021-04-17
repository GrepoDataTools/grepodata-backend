<?php

namespace Grepodata\Cron;

use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Intel;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

$town_id = "";
$index_id = "";

$aTowns = Intel::where("town_id","=", $town_id, "and")
  ->where("index_key", "=", $index_id, "and")
  ->where("report_type", "!=", "attack_on_conquest")
  ->orderBy('created_at', 'asc')
  ->get();

$aBuildings = array();
/** @var Intel $oTown */
foreach ($aTowns as $oTown) {
  $Build = $oTown->buildings;
  if ($Build != null && $Build != "" && $Build != "[]") {
    $aBuild = (array) json_decode($Build);
    if (is_array($aBuild) && sizeof($aBuild) > 0) {
      foreach ($aBuild as $Name => $Value) {
        $aBuildings[$Name] = $Value;
      }
    }
  }
}

$a=2;

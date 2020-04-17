<?php

namespace Grepodata\Cron;

use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

$source_world = "nl79";
$target_index = "";

$aIndex = IndexInfo::where('world', '=', $source_world)->get();
$Keys = array();
/** @var IndexInfo $oIndex */
foreach ($aIndex as $oIndex) {
  $Keys[] = $oIndex->key_code;
}

$aTowns = City::whereIn('index_key', $Keys)
  ->get();

$aExists = array();

/** @var City $oTown */
foreach ($aTowns as $oTown) {
  try {
    $oCity = $oTown->replicate();
    $oCity->index_key=$target_index;

    /** @var City $oTownIn */
    $bExists = false;
    foreach ($aExists as $oTownIn) {
      if (!$bExists && $oCity->town_id === $oTownIn->town_id && $oCity->report_date === $oTownIn->report_date && $oCity->report_type === $oTownIn->report_type) {
        $bExists = true;
      }
    }

    if ($bExists === false) {
      $aExists[] = $oCity;
      $r=$oCity->save();
      $t=2;
    }
  } catch (\Exception $e) {
    $t=2;
    echo $e->getMessage();
  }
}

$a=2;
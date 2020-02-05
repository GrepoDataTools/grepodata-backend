<?php

namespace Grepodata\Cron;

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

$source_id = "";
$source_id2 = "";
$target_id = "";

$aTowns_in = City::where("index_key", "=", $source_id)
  ->orderBy('created_at', 'asc')
  ->get();
$aTowns = City::where("index_key", "=", $source_id2)
  ->orderBy('created_at', 'asc')
  ->get();

/** @var City $oTown */
foreach ($aTowns as $oTown) {
  try {
    $oCity = $oTown->replicate();
    $oCity->index_key=$target_id;

    /** @var City $oTownIn */
    $bExists = false;
    foreach ($aTowns_in as $oTownIn) {
      if (!$bExists && $oCity->town_id === $oTownIn->town_id && $oCity->report_date === $oTownIn->report_date && $oCity->report_type === $oTownIn->report_type) {
        $bExists = true;
      }
    }

    if ($bExists === false) {
      $r=$oCity->save();
      $t=2;
    }
  } catch (\Exception $e) {
    $t=2;
    echo $e->getMessage();
  }
}

$a=2;
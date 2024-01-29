<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../../config.php');

use Grepodata\Library\Controller\Island;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\TownOffset;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Logger\Logger;

/**
 * This is a one time script to add absolute coordinates to old town data. Going forward every new town has these coordinates added during import
 */

// Find worlds to process
$aWorlds = Common::getAllActiveWorlds();
if ($aWorlds === false) {
  Logger::error("Terminating execution of hourly import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

// Prepare town offset hashmap
$aOffsetMap = TownOffset::getAllAsHasmap();

$j=0;
foreach ($aWorlds as $oWorld) {
  $j++;
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1] != null && $argv[1] != '' && $argv[1] != $oWorld->grep_id) continue;

  // Get all towns for world
  $aTowns = Town::allByWorld($oWorld->grep_id);

  $aIslandData = InnoData::loadIslandData($oWorld->grep_id);
  $aIslandTypeMap = array();
  foreach ($aIslandData as $aIsland) {
    $aIslandTypeMap[$aIsland['island_x']."_".$aIsland['island_y']] = $aIsland['island_type'];
  }
  unset($aIslandData);

  $i=0;
  $corrupt=0;
  foreach ($aTowns as $oTown) {
    $i++;
    if ($i % 500 == 0) {
      echo $oWorld->grep_id . " " . $j . "/" . count($aWorlds) . " -> towns: " . $i . "/" . count($aTowns) . " (corrupt: ".$corrupt.")" . PHP_EOL;
    }

    if (is_null($oTown->island_x) || is_null($oTown->island_y) || is_null($oTown->island_i)) {
      $corrupt++;
      continue;
    }

    // Get island
    $IslandId = $oTown->island_x . "_" . $oTown->island_y;
    if (!key_exists($IslandId, $aIslandTypeMap)) {
      echo "Island not found: [$oTown->grep_id] -> $oTown->island_x, $oTown->island_y, $oTown->world".PHP_EOL;
      $corrupt++;
      continue;
    }

    $IslandType = $aIslandTypeMap[$IslandId];
    $oTown->island_type = $IslandType;

    // Get offset
    $OffsetKey = TownOffset::getKeyForTown($oTown);
    $oOffset = $aOffsetMap[$OffsetKey];

    // Calculate abs x y
    $aCalculated = TownOffset::getAbsoluteTownCoordinates($oTown, $oOffset);

    // Update town in DB
    $oTown->absolute_x = $aCalculated[0];
    $oTown->absolute_y = $aCalculated[1];
    $oTown->save();
  }
}

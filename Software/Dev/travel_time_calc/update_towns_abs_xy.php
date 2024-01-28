<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../../config.php');

use Grepodata\Library\Controller\Island;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\TownOffset;
use Grepodata\Library\Cron\Common;
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

foreach ($aWorlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1] != null && $argv[1] != '' && $argv[1] != $oWorld->grep_id) continue;

  // Get all towns for world
  $aTowns = Town::allByWorld($oWorld->grep_id);

  $aIslandCache = array();
  $i=0;
  foreach ($aTowns as $oTown) {
    $i++;
    if ($i % 500 == 0) {
      echo $oWorld->grep_id . " -> " . $i . "/" . count($aTowns) . PHP_EOL;
    }

    // Get island
    $IslandId = $oTown->island_x . "_" . $oTown->island_y . "_" . $oTown->world;
    if (key_exists($IslandId, $aIslandCache)) {
      $oIsland = $aIslandCache[$IslandId];
    } else {
      $oIsland = Island::firstByXY($oTown->island_x, $oTown->island_y, $oTown->world);
      if ($oIsland == false) {
        echo "Island not found: [$oTown->grep_id] -> $oTown->island_x, $oTown->island_y, $oTown->world".PHP_EOL;
        continue;
      }
      $aIslandCache[$IslandId] = $oIsland;
    }

    // Get offset
    $OffsetKey = TownOffset::getKeyForTown($oTown, $oIsland);
    $oOffset = $aOffsetMap[$OffsetKey];

    // Calculate abs x y
    $aCalculated = Island::getAbsoluteTownCoordinates($oIsland, $oOffset);

    // Update town in DB
    $oTown->absolute_x = $aCalculated[0];
    $oTown->absolute_y = $aCalculated[1];
    $oTown->island_type = $oIsland->island_type;
    $oTown->save();
  }
}

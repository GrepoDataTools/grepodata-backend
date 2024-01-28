<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../../config.php');

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Town;

// Depending on the direction of the town, these offsets should apply (not sure if this is only for drawing or also for distance)
$aDirectionCorrections = array(
    'ne' => array(17, 11),
    'se' => array(15, 13),
    'nw' => array(9, 14),
    'sw' => array(10, 13),
);

$mapdatastr = file_get_contents("MapTiles.json");
$aMapData = json_decode($mapdatastr, true);

$IslandIdx = 0;
foreach ($aMapData as $IslandOffset) {

  if ($IslandOffset != null) {

    $TownIdx = 0;
    foreach ($IslandOffset['town_offsets'] as $TownOffset) {

      $oOffset = new \Grepodata\Library\Model\TownOffset();
      $oOffset->island_type_idx = $IslandIdx; // Island->island_type
      $oOffset->island_img = $IslandOffset['img'];
      $oOffset->island_width = $IslandOffset['width'];
      $oOffset->island_height = $IslandOffset['height'];
      $oOffset->centering_offset_x = $IslandOffset['centering_offset_x'] ?? 0;
      $oOffset->centering_offset_y = $IslandOffset['centering_offset_y'] ?? 0;
      $oOffset->town_offset_idx = $TownIdx; // Town->island_i
      $oOffset->town_offset_x = $TownOffset['x'];
      $oOffset->town_offset_y = $TownOffset['y'];
      $oOffset->town_offset_fx = $TownOffset['fx'];
      $oOffset->town_offset_fy = $TownOffset['fy'];
      $oOffset->town_dir_offset_x = $aDirectionCorrections[$TownOffset['dir']][0];
      $oOffset->town_dir_offset_y = $aDirectionCorrections[$TownOffset['dir']][1];
      $oOffset->town_dir = $TownOffset['dir'];
      $oOffset->save();

      $TownIdx++;
    }
  }

  $IslandIdx++;
}

$t=2;

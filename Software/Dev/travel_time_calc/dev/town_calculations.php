<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../../../config.php');

use Carbon\Carbon;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Town;

$aTestCases = array(
    840 => array(66573, 61126), # grcrt: 66573, 61126
    1106 => array(66570, 61251), # grcrt: 66570, 61241
    1121 => array(66510, 61154), # grcrt: 66510, 61144
    1508 => array(66967, 61163), # grcrt: 66967, 61153
    3317 => array(66763, 61686), # grcrt: 66763, 61676

    // let links = document.querySelectorAll('a.tile.default');
    // links.forEach(link => {
    //    console.log(link.id+" => array("+link.style.left+", "+link.style.top+")");
    //});
    3122 => array(67175, 62131),
//    3317 => array(66763, 61686),
    2962 => array(67821, 62337),
//    840 => array(66573, 61126),
    3139 => array(66428, 61387),
    909 => array(66186, 61155),
    930 => array(66182, 61335),
    763 => array(66210, 61179),
    612 => array(66445, 61110),
    1271 => array(66354, 61368),
    2271 => array(67946, 62138),
    1044 => array(66496, 61397),
    1049 => array(66232, 61360),
    2315 => array(66501, 61330),
    1050 => array(66278, 61391),
//    1106 => array(66570, 61251),
    1046 => array(66169, 61102),
//    1121 => array(66510, 61154),
    1137 => array(66565, 61300),
    1149 => array(66540, 61204),
    1273 => array(66238, 61032),
    1285 => array(67775, 62041),
    1310 => array(67903, 61874),
    1332 => array(67936, 61938),
    1336 => array(67832, 62114),
    2897 => array(67765, 61967),
    2367 => array(66291, 61054),
    2240 => array(65966, 61761),
    939 => array(66912, 60923),
    931 => array(66984, 60908),
    797 => array(67193, 60828),
    1519 => array(67232, 61211),
    940 => array(66794, 61078),
    1139 => array(66858, 60951),
    575 => array(66849, 61173),
    2540 => array(67288, 61180),
//    1508 => array(66967, 61163),
    1405 => array(67355, 60913),
    1364 => array(67435, 60985),
    1365 => array(67438, 61050),
    1367 => array(67413, 60949),
    1375 => array(66739, 61056),
    1384 => array(67003, 61217),
    1386 => array(67436, 61128),
    1396 => array(67377, 61162),
    1400 => array(66792, 61002),
);

// Depending on the direction of the town, these offsets should apply (not sure if this is only for drawing or also for distance)
// NOTE: These offsets are only relevant for map position, they are not used by the distance calculation
//$aDirectionCorrections = array(
//    'ne' => array(17, 11),
//    'se' => array(15, 13),
//    'nw' => array(9, 14),
//    'sw' => array(10, 13),
//);

$mapdatastr = file_get_contents("MapTiles.json");
$aMapData = json_decode($mapdatastr, true);

function getAbsoluteCoordinates(Town $Town)
{
    global $aMapData;
//    global $aDirectionCorrections;
    $oIsland = \Grepodata\Library\Controller\Island::firstByXY($Town->island_x, $Town->island_y, $Town->world);
    if ($oIsland == false) {
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Island not found: $Town->island_x, $Town->island_y, $Town->world");
    }
    if (!isset($oIsland->island_type) || !isset($oIsland->island_x) || !isset($aMapData[$oIsland->island_type])) {
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException("No map data for island with type: $oIsland->island_type, $Town->world");
    }
    $aIslandData = $aMapData[$oIsland->island_type];
    //echo "Island: $Town->name: ".json_encode($aIslandData). "\n";
    if (!isset($aIslandData['town_offsets']) || !isset($aIslandData['town_offsets'][$Town->island_i])) {
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException("No town offset data for town with type $Town->island_i and island type $oIsland->island_type, $Town->world");
    }
    $aTownOffset = $aIslandData['town_offsets'][$Town->island_i];
    $IslandAbsX = 128 * $oIsland->island_x;
    $IslandAbsY = 128 * $oIsland->island_y;
    $TownAbsX = $IslandAbsX + $aTownOffset['x'];
    $TownAbsY = $IslandAbsY + $aTownOffset['y'];

//    $DirectionCorrection = $aDirectionCorrections[$aTownOffset['dir']];
//    $TownAbsX += $DirectionCorrection[0];
//    $TownAbsY += $DirectionCorrection[1];

    return array(
//        $TownAbsX + 10, // 10 = default
//        $TownAbsY + 10 + 64, // 10 = default, 64 = half of ytile size
        $TownAbsX, //
        $oIsland->island_x % 2 == 1 ? $TownAbsY+64 : $TownAbsY, // add 64 (= half of ytile size) if islandx is odd
        "[".implode(";", $aTownOffset)."]",
        "[".implode(";", array($oIsland->island_type, $Town->island_i))."]"
    );
}

foreach ($aTestCases as $TownId => $aTest) {
    $oTown = \Grepodata\Library\Controller\Town::firstOrFail($TownId, 'nl112');

    $aCalculated = getAbsoluteCoordinates($oTown);

    echo "TownID " . $TownId . ", Expected: " .implode(",", $aTest).", Calculated: ".implode(",", $aCalculated).", Delta X: ".($aCalculated[0]-$aTest[0]).", Delta Y: ".($aCalculated[1]-$aTest[1]).PHP_EOL;

    $t=2;

}


$t=2;

<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../../config.php');

use Carbon\Carbon;
use Grepodata\Library\Controller\World;

set_time_limit(0);

$zoom = 1;
$cut = 1; // cut outer edges of map with this factor
$originalSize = 1000;
$scaledSize = ceil(($originalSize / $cut) / $zoom);

function mapCoor($c) {
  global $zoom, $cut, $originalSize;
  // Apply edge cut
  $edgeCut = ($originalSize / $cut) / 2;
  $c = $c - $edgeCut;
  if ($c < 0 || $c > $originalSize / $cut) {
    // coor lies outside edge cut
    return false;
  }
  // Apply zoom
  return ceil($c / $zoom);
}

$img = imageCreateTrueColor($scaledSize, $scaledSize);


$black = imagecolorallocate($img, 0, 0, 0);
$red = imageColorAllocate($img, 255, 0, 0);
$white = imagecolorallocate($img, 255, 255, 255);
$txt = "Hello World";
$font = "C:\Windows\Fonts\arial.ttf";

$World = 'nl66';

$aStaticColors = array(
  imagecolorallocate($img, 0, 0, 0),
  imagecolorallocate($img, 0, 0, 255), // rank 1
  imagecolorallocate($img, 255, 0, 0), // rank 2
  imagecolorallocate($img, 0, 255, 0), // etc
  imagecolorallocate($img, 224, 224, 76), // etc
  imagecolorallocate($img, 224, 76, 224), // etc
  imagecolorallocate($img, 76, 224, 224), // etc
  imagecolorallocate($img, 224, 42, 191), // etc
  imagecolorallocate($img, 128, 97, 91), // etc
);

//$oIslands = \Grepodata\Library\Model\Island::where('world', '=', $World)->get();
$oTowns = \Grepodata\Library\Model\Town::where('world', '=', $World)->get();
$aPlayers = array();
foreach (\Grepodata\Library\Model\Player::where('world', '=', $World)->cursor() as $oPlayer) {
  $aPlayers[$oPlayer->grep_id] = $oPlayer;
}

// island alliance heatmap
printf("Creating alliance heatmap\n");
$imgSize = 1000;
$islandMap = array_fill(0, $imgSize, array_fill(0, $imgSize, array()));
foreach ($oTowns as $oTown) {
  if ($oTown->player_id > 0 && $oTown->island_x != null && $oTown->island_y != null && $oTown->points > 175 && isset($aPlayers[$oTown->player_id])) {
    $oPlayer = $aPlayers[$oTown->player_id];
    if (isset($islandMap[$oTown->island_x][$oTown->island_y][$oPlayer->alliance_id])) {
      $islandMap[$oTown->island_x][$oTown->island_y][$oPlayer->alliance_id] += 1;
    } else {
      $islandMap[$oTown->island_x][$oTown->island_y][$oPlayer->alliance_id] = 1;
    }
  }
}
unset($aPlayers);
unset($oTowns);


$aAlliances = array();
foreach (\Grepodata\Library\Model\Alliance::where('world', '=', $World)->cursor() as $oAlliance) {
  if ($oAlliance->rank > 15) {
    continue;
  }
  $oAlliance['color'] = imagecolorallocate($img, rand(0,255), rand(0,255), rand(0,255));
  if (isset($aStaticColors[$oAlliance->rank])) {
    $oAlliance['color'] = $aStaticColors[$oAlliance->rank]; // Assign high contrast colors to top alliances
  }
  $aAlliances[$oAlliance->grep_id] = $oAlliance;
}

// Convert to color
printf("Creating color map\n");
$aAlliancesInImage = array();
$total = 0;
$colorMap = array_fill(0, $imgSize, array_fill(0, $imgSize, array('color' => null, 'touched' => false, 'actual' => false)));
for ($x = 0; $x < $imgSize; ++$x) {
  for ($y = 0; $y < $imgSize; ++$y) {
    $MostOwned = 0;
    $owner = null;
    foreach ($islandMap[$x][$y] as $AllianceId => $NumOwned) {
      if ($NumOwned > $MostOwned) {
        $MostOwned = $NumOwned;
        $owner = $AllianceId;
      }
    }
    if ($MostOwned > 2 && isset($aAlliances[$owner])) {
      $total += 1;
      $colorMap[$x][$y] = array('color' => $aAlliances[$owner]['color'], 'touched' => true, 'actual' => true);
      touchMap($colorMap, $x, $y);

      if (isset($aAlliancesInImage[$owner])) {
        $aAlliancesInImage[$owner] += 1;
      } else {
        $aAlliancesInImage[$owner] = 1;
      }
    }
  }
}

// Create img
printf("Creating image\n");
$img = imagecreatetruecolor($imgSize, $imgSize);
$numTouched = 0;
for ($x = 0; $x < $imgSize; ++$x) {
  for ($y = 0; $y < $imgSize; ++$y) {
    if ($colorMap[$x][$y]['touched'] == true) {
      $numTouched++;
      if ($numTouched % 5000 == 0) {
        printf("Num touched: $numTouched\n");
      }
      $Color = $colorMap[$x][$y]['color'];
      if ($Color == null) {
        // find nearest neighbour
        $Color = nearestPixelColor($x, $y, $colorMap);
      }
      if ($Color != null) {
        imagesetpixel($img, $x, $y, $Color);
      }
    }
  }
}

// Resize
$image = imagecreatetruecolor($imgSize, $imgSize);
$zoom = 300; //px cut from edge
imagecopyresampled($image, $img, 0, 0, $zoom, $zoom, $imgSize, $imgSize, $imgSize-($zoom*2), $imgSize-($zoom*2));
$img = $image;

// Legend
arsort($aAlliancesInImage);
imagefilledrectangle($img, 5, 5, 250, min(310, sizeof($aAlliancesInImage)*20+10), $white);
$offset = 0;
foreach ($aAlliancesInImage as $Id => $Count) {
  if ($offset > 280) continue;
  imagefilledrectangle($img, 10, 13+$offset, 20, 23+$offset, $aAlliances[$Id]['color']);
  imagettftext($img, 14, 0, 25, 25+$offset, $aAlliances[$Id]['color'], $font, ceil(($Count / $total)*100) . "%  "
    . substr($aAlliances[$Id]['name'], 0, 18) . (strlen($aAlliances[$Id]['name'])>18?'..':''));
  $offset += 20;
}

// Date
imagefilledrectangle($img, 0, $scaledSize-40, $scaledSize, $scaledSize, $black);
imagettftext($img, 24, 0, 5, $scaledSize-5, $white, $font, Carbon::now()->format("d-m-Y"));
imagepng($image, 'now_'.$World.'.png');

function touchMap(&$pixelArray, $xi, $yi) {
  $touchrange = 5;
  for ($x = -$touchrange; $x <= $touchrange; ++$x) {
    for ($y = -$touchrange; $y <= $touchrange; ++$y) {
      if (abs($x)+abs($y)<=ceil(($touchrange+(0.6*$touchrange)))){
        $pixelArray[$xi + $x][$yi + $y]['touched'] = true;
      }
    }
  }
}

function nearestPixelColor($xi, $yi, $pixelArray) {
  // Search a 10x10 window for the nearest value
  $searchWindow = 6;
  $closest = 1000;
  $closestValue = $pixelArray[$xi][$yi]['color'];
  for ($x = $xi-$searchWindow; $x < $xi+$searchWindow; ++$x) {
    for ($y = $yi-$searchWindow; $y < $yi+$searchWindow; ++$y) {
      if (isset($pixelArray[$x][$y]) && isset($pixelArray[$x][$y]['actual']) && $pixelArray[$x][$y]['actual']==true) {
        $d = sqrt(pow($x - $xi, 2) + pow($y - $yi, 2));
        if ($d < $closest) {
          $closest = $d;
          $closestValue = $pixelArray[$x][$y]['color'];
        }
      }
    }
  }
  return $closestValue;
}

$t=2;
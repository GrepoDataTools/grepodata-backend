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

//$oIslands = \Grepodata\Library\Model\Island::where('world', '=', $World)->get();
//$oTowns = \Grepodata\Library\Model\Town::where('world', '=', $World)->get();

$aAlliances = \Grepodata\Library\Model\Alliance::where('world', '=', $World)
  ->orderBy('grep_id', 'asc')
  ->get();
$aAllianceIds = array();
$aStaticColors = array(
  imagecolorallocate($img, 0, 0, 0),
  imagecolorallocate($img, 0, 0, 255), // rank 1
  imagecolorallocate($img, 255, 0, 0), // rank 2
  imagecolorallocate($img, 0, 255, 0), // etc
  imagecolorallocate($img, 224, 224, 76), // etc
  imagecolorallocate($img, 224, 76, 224), // etc
  imagecolorallocate($img, 76, 224, 224), // etc
  );
/** @var \Grepodata\Library\Model\Alliance $oAlliance */
foreach ($aAlliances as $oAlliance) {
//  $oAlliance['color'] = imagecolorallocatealpha($img, rand(0,255), rand(0,255), rand(0,255), 0);
  $oAlliance['color'] = imagecolorallocate($img, rand(0,255), rand(0,255), rand(0,255));
  if (isset($aStaticColors[$oAlliance->rank]) && Carbon::parse($oAlliance->updated_at) > Carbon::now()->subDays(30)) {
    $oAlliance['color'] = $aStaticColors[$oAlliance->rank]; // Assign high contrast colors to top alliances
  }
  $oAlliance['conq_count'] = 0;
  if ($oAlliance->rank <= 30 || $oAlliance->grep_id < 1000) {
    // only top 10
    $aAllianceIds[$oAlliance->grep_id] = $oAlliance;
  }
}
$aAllianceIds[0] = array('color'=>$black); // default
$aPlayerAllianceIds = array();

$imgSize = 1000;
$pixelArray = array_fill(0, $imgSize, array_fill(0, $imgSize, array('id'=>0,'fade'=>0,'touched'=>false,'actual'=>false, 'p_id'=>0)));

$batchSize = 100;
$count = 0;
$batch = 0;
/** @var \Grepodata\Library\Model\Conquest $oConquest */
foreach (\Grepodata\Library\Model\Conquest::where('world', '=', $World)->orderBy('time', 'asc')->cursor() as $oConquest) {
//  if ($oConquest->n_a_id == $oConquest->o_a_id || !key_exists($oConquest->n_a_id, $aAllianceIds)) {
  if (!key_exists($oConquest->n_a_id, $aAllianceIds)) {
    // ignore internals
    continue;
  }

  $oTown = \Grepodata\Library\Controller\Town::firstById($World, $oConquest->town_id);

  if (!key_exists($oConquest->n_a_id, $aAllianceIds)) {
    $aAllianceIds[$oConquest->n_a_id] = array('color'=>imagecolorallocate($img, rand(0,255), rand(0,255), rand(0,255)));
  }

//  $oAlliance = null;
//  $oColor = $white;
//  if (isset($aAllianceIds[$oConquest->n_a_id])) {
//    $oAlliance = $aAllianceIds[$oConquest->n_a_id];
//    $oColor = $oAlliance['color'];
//    $aAllianceIds[$oConquest->n_a_id]['conq_count']+= 1;
//  }

//  $xCoor = mapCoor($oTown->island_x);
//  $yCoor = mapCoor($oTown->island_y);
//  if ($xCoor == false || $yCoor == false) {
//    printf("Skipped coor $oTown->island_x,$oTown->island_y \n");
//    continue;
//  }

//  imagesetpixel($img, $xCoor, $yCoor, $oColor);

  if (isset($aPlayerAllianceIds[$oConquest->n_p_id]) && $aPlayerAllianceIds[$oConquest->n_p_id] != $oConquest->n_a_id) {
    // Update all pixels where the alliance was changed for this player
    for ($x = 0; $x < $imgSize; ++$x) {
      for ($y = 0; $y < $imgSize; ++$y) {
        if ($pixelArray[$x][$y]['p_id'] == $oConquest->n_p_id) {
          $pixelArray[$x][$y]['id'] = $oConquest->n_a_id;
          touchMap($pixelArray, $x, $y);
        }
      }
    }
    $aPlayerAllianceIds[$oConquest->n_p_id] = $oConquest->n_a_id;
  }
  $aPlayerAllianceIds[$oConquest->n_p_id] = $oConquest->n_a_id;
  $pixelArray[$oTown->island_x][$oTown->island_y] = array('id'=>$oConquest->n_a_id,'fade'=>10,'touched'=>true,'actual'=>true,'p_id'=>$oConquest->n_p_id);
  touchMap($pixelArray, $oTown->island_x, $oTown->island_y);

  $count++;
  if ($count > $batchSize) {
    printf("Processing batch $batch: $oConquest->time");
    $LastDate = Carbon::parse($oConquest->time);
    $count = 0;
    $batch++;

    $img = array2img($pixelArray, $LastDate);
    $png = imagepng($img, "img/map_".sprintf('%04d', $batch).".png");
    printf("\n");

    $t=2;
  }
}

function touchMap(&$pixelArray, $xi, $yi) {
  $touchrange = 10;
  for ($x = -$touchrange; $x <= $touchrange; ++$x) {
    for ($y = -$touchrange; $y <= $touchrange; ++$y) {
      if (abs($x)+abs($y)<=ceil(($touchrange+(0.6*$touchrange)))){
        $pixelArray[$xi + $x][$yi + $y]['touched'] = true;
      }
    }
  }
}

function array2img(&$pixelArray, $Date) {
  global $aAllianceIds, $scaledSize, $font, $white, $black, $imgSize;
  // Build img from pixel array
  $aAlliancesInImage = array();
  $totalPixels = 0;
  $scans = 0;
  $img = imagecreatetruecolor($imgSize, $imgSize);
//  imagefilledrectangle($img, 0, 0, $imgSize, $imgSize, $white);
//  imagealphablending($img, true);
//  imagesavealpha($img, true);
  for ($x = 0; $x < $imgSize; ++$x) {
    for ($y = 0; $y < $imgSize; ++$y) {
      $pixelValue = $pixelArray[$x][$y];
      if ($pixelValue['touched']==true) {
        $pixelArray[$x][$y]['touched'] = false;
        if (($pixelValue['id']==0 || $pixelValue['fade'] <= 0) && $pixelValue['actual']==false) {
//        if (($pixelValue['id']==0 || $pixelValue['fade'] <= 0)) {
          // Assign to nearest pixel if this pixel was touched AND is faded out
          $scans++;
          $pixelArray[$x][$y] = nearestPixel($x, $y, $pixelArray);
        } else {
          $pixelArray[$x][$y]['fade'] -= 1;
        }
      }
      if ($pixelArray[$x][$y]['id'] != 0) {
        $totalPixels += 1;
        $color = $aAllianceIds[$pixelArray[$x][$y]['id']]['color'];
        imagesetpixel($img, $x, $y, $color);
        if (isset($aAlliancesInImage[$pixelArray[$x][$y]['id']])) {
          $aAlliancesInImage[$pixelArray[$x][$y]['id']]['count'] += 1;
        } else {
          $aAlliancesInImage[$pixelArray[$x][$y]['id']] = array(
            'name' => isset($aAllianceIds[$pixelArray[$x][$y]['id']]['name'])?$aAllianceIds[$pixelArray[$x][$y]['id']]['name']:'Unknown',
            'color' => $color,
            'count' => 1
          );
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
  uasort($aAlliancesInImage, function ($Player1, $Player2) {
    // sort by score descending
    return $Player1['count'] > $Player2['count'] ? -1 : 1;
  });
  imagefilledrectangle($img, 5, 5, 250, min(310, sizeof($aAlliancesInImage)*20+10), $white);
  $offset = 0;
  foreach ($aAlliancesInImage as $Alliance) {
    if ($offset > 280) continue;
    imagefilledrectangle($img, 10, 13+$offset, 20, 23+$offset, $Alliance['color']);
    imagettftext($img, 14, 0, 25, 25+$offset, $Alliance['color'], $font, ceil(($Alliance['count'] / $totalPixels)*100) . "%  " . substr($Alliance['name'], 0, 18) . (strlen($Alliance['name'])>18?'..':''));
    $offset += 20;
  }

  // Date
  imagefilledrectangle($img, 0, $scaledSize-40, $scaledSize, $scaledSize, $black);
  imagettftext($img, 24, 0, 5, $scaledSize-5, $white, $font, $Date->format("d-m-Y"));
  printf(" Scans: $scans");
  return $img;
}

function nearestPixel($xi, $yi, $pixelArray) {
  // Search a 10x10 window for the nearest value
  $searchWindow = 10;
  $closest = 1000;
  $closestValue = $pixelArray[$xi][$yi];
  for ($x = $xi-$searchWindow; $x < $xi+$searchWindow; ++$x) {
    for ($y = $yi-$searchWindow; $y < $yi+$searchWindow; ++$y) {
      if (isset($pixelArray[$x][$y])) {
        $d = sqrt(pow($x - $xi, 2) + pow($y - $yi, 2));
        if ($d < $closest && key_exists('id', $pixelArray[$x][$y]) && $pixelArray[$x][$y]['id']!=0 && $pixelArray[$x][$y]['fade']>1 && $pixelArray[$x][$y]['actual']==true) {
          $closest = $d;
          $closestValue = $pixelArray[$x][$y];
        }
      }
    }
  }
  $closestValue['actual'] = false;
  return $closestValue;
}

$t=2;
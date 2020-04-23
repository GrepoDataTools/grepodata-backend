<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

ini_set('memory_limit','1G');

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\World;

Logger::enableDebug();
Logger::debugInfo("Started world map processing");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 20*60);

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
if ($worlds === false) {
  Logger::error("Terminating execution of world mapper: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

$imgSize = 10;
$img = imagecreatetruecolor($imgSize, $imgSize);
$aPreferredColors = array(
  imagecolorallocate($img, 41, 127, 185), // gd blue
  imagecolorallocate($img, 234, 97, 83), // gd red
  imagecolorallocate($img, 67, 131, 67), // gd green
  imagecolorallocate($img, 224, 224, 76), // yellow
  imagecolorallocate($img, 97, 2, 221), // dark purple
  imagecolorallocate($img, 76, 224, 224), // cyan
  imagecolorallocate($img, 159, 221, 175), // mint
  imagecolorallocate($img, 123, 119, 127), // greyish
  imagecolorallocate($img, 0, 40, 127), // dark blue
  imagecolorallocate($img, 127, 80, 0), // brown
  imagecolorallocate($img, 215, 156, 207), // PINK
);

/** @var $oWorld World */
foreach ($worlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

  try {
    Logger::debugInfo("Processing map for world $oWorld->grep_id");
    Logger::debugInfo("World map memory usage: used=" . round(memory_get_usage(false)/1048576,2) . "MB, real=" . round(memory_get_usage(true)/1048576,2) . "MB");

    // Get colormap
    $ColorMap = $oWorld->colormap;
    //$MapSettings = $oWorld->mapsettings;
    if ($ColorMap == null) {
      // Create new map
      $ColorMap = array();
    } else {
      $ColorMap = json_decode($ColorMap, true);
    }

    // island heatmap
    $oTowns = \Grepodata\Library\Model\Town::where('world', '=', $oWorld->grep_id)->orderBy('grep_id', 'asc')->limit(25000)->get();
    $aPlayers = array();
    $aTop10Players = array();
    $TopCount = 0;
    foreach (\Grepodata\Library\Model\Player::where('world', '=', $oWorld->grep_id, 'and')
               ->where('active', '=', true)->orderBy('rank', 'asc')->cursor() as $oPlayer) {
      $aPlayers[$oPlayer->grep_id] = $oPlayer;
      if ($oPlayer->active == true && $TopCount < 10) {
        $TopCount++;
        $aTop10Players[] = $oPlayer;
      }
    }
    Logger::silly("Creating alliance heatmap");
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
    $img = imagecreatetruecolor($imgSize, $imgSize);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $bgcolor = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $bgcolor);
    foreach (\Grepodata\Library\Model\Alliance::where('world', '=', $oWorld->grep_id, 'and')
               ->where('rank', '<=', 30, 'and')->where('members', '>', 0, 'and')->where('points', '>', 0, 'and')
               ->where('updated_at', '>', Carbon::now()->subDays(64))
               ->orderBy('rank', 'asc')->cursor() as $oAlliance) {
      if (!key_exists($oAlliance->grep_id, $ColorMap)) {
        // Add new entry to colormap for new alliance
        $ColorMap[$oAlliance->grep_id] = array(
          'color' => getNewColor($img, $ColorMap),
          'last_used' => Carbon::now()
        );
      } else {
        $ColorMap[$oAlliance->grep_id]['last_used'] = Carbon::now();
      }
      $oAlliance['color'] = $ColorMap[$oAlliance->grep_id]['color'];
      $aAlliances[$oAlliance->grep_id] = $oAlliance;
    }

    // clean unused colors
    foreach ($ColorMap as $Id => $AllianceColor) {
      if ($AllianceColor['last_used'] < Carbon::now()->subDays(14)) {
        unset($ColorMap[$Id]);
      }
    }

    // Check if preferred color is available to start using
    $SubList = array_chunk($aAlliances, sizeof($aPreferredColors), true)[0];
    foreach ($SubList as $Id => $oAlliance) {
      $bHasPreferredColor = false;
      foreach ($aPreferredColors as $Color) {
        if ($oAlliance['color'] == $Color) {
          $bHasPreferredColor = true;
        }
      }
      if ($bHasPreferredColor == false) {
        $NewColor = getNewColor($img, $ColorMap);
        // check if new color is actually preferred, otherwise keep old color
        foreach ($aPreferredColors as $Color) {
          if ($NewColor == $Color) {
            $aAlliances[$Id]['color'] = $NewColor;
            $ColorMap[$Id] = array(
              'color'=>$NewColor,
              'last_used' => Carbon::now()
            );
          }
        }
      }
    }

    // Convert to color
    Logger::silly("Creating color pixel map");
    $aAlliancesInImage = array();
    $total = 0;
    $distances = array();
    $colorMap = array_fill(0, $imgSize, array_fill(0, $imgSize, array('color' => null,
      'closest' => 1000, 'touched' => 0, 'actual' => false)));
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
          $colorMap[$x][$y] = array('color' => $aAlliances[$owner]['color'], 'closest'=>0, 'touched' => 1, 'actual' => true);
          $distToCenter = sqrt(pow(500 - $x, 2) + pow(500 - $y, 2));
          touchMap($colorMap, $x, $y, $distToCenter, $aAlliances[$owner]['color']);
          $distances[] = $distToCenter;

          if (isset($aAlliancesInImage[$owner])) {
            $aAlliancesInImage[$owner] += 1;
          } else {
            $aAlliancesInImage[$owner] = 1;
          }
        }
      }
    }
    unset($islandMap);

    // avg dist
    $totalDist = array_sum($distances);
    $avgDist = $totalDist / $total;
    Logger::silly("Average distance to center: $avgDist");

    // Create img
    Logger::silly("Creating image");
    $black = imagecolorallocate($img, 0, 0, 0);
    $gdblack = imagecolorallocate($img, 48, 67, 87);
    $white = imagecolorallocate($img, 255, 255, 255);
    $font = bDevelopmentMode?"C:\Windows\Fonts\arial.ttf":TEMP_DIRECTORY."arial.ttf";
    $numTouched = 0;
    for ($x = 0; $x < $imgSize; ++$x) {
      for ($y = 0; $y < $imgSize; ++$y) {
        $Color = $colorMap[$x][$y]['color'];
        if (!is_null($Color) && ($colorMap[$x][$y]['touched']>1 || $colorMap[$x][$y]['actual']==true)) {
          $numTouched++;
          //if ($colorMap[$x][$y]['actual']==true) $Color=$white;
          imagesetpixel($img, $x, $y, $Color);
        }
      }
    }
    Logger::silly("Num pixels painted: $numTouched");
    unset($colorMap);
    unset($numTouched);

    // Resize
    $BannerWidth = 250;
    $image = imagecreatetruecolor($imgSize + $BannerWidth, $imgSize);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $bgcolor = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $bgcolor);

    //zoom = num px cut from all edges
    //plotted curve at: https://mycurvefit.com/
//    $fittedzoom = 422.6188 - 2.080423*$avgDist + 0.004555885*pow($avgDist, 2);
    $fittedzoom = 250.6 + (403.4 - 250.6)/(1 + pow(($avgDist/53.5), 4.1));
    $maxzoom = 400;
    $minzoom = 250;
    $zoom = round(max(min(ceil($fittedzoom), $maxzoom), $minzoom), -1); // Rounded to -1 (tens)
    $zoomedSize = $imgSize-$zoom*2;
    Logger::silly("Fitted zoom: $fittedzoom. Applied zoom: $zoom. Zoomed size: $zoomedSize");
    $img = imagecrop($img, array('x'=>$zoom, 'y'=>$zoom, 'width'=>$zoomedSize, 'height'=>$zoomedSize));
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $img = imagescale($img, $imgSize, $imgSize, IMG_NEAREST_NEIGHBOUR);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    imagecolortransparent($img, $black);
    imagecopy($image, $img, 0, 0, 0, 0, $imgSize, $imgSize);
//    imagecopyresampled($image, $img, 0, 0, $zoom, $zoom, $imgSize, $imgSize, $imgSize-($zoom*2), $imgSize-($zoom*2));
    $img = $image;
    imagealphablending($img, false);
    imagesavealpha($img, true);

    // Legend
    arsort($aAlliancesInImage);
    $offset = 0;
    foreach ($aAlliancesInImage as $Id => $Count) {
      $percentage = ceil(($Count / $total)*100);
//      if ($offset > 580 || ($offset > 200 && $percentage <= 2)) continue;
      if ($offset > 580) continue;
      imagefilledrectangle($img, $imgSize + 9, 82+$offset, $imgSize + 21, 94+$offset, $aAlliances[$Id]['color']);
      imagettftext($img, 14, 0, $imgSize + 25, 95+$offset, $aAlliances[$Id]['color'], $font, $percentage . "%");
      imagettftext($img, 14, 0, $imgSize + 70, 95+$offset, $aAlliances[$Id]['color'], $font, substr($aAlliances[$Id]['name'], 0, 16) . (strlen($aAlliances[$Id]['name'])>16?'..':''));
//      imagettfstroketext($img, 12, 0, $imgSize + 70, 95+$offset, $aAlliances[$Id]['color'], $gdblack, $font, substr($aAlliances[$Id]['name'], 0, 16) . (strlen($aAlliances[$Id]['name'])>16?'..':''), 1);
      $offset += 20;
    }
    unset($aAlliances);
    unset($aAlliancesInImage);

    // top 10 players
    $offset += 40;
    imagettftext($img, 20, 0, $imgSize + 9, 95+$offset, $gdblack, $font, "Top 10 players:");
    foreach ($aTop10Players as $Rank => $oPlayer) {
      imagettftext($img, 16, 0, $imgSize + 25, 120+$offset, $gdblack, $font, " ". substr($oPlayer->name, 0, 17) . (strlen($oPlayer->name)>17?'..':''));
      $offset += 20;
    }
    unset($aTop10Players);

    // Date
    $Date = Carbon::now();
    imagettftext($img, 24, 0, $imgSize + 9, 35, $gdblack, $font, $oWorld->name);
    imagettftext($img, 24, 0, $imgSize + 9, 70, $gdblack, $font, $Date->format("d-m-Y"));
    imagettftext($img, 14, 0, $imgSize + 9, $imgSize - 9, $gdblack, $font, "© grepodata.com ".$Date->format("Y"));

    // save img and clean
    $filename = \Grepodata\Library\Cron\LocalData::saveMap($img, $oWorld->grep_id, $Date->format("Y_m_d"));
    \Grepodata\Library\Cron\LocalData::saveMap($img, $oWorld->grep_id, "today");
    unset($image);
    unset($img);

    // save map info to SQL
    $oWorldMap = \Grepodata\Library\Controller\WorldMap::firstOrNew($oWorld->grep_id, $Date, $filename);
    $oWorldMap->zoom = $zoom;
    $oWorldMap->colormap = json_encode($ColorMap);
    $oWorldMap->save();
    unset($oWorldMap);

    // Save colormap
    $oWorld->colormap = json_encode($ColorMap);
    $oWorld->save();
    unset($ColorMap);

    // Animate map history
    try {
      $aMaps = \Grepodata\Library\Model\WorldMap::where('world', '=', $oWorld->grep_id)->get();
      $NumMaps = sizeof($aMaps);
      if ($NumMaps<=0) {
        throw new Exception("Unable to find map count for this world. Aborting animation.");
      }
      $MaxDuration = 30; // max gif length in seconds
      $FramesPerSecond = max(2, ceil($NumMaps / $MaxDuration));
      Logger::silly("Animating world map history. Number of maps for world $oWorld->grep_id: $NumMaps, FPS: $FramesPerSecond");

      $ffmpeg_path = '/usr/bin/ffmpeg';
      $world_path = MAP_DIRECTORY . $oWorld->grep_id;
      Logger::silly("Animation working directory: $world_path");

      //$animate_cmd = '-framerate '.$FramesPerSecond.' -video_size 1000x1000 -pattern_type glob -i "'.$world_path.'/map_*.png" -c:v libx264 -vf "fps=25,format=yuv420p" '.$world_path.'/temp.mp4 -y -nostdin';
      //$animate_cmd = '-loop 1 -framerate '.$FramesPerSecond.' -i '.TEMP_DIRECTORY.'mapbg.png -framerate '.$FramesPerSecond.' -pattern_type glob -i "'.$world_path.'/map_*.png" -filter_complex "overlay=(W-w)/2:(H-h)/2:shortest=1,format=yuv420p" -vcodec png '.$world_path.'/temp.mov -y -nostdin';
      $animate_cmd = '-loop 1 -framerate '.$FramesPerSecond.' -i '.TEMP_DIRECTORY.'mapbg.png -framerate '.$FramesPerSecond.' -pattern_type glob -i "'.$world_path.'/map_*.png" -filter_complex "overlay=(W-w)/2:(H-h)/2:shortest=1,format=yuv420p" -c:v libx264 '.$world_path.'/temp.mp4';
      $default_cmd = '-y -nostdin -hide_banner -loglevel error'; // -y = overwrite old file, -nostdin = dont ask for input
      $Output = shell_exec("$ffmpeg_path $animate_cmd $default_cmd 2>&1");
      if ($Output!=null) {
        throw new Exception("Error creating animated mp4: ".$Output);
      }

      Logger::silly("Converting temporary mov to gif");
      //$gif_cmd = '-i '.$world_path.'/temp.mov -pix_fmt grb24 '.$world_path.'/animated.gif -y -nostdin';
      $gif_cmd = '-i '.$world_path.'/temp.mp4 '.$world_path.'/animated.gif';
      $OutputGif = shell_exec("$ffmpeg_path $gif_cmd $default_cmd 2>&1");
      if ($OutputGif!=null) {
        throw new Exception("Error converting to gif: ".$OutputGif);
      }

      // ffmpeg -framerate 2 -video_size 1000x1000 -pattern_type glob -i "map_*.png" -c:v libx264 -vf "fps=25,format=yuv420p" temp.mp4
      // ffmpeg -i temp.mp4 -pix_fmt rgb24 animated.gif
      // ffmpeg -i temp.mp4 animated.gif

      Logger::silly("Saved animated gif to: $world_path/animated.gif");
    } catch (Exception $e) {
      Logger::warning("Unable to animate world map using ffmpeg: " . $e->getMessage());
    }

  } catch (\Exception $e) {
    Logger::error("Error processing map for world " . $oWorld->grep_id . ": " . $e->getMessage());
  }
}

Logger::debugInfo("Finished successful execution of world mapper.");
Common::endExecution(__FILE__, $Start);

function getNewColor($img, $ExistingColorMap) {
  global $aPreferredColors;
  // try preferred colors first
  foreach ($aPreferredColors as $Color) {
    $bExists = false;
    foreach ($ExistingColorMap as $ExistingColor) {
      if ($Color == $ExistingColor['color']) {
        $bExists = true;
      }
    }
    if ($bExists == false) {
      return $Color;
    }
  }

  $Attempts = 0;
  while ($Attempts < 50) {
    $Attempts++;
    // try to find a good color
    $r = 25+rand(0,20)*10;
    $g = 25+rand(0,20)*10;
    $b = 25+rand(0,20)*10;
    $Color = imagecolorallocate($img, $r, $g, $b);
    $bExists = false;
    foreach ($ExistingColorMap as $ExistingColor) {
      if ($Color == $ExistingColor['color']) {
        $bExists = true;
      }
    }
    if ($bExists == false && abs(abs($r-$g)-$b)>60) {
      return $Color;
    }
  }
  return imagecolorallocate($img, rand(0,255), rand(0,255), rand(0,255));
}

function touchMap(&$pixelArray, $xi, $yi, $pxDistToCenter, $color) {
  $scanrange = 30;
  $touchrange = 10;


  // decrease touchrange further from center
  $distToCenter = 1 - ($pxDistToCenter/500);
  if ($distToCenter<.7) {
    $touchrange = max(ceil(max($distToCenter, .4) * $touchrange), 3);
  }

  // Scan
  for ($x = -$scanrange; $x <= $scanrange; ++$x) {
    for ($y = -$scanrange; $y <= $scanrange; ++$y) {
      $d = sqrt(pow(($xi + ($x)) - $xi, 2) + pow(($yi + ($y)) - $yi, 2));
      if ($d<=$scanrange+1&&isset($pixelArray[$xi + $x])&&isset($pixelArray[$xi + $x][$yi + $y])
        &&$pixelArray[$xi + $x][$yi + $y]['actual']==false){
        $pixelArray[$xi + $x][$yi + $y]['touched'] += 1;

        // Update closest color if within touchrange
        if ($d<$touchrange+1 && $d<$pixelArray[$xi + $x][$yi + $y]['closest']) {
          $pixelArray[$xi + $x][$yi + $y]['closest'] = $d;
          $pixelArray[$xi + $x][$yi + $y]['color'] = $color;

        }
      }
    }
  }
}

//function nearestPixelColor($xi, $yi, $pixelArray) {
//  // Search a 10x10 window for the nearest value
//  $searchWindow = 20; // scan this range for neighbouring actuals
//  $nearestWindow = 10; // scan this range for colors
//  $numNear = 0;
//  $closest = 1000;
//  $closestValue = $pixelArray[$xi][$yi]['color'];
//  for ($x = $xi-$searchWindow; $x < $xi+$searchWindow; ++$x) {
//    for ($y = $yi-$searchWindow; $y < $yi+$searchWindow; ++$y) {
//      if (isset($pixelArray[$x][$y]) && isset($pixelArray[$x][$y]['actual']) && $pixelArray[$x][$y]['actual']==true) {
//        $numNear++;
//        if (abs($yi - $y) < $nearestWindow || abs($xi - $x) < $nearestWindow) {
//          $d = sqrt(pow($x - $xi, 2) + pow($y - $yi, 2));
//          if ($d < $closest) {
//            $closest = $d;
//            $closestValue = $pixelArray[$x][$y]['color'];
//          }
//        }
//      }
//    }
//  }
//  if ($numNear <= 2) {
//    return $pixelArray[$xi][$yi]['color'];
//  }
//  return $closestValue;
//}

//function imagettfstroketext(&$image, $size, $angle, $x, $y, $textcolor, $strokecolor, $fontfile, $text, $px) {
//  for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
//    for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
//      $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
//  return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
//}

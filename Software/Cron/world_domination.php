<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\DominationScoreboard;
use Grepodata\Library\Model\WorldDomination;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started domination calculation");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 2*60);

// Find worlds to process
//$aWorlds = Common::getAllActiveWorlds();
//if ($aWorlds === false) {
//  Logger::error("Terminating execution of hourly import: Error retrieving worlds from database.");
//  Common::endExecution(__FILE__);
//}

$aWorlds = array(
//  World::getWorldById("fr131")
  World::getWorldById("nl92")
);

/** @var \Grepodata\Library\Model\World $oWorld */
foreach ($aWorlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;


  // Get local town data
  $aTownsLocal = LocalData::getLocalTownData($oWorld->grep_id);
  if (sizeof($aTownsLocal) > 60000) {
    // probably a legacy world? skip calculation as it would be too slow
    Logger::debugInfo("Skipping domination calculation for world " . $oWorld->grep_id . " (" . sizeof($aTownsLocal) . " towns)");
    continue;
  } else {
    Logger::debugInfo("Running domination calculation for world " . $oWorld->grep_id);
  }
  Logger::silly("Towns local: " . sizeof($aTownsLocal));

  // Get database town data
  $aTowns = \Grepodata\Library\Model\Town::select([
    'Town.*',
    'Player.alliance_id',
    'Alliance.name AS alliance_name',
    'Alliance.members AS alliance_members',
    'Alliance.towns AS alliance_towns'
  ])
    ->leftJoin('Player', function($join)
    {
      $join->on('Player.grep_id', '=', 'Town.player_id')
        ->on('Player.world', '=', 'Town.world');
    })
    ->leftJoin('Alliance', function($join)
    {
      $join->on('Alliance.grep_id', '=', 'Player.alliance_id')
        ->on('Player.world', '=', 'Alliance.world');
    })
    ->where('Town.world', '=', $oWorld->grep_id, 'and')
    ->get();
  Logger::silly("Towns db: " . sizeof($aTowns));

  // Parse towns into islands
  $aIslandsList = array();
  foreach ($aTowns as $oTown) {
    if (!key_exists($oTown->grep_id, $aTownsLocal)) {
      // Skip towns that no longer exist
      continue;
    }

    $aTownData = $oTown;
    $aTownData['island_xy'] = (int) ($oTown->island_x . $oTown->island_y);
    $aTownData['ocean'] = floor($oTown->island_x/100) . floor($oTown->island_y/100);

    // Which quadrant does the island fall in?
    if ($oTown->island_x < 500) {
      if ($oTown->island_y >= 500) {
        $aTownData['quadrant'] = 'SW';
      } else {
        $aTownData['quadrant'] = 'NW';
      }
    } else {
      if ($oTown->island_y >= 500) {
        $aTownData['quadrant'] = 'SE';
      } else {
        $aTownData['quadrant'] = 'NE';
      }
    }

    // Save to list
    if (!key_exists($aTownData['quadrant'], $aIslandsList)) {
      $aIslandsList[$aTownData['quadrant']] = array();
    }
    if (!key_exists($aTownData['island_xy'], $aIslandsList[$aTownData['quadrant']])) {
      $aIslandsList[$aTownData['quadrant']][$aTownData['island_xy']] = array(
        'towns' => array(),
        'center_distance' => sqrt(abs(500-$oTown->island_x)**2 + abs(500-$oTown->island_y)**2),
        'num_towns' => 0,
        'full_island' => false,
      );
    }
    $aIslandsList[$aTownData['quadrant']][$aTownData['island_xy']]['towns'][] = $aTownData;
    $aIslandsList[$aTownData['quadrant']][$aTownData['island_xy']]['num_towns']++;
    if ($aIslandsList[$aTownData['quadrant']][$aTownData['island_xy']]['num_towns'] >= 15) {
      $aIslandsList[$aTownData['quadrant']][$aTownData['island_xy']]['full_island'] = true;
    }
  }

  // Handle each quadrant independently (circle can be bigger depending on relative growth of quadrant)
  foreach ($aIslandsList as $Quadrant => $islands) {
    // drop non-full islands
    $aIslandsList[$Quadrant] = array_filter($aIslandsList[$Quadrant], function ($obj) {
      return $obj['full_island'];
    });

    // Order islands by quadrant and distance to center
    uasort($aIslandsList[$Quadrant], function ($a, $b) {
      return $a['center_distance'] > $b['center_distance'];
    });
  }

  // How big should the circle be in each direction?
  $aDominationPercentiles = array(70, 75, 80, 85, 90);
  $aDominationData = array();
  foreach ($aDominationPercentiles as $domination_percentile) {
    $aIslandsListCopy = array();

    // Handle each quadrant independently (circle can be bigger depending on relative growth of quadrant)
    foreach ($aIslandsList as $Quadrant => $islands) {
      // Apply domination percentile
      $aIslandsListCopy[$Quadrant] = array_slice($aIslandsList[$Quadrant], 0, floor(($domination_percentile/100) * sizeof($aIslandsList[$Quadrant])));
    }

    // aggregate domination towns
    $aDominationTowns = array();
    foreach ($aIslandsListCopy as $Quadrant => $islands) {
      foreach ($islands as $towns) {
        $aDominationTowns = array_merge($aDominationTowns, $towns['towns']);
      }
    }

    // aggregate by alliance
    $aAlliances = array();
    foreach ($aDominationTowns as $oTown) {
      $AllianceId = $oTown->alliance_id ?? 0;
      if (!key_exists($AllianceId, $aAlliances)) {
        $aAlliances[$AllianceId] = array(
          'i' => $AllianceId,
          'm' => $oTown->alliance_members ?? 1,
          'tt' => $oTown->alliance_towns ?? 1,
          'n' => $oTown->alliance_name ?? 'No alliance / ghost',
          't' => 0
        );
      }
      $aAlliances[$AllianceId]['t']++;
    }

    // order by towns descending
    uasort($aAlliances, function ($a, $b) {
      return $a['t'] < $b['t'];
    });

    // Add percentages
    $TotalTowns = sizeof($aDominationTowns);
    foreach ($aAlliances as $AllianceId => $aAlliance) {
      $aAlliances[$AllianceId]['p'] = round(($aAlliance['t'] / $TotalTowns) * 100, 1); // domination percentage
      $aAlliances[$AllianceId]['tpm'] = round($aAlliance['t'] / $aAlliance['m'], 1); // how many domination towns per alliance

      // keep only alliances with >1% domination
      if ($aAlliances[$AllianceId]['p'] < 1) {
        unset($aAlliances[$AllianceId]);
      }
    }

    $aDominationData[$domination_percentile] = $aAlliances;

    unset($aIslandsListCopy);
  }

  // log 80th percentile
  if ($oWorld->grep_id == 'nl92') {
    foreach ($aDominationData[90] as $aAlliance) {
      Logger::silly($aAlliance['n'] . " = " . $aAlliance['t'] . " (" . $aAlliance['p'] . "%) (".$aAlliance['tpm']." towns per member)");
    }
  }

  // Save to database
  /** @var WorldDomination $oWorldDomination */
//  $oWorldDomination = WorldDomination::firstOrNew(array('world'=>$oWorld->grep_id));
//  $oWorldDomination->domination_json = json_encode($aDominationData);
//  $oWorldDomination->save();

  // Cleanup
  unset($aTownsLocal);
  unset($aTowns);
  unset($aIslandsList);
  unset($aDominationTowns);
  unset($islands);
  unset($towns);

}

Logger::debugInfo("Finished successful execution of domination update.");
Common::endExecution(__FILE__, $Start);

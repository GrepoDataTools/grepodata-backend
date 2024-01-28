<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Alliance;
use Grepodata\Library\Model\AllianceChanges;
use Grepodata\Library\Model\AllianceHistory;
use Grepodata\Library\Model\AllianceScoreboard;
use Grepodata\Library\Model\Conquest;
use Grepodata\Library\Model\IndexV2\Intel;
use Grepodata\Library\Model\IndexV2\IntelShared;
use Grepodata\Library\Controller\IndexV2\DailyReport;
use Grepodata\Library\Model\Player;
use Grepodata\Library\Model\PlayerHistory;
use Grepodata\Library\Model\PlayerScoreboard;
use Grepodata\Library\Model\Town;
use Grepodata\Library\Model\World;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started stopped world cleanup");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 3*60);

$Days = 14;
$Limit = Carbon::now()->subDays($Days);
$Worlds = World::where('stopped', '=', 1, 'and')
  ->where('cleaned', '=', 0, 'and')
  ->where('updated_at', '<', $Limit)
  ->get();

// Skipped cleaning: nl66, nl92, zz9, zz17 (as of 01-2024)

/** @var World $oWorld */
foreach ($Worlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

  try {
    Logger::warning("Processing world data cleanup for world: " . $oWorld->grep_id);

    // Elasticsearch players
    $aPlayers = \Grepodata\Library\Controller\Player::allActiveByWorld($oWorld->grep_id);
    $PlayerCount = 0;
    $FailCount = 0;
    foreach ($aPlayers as $oPlayer) {
      $PlayerCount++;
      $bDeleted = Import::DeletePlayer($oPlayer);
      if ($bDeleted === false) {
        $FailCount++;
      }
    }
    unset($aPlayers);
    Logger::debugInfo("Deleted $PlayerCount players from Elasticsearch. Failures: $FailCount");

    // Elasticsearch alliances
    $aAlliances = \Grepodata\Library\Controller\Alliance::allByWorld($oWorld->grep_id);
    $AllianceCount = 0;
    $FailCount = 0;
    foreach ($aAlliances as $oAlliance) {
      $AllianceCount++;
      $bDeleted = Import::DeleteAlliance($oAlliance);
      if ($bDeleted === false) {
        $FailCount++;
      }
    }
    unset($aPlayers);
    Logger::debugInfo("Deleted $AllianceCount alliances from Elasticsearch. Failures: $FailCount");

    // Elasticsearch towns
    $aTowns = \Grepodata\Library\Controller\Town::allByWorld($oWorld->grep_id);
    $TownCount = 0;
    $FailCount = 0;
    foreach ($aTowns as $oTown) {
      $TownCount++;
      $bDeleted = Import::DeleteTown($oTown);
      if ($bDeleted === false) {
        $FailCount++;
      }
    }
    unset($aPlayers);
    Logger::debugInfo("Deleted $TownCount towns from Elasticsearch. Failures: $FailCount");

    // Towns
    $Deleted = Town::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted towns");

    // Conquests
    $Deleted = Conquest::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted conquests");

    // Alliance changes
    $Deleted = AllianceChanges::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted alliance changes");

    // Alliance history
    $Deleted = AllianceHistory::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted alliance histories");

    // Alliance scoreboard
    $Deleted = AllianceScoreboard::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted alliance scoreboards");

    // Alliances
    $Deleted = Alliance::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted alliances");

    // Player history
    $Deleted = PlayerHistory::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted player histories");

    // Player scoreboard
    $Deleted = PlayerScoreboard::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted player scoreboards");

    // Players
    $Deleted = Player::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $Deleted players");

    // Intel
    $DistinctTowns = Intel::where('world', '=', $oWorld->grep_id)->distinct()->count('town_id');
    $DeletedIntel = Intel::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $DeletedIntel intel records");
    $DeletedShared = IntelShared::where('world', '=', $oWorld->grep_id)->delete();
    Logger::debugInfo("Deleted $DeletedShared intel link records");

    // Persist deleted rows to keep track of total stats
    DailyReport::increment_persisted_property('persist_property_intel_towns_deleted', $DistinctTowns);
    DailyReport::increment_persisted_property('persist_property_intel_raw_deleted', $DeletedIntel);
    DailyReport::increment_persisted_property('persist_property_intel_shared_deleted', $DeletedShared);

    $oWorld->cleaned = 1;
    $oWorld->save();
  } catch (\Exception $e) {
    Logger::error("CRITICAL: Error cleaning stopped world: " . $e->getMessage());
  }
}

Logger::debugInfo("Finished execution of stopped world cleaner.");
Common::endExecution(__FILE__, $Start);

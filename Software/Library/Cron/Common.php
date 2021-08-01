<?php

namespace Grepodata\Library\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\CronStatus;
use Grepodata\Library\IndexV2\ForumParser;
use Grepodata\Library\IndexV2\InboxParser;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\IndexV2\Intel;
use Grepodata\Library\Model\IndexV2\IntelShared;
use Grepodata\Library\Model\Operation_scriptlog;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class Common
{

  /**
   * @param $Path
   * @param null $ExpireOverrideMinutes
   * @param bool $bThrowError
   * @return bool|\Grepodata\Library\Model\CronStatus
   */
  public static function markAsRunning($Path, $ExpireOverrideMinutes = null, $bThrowError = true)
  {
    if (bDevelopmentMode === true) {
      return true;
    }

    $oCronStatus = CronStatus::firstOrNew(basename($Path));

    // Inactive cron
    if (isset($oCronStatus->active) && $oCronStatus->active == false) {
      Logger::error("Cronjob disabled: " . basename($Path));
      die("Cronjob disabled: " . basename($Path));
    }

    // Already running
    if (isset($oCronStatus->running) && $oCronStatus->running == true) {

      // Check Override
      $bTerminate = true;
      if (!is_null($ExpireOverrideMinutes)) {
        $ExpireOverrideTime = Carbon::now()->subMinutes($ExpireOverrideMinutes);
        if ($oCronStatus->last_run_started < $ExpireOverrideTime) {
          // Last run did not terminate correctly or is still running within the override time
          $Msg = "Cronjob restarted with override: " . basename($Path) . ". Last run started at: " . $oCronStatus->last_run_started;
          if ($bThrowError) {
            Logger::error($Msg);
          } else {
            Logger::warning($Msg);
          }
          return $oCronStatus;
        }
      }

      if ($bTerminate == true) {
        if ($bThrowError) {
          Logger::error("Cronjob already running: " . basename($Path));
        } else {
          Logger::warning("Cronjob already running: " . basename($Path));
        }
        die("Cronjob already running: " . basename($Path));
      }

    }

    // Mark as running
    $oCronStatus->running = true;
    $oCronStatus->last_run_started = Carbon::now();
    $oCronStatus->save();
    return $oCronStatus;
  }

  /**
   * @param $Path
   * @return \Grepodata\Library\Model\CronStatus
   */
  private static function markAsComplete($Path)
  {
    $oCronStatus = CronStatus::firstOrNew(basename($Path));

    // Unmark as running
    $oCronStatus->running = false;
    $oCronStatus->last_run_ended = Carbon::now();
    $oCronStatus->save();
    return $oCronStatus;
  }

  public static function endExecution($Path = null, $Start = null)
  {
    if (bDevelopmentMode === true) {
      return true;
    }
    if (!is_null($Path)) {
      self::markAsComplete($Path);
    }
    if (!is_null($Start)) {
      try {
        Common::saveScriptLog($Path, $Start, Carbon::now());
      } catch (\Exception $e) {}
    }
    die();
  }

  public static function scoreboardSort($aDiffs)
  {
    uasort($aDiffs, function ($Player1, $Player2) {
      if ($Player1['s'] == $Player2['s']) {
        // Rank ascending if scores are equal
        if (isset($Player1['r']) && isset($Player2['r'])) {
          return $Player1['r'] - $Player2['r'];
        } else {
          return -1;
        }
      }
      // Otherwise sort by score descending
      return $Player1['s'] > $Player2['s'] ? -1 : 1;
    });
    return $aDiffs;
  }

  /**
   * @param Intel $Report
   * @return Intel|bool
   * @throws \Exception
   */
  public static function debugIndexer(Intel $Report)
  {
    $aReportData = (array) json_decode($Report->report_json, true);

    $aParsed = null;

    $UserId = $Report->indexed_by_user_id;
    $ReportHash = $Report->hash;
    $ReportPosterId = $Report->poster_player_id;
    $ReportPosterName = $Report->poster_player_name;
    $ReportAllianceId = $Report->poster_alliance_id;
    $World = $Report->world;
    $ReportJson = $Report->report_json;
    $ReportInfo = $Report->report_info;
    $ScriptVersion = $Report->script_version;
    $Locale = substr($Report->world, 0, 2);

    // Index list
    $aIndexSharedList = IntelShared::where('intel_id', '=', $Report->id)->get();
    $aRawIndexKeyList = array();
    /** @var IntelShared $oIndex */
    foreach ($aIndexSharedList as $oIndex) {
      if ($oIndex->index_key != null) {
        $aRawIndexKeyList[] = $oIndex->index_key;
      }
    }

    $bCombatExperience = true;
//    $bCombatExperience = false;

    if ($Report->source_type === 'forum') {
      $aParsed = ForumParser::ParseReport(
        $UserId,
        $World,
        $aReportData,
        $ReportHash,
        $ReportJson,
        $ReportInfo,
        $ReportPosterName,
        $ReportPosterId,
        $ReportAllianceId,
        $ScriptVersion,
        $Locale,
        $Report,
        $aRawIndexKeyList
      );
      $t=2;
    } else {
      $aParsed = InboxParser::ParseReport(
        $UserId,
        $World,
        $aReportData,
        $ReportPosterName,
        $ReportPosterId,
        $ReportAllianceId,
        $bCombatExperience,
        $ReportHash,
        $ReportJson,
        $ReportInfo,
        $ScriptVersion,
        $Locale,
        $Report,
        $aRawIndexKeyList
      );
      $t=2;
    }

    if (is_numeric($aParsed) && $aParsed > 0) {
      // get parsed record
      return Intel::where('id', '=', $aParsed)->firstOrFail();
    }

    return false;
  }

  public static function formatConquestScoresArray($aConquestScores)
  {
    $newScores = array();
    foreach ($aConquestScores as $id => $score) {
      if ($id == "") {
        // Skip ghosts
        continue;
      }
      $newScores[] = array(
        'i' => $id,
        's' => $score['cities']
      );
    }
    return $newScores;
  }

  public static function getAllianceScoreboardFromDiffs($aDiffs)
  {
    // Merge player diffs into alliance diffs
    $aAllianceDiffs = array();
    foreach ($aDiffs as $aData) {
      if (isset($aAllianceDiffs[$aData['a_id']])) {
        $aAllianceDiffs[$aData['a_id']]['s'] += $aData['s'];
        if ($aAllianceDiffs[$aData['a_id']]['r'] < $aData['r']) {
          $aAllianceDiffs[$aData['a_id']]['r'] = $aData['r']; // Alliance rank = rank of their best player
        }
      }
      else {
        $aAllianceDiffs[$aData['a_id']]['s'] = $aData['s'];
        $aAllianceDiffs[$aData['a_id']]['r'] = $aData['r'];
      }
    }

    // Format scoreboard
    $aFormattedAllianceDiffs = array();
    foreach ($aAllianceDiffs as $id => $score) {
      if ($id == "") continue; // Skip ghosts
      $aFormattedAllianceDiffs[] = array(
        'i' => $id,
        's' => $score['s'],
        'r' => $score['r']
      );
    }

    return $aFormattedAllianceDiffs;
  }

  public static function padConquestArray($aOriginal, $aPads, $MaxSize = 50)
  {
    foreach ($aPads as $aPad) {
      if (sizeof($aOriginal) < $MaxSize) {
        // if pad not in original
        $Exists = false;
        foreach ($aOriginal as $aData) {
          if ($aData['i'] == $aPad['i']) $Exists = true;
        }

        // add pad to original
        if (!$Exists) $aOriginal[] = array('i' => $aPad['i'],'s' => 0);
      }
    }
    return $aOriginal;
  }

  /**
   * @param bool $bPrioritize
   * @return bool|Collection|\Grepodata\Library\Model\World[]
   */
  public static function getAllActiveWorlds($bPrioritize = true)
  {
    $worlds = World::where('stopped', '=', 0)
      ->orderBy('id', 'desc')
      ->get();

    if (!isset($worlds) || $worlds == null || $worlds == '' || sizeof($worlds) <= 0) {
      Logger::warning("Found 0 active worlds in database.");
      return false;
    }

    if ($bPrioritize === false) {
      return $worlds;
    }

    // Prioritise Dutch worlds, then French worlds
    $aPriorityOrder = array('nl' => array(), 'fr' => array(), 'de' => array(), 'en' => array(), 'other' => array()); // Other servers maintain original order
    /** @var World $oWorld */
    foreach ($worlds as $oWorld) {
      $Server = substr($oWorld->grep_id, 0, 2);
      if (in_array($Server, array_keys($aPriorityOrder))) {
        $aPriorityOrder[$Server][] = $oWorld;
      } else {
        $aPriorityOrder["other"][] = $oWorld;
      }
    }

    // Join servers
    $aJoined = array();
    foreach ($aPriorityOrder as $aServer) {
      foreach ($aServer as $oWorld) {
        $aJoined[] = $oWorld;
      }
    }

    return $aJoined;
  }

  public static function getAllActiveIndexes()
  {
    $aIndex = IndexInfo::where('status', '=', 'active')->get();

    if (!isset($aIndex) || $aIndex == null || $aIndex == '' || sizeof($aIndex) <= 0) {
      Logger::warning("Found 0 active indices in database.");
      return false;
    }

    return $aIndex;
  }

  public static function getAllIndexesByWorld(World $oWorld)
  {
    $aIndex = IndexInfo::where('world', '=', $oWorld->grep_id)
      ->orderBy('id', 'desc')
      ->get();

    if (!isset($aIndex) || $aIndex == null || $aIndex == '' || sizeof($aIndex) <= 0) {
      return array();
//      Logger::warning("Found 0 active indices in database.");
//      return false;
    }

    return $aIndex;
  }

  public static function saveScriptLog($Name, $Start, $End)
  {
    $Log = new Operation_scriptlog();
    $Log->script = $Name;
    $Log->start = $Start;
    $Log->end = $End;
    $Log->pid = getmypid();
    $Log->save();
  }
}

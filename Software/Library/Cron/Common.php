<?php

namespace Grepodata\Library\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\CronStatus;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\Indexer\ReportId;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Indexer\ForumParser;
use Grepodata\Library\Indexer\InboxParser;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\Indexer\Report;
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

  public static function debugIndexer(Report $Report, IndexInfo $Info, $bForceReparse = false, $bRebuildIndex = false)
  {
    $aReportData = (array) json_decode($Report->report_json, true);

    $aParsed = null;
    try {
      if ($bForceReparse && $Report->city_id > 0) {
        // Force reparse and delete existing parsed entry
        City::where('id', '=', $Report->city_id)->delete();
        $Report->city_id = null;
        $Report->save();
      }

      $ReportHash = 'LOCAL_DEBUG';
      $ReportPosterId = '';
      $ReportPosterName = $Report->report_poster;
      $ReportAllianceId = '';
      try {
        $oReportHash = ReportId::firstByIndexByHash($Info->key_code, $Report->fingerprint);
        $ReportHash = $oReportHash->report_id;
      } catch (\Exception $e) {}

      if ($Report->type === 'default') {
        $aParsed = ForumParser::ParseReport($Report->index_code, $aReportData, $ReportPosterName, $ReportHash, substr($Info->world, 0, 2));
        if (is_array($aParsed) && isset($aParsed['id'])) {
          $aParsed = $aParsed['id'];
        }
      } else {
        if (isset($oReportHash)) {
          $ReportPosterId = $oReportHash->player_id;
          try {
            $oPlayer = Player::firstOrFail($ReportPosterId, $Info->world);
            $oAlliance = Alliance::firstOrFail($oPlayer->alliance_id, $Info->world);
            $ReportAllianceId = $oAlliance->grep_id;
          } catch (\Exception $e) {}
        }
        $aParsed = InboxParser::ParseReport($Report->index_code, $aReportData, $ReportPosterName, $ReportPosterId, $ReportAllianceId, $ReportHash, substr($Info->world, 0, 2));
      }

      if (is_numeric($aParsed) && $aParsed > 0 && $Report->city_id <= 0) {
        $Report->city_id = $aParsed;
        $Report->save();
        if ($bRebuildIndex === true) {
          IndexOverview::buildIndexOverview($Info);
        }
      }
    } catch (\Exception $e) {
      return '<table>'.$e->xdebug_message.'</table>';
    }

    return $aParsed;
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
      Logger::warning("Found 0 active indices in database.");
      return false;
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
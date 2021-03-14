<?php

namespace Grepodata\Library\Import;

use Carbon\Carbon;
use Grepodata\Library\Controller\AllianceScoreboard;
use Grepodata\Library\Controller\PlayerScoreboard;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Elasticsearch\Diff;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Hourly
{

  /**
   * @param \Grepodata\Library\Model\World $oWorld
   * @return bool
   * @throws \Exception
   */
  public static function DataImportRanks(\Grepodata\Library\Model\World $oWorld)
  {
    // Get data
    $aAttData = LocalData::getLocalPlayerAttackData($oWorld->grep_id);
    $aDefData = LocalData::getLocalPlayerDefenceData($oWorld->grep_id);
    $aAllData = InnoData::loadPlayerKillsData($oWorld->grep_id);
    $aPlayerData = InnoData::loadPlayerData($oWorld->grep_id);

    $total = sizeof($aPlayerData);
    Logger::silly("Loaded ranking data for " . $total . " players.");

    if ($aAttData == false || $aDefData == false || $aAllData == false || $aPlayerData == false) {
      // data not yet exists. skip for now
      Logger::debugInfo("Rank import skipped for world ".$oWorld->grep_id."; local data not yet exists.");
      return true;
    }

    $numUpdated = 0;
    /** @var \Grepodata\Library\Model\Player $oPlayer */
    foreach (\Grepodata\Library\Model\Player::where('world', '=', $oWorld->grep_id, 'and')
               ->where('active', '=', 1)->cursor() as $oPlayer) {
      $bUpdated = false;

      // ATT
      if ($aData = $aAttData[$oPlayer->grep_id] ?? null) {
        if ($oPlayer->att_rank_max == null || $oPlayer->att_rank == null || $aData['rank'] != $oPlayer->att_rank) {
          if ($oPlayer->att_rank_max == null || $oPlayer->att_rank == null
            || ($oPlayer->att_rank < $aData['rank'] && $oPlayer->att_rank <= $oPlayer->att_rank_max)) {
            $oPlayer->att_rank_max = $oPlayer->att_rank != null ? $oPlayer->att_rank : $aData['rank'];
            $oPlayer->att_rank_date = Carbon::now()->subDay();
          }
          $oPlayer->att_rank = $aData['rank'];
          $bUpdated = true;
        }
      }
      
      // DEF
      if ($aData = $aDefData[$oPlayer->grep_id] ?? null) {
        if ($oPlayer->def_rank_max == null || $oPlayer->def_rank == null || $aData['rank'] != $oPlayer->def_rank) {
          if ($oPlayer->def_rank_max == null || $oPlayer->def_rank == null
            || ($oPlayer->def_rank < $aData['rank'] && $oPlayer->def_rank <= $oPlayer->def_rank_max)) {
            $oPlayer->def_rank_max = $oPlayer->def_rank != null ? $oPlayer->def_rank : $aData['rank'];
            $oPlayer->def_rank_date = Carbon::now()->subDay();
          }
          $oPlayer->def_rank = $aData['rank'];
          $bUpdated = true;
        }
      }
      
      // KILLS
      if ($aData = $aAllData[$oPlayer->grep_id] ?? null) {
        if ($oPlayer->fight_rank_max == null || $oPlayer->fight_rank == null || $aData['rank'] != $oPlayer->fight_rank) {
          if ($oPlayer->fight_rank_max == null || $oPlayer->fight_rank == null
            || ($oPlayer->fight_rank < $aData['rank'] && $oPlayer->fight_rank <= $oPlayer->fight_rank_max)) {
            $oPlayer->fight_rank_max = $oPlayer->fight_rank != null ? $oPlayer->fight_rank : $aData['rank'];
            $oPlayer->fight_rank_date = Carbon::now()->subDay();
          }
          $oPlayer->fight_rank = $aData['rank'];
          $bUpdated = true;
        }
      }

      // TOWNS + RANK
      if ($aData = $aPlayerData[$oPlayer->grep_id] ?? null) {
        if ($oPlayer->rank_max == null || $oPlayer->rank != $aData['rank']) {
          if ($oPlayer->rank_max == null
            || ($oPlayer->rank < $aData['rank'] && $oPlayer->rank <= $oPlayer->rank_max)) {
            $oPlayer->rank_max = $oPlayer->rank != null ? $oPlayer->rank : $aData['rank'];
            $oPlayer->rank_date = Carbon::now()->subDay();
          }
          $oPlayer->rank = $aData['rank'];
          $bUpdated = true;
        }

        if ($oPlayer->towns_max == null || $oPlayer->towns != $aData['towns']) {
          if ($oPlayer->towns_max == null
            || ($oPlayer->towns > $aData['towns'] && $oPlayer->towns >= $oPlayer->towns_max)) {
            $oPlayer->towns_max = $oPlayer->towns != null ? $oPlayer->towns : $aData['towns'];
            $oPlayer->towns_date = Carbon::now()->subDay();
          }
          $oPlayer->towns = $aData['towns'];
          $bUpdated = true;
        }
      }

      if ($bUpdated == true) {
        $numUpdated += 1;
        $oPlayer->save();
      }
    }
    unset($aAttData);
    unset($aDefData);
    unset($aAllData);
    unset($aPlayerData);

    Logger::silly("Updated rankings for $numUpdated/$total players.");
    return true;
  }

  /**
   * @param \Grepodata\Library\Model\World $oWorld
   * @param bool $bForceUpdate
   * @return bool
   * @throws \Exception
   */
  public static function DataImportHourly(\Grepodata\Library\Model\World $oWorld, $bForceUpdate = false)
  {
    // Check headers for new update
    $aHeaders = InnoData::loadPlayerAttData($oWorld->grep_id, true);
    if ($aHeaders === false) {
      Logger::warning("Error checking inno data endpoint headers.");
      return false;
    }
    $GrepServerTime = $aHeaders['Last-Modified'];
    $GrepEtag       = $aHeaders['ETag'];

    // Check etag
    if ($GrepEtag == $oWorld->etag) {
      // No new data, skip run..
      Logger::debugInfo("Skipping update, no new data. [Local: ".$oWorld->etag." ".date("Y-m-d H:i:s", strtotime($oWorld->grep_server_time))."] [Remote: ".$GrepEtag." ".date("Y-m-d H:i:s", strtotime($GrepServerTime))."]");
      return false;
    }

    // Check update times
    $bTerminateIfEmpty = false;
    if ($bForceUpdate===false && strtotime($oWorld->grep_server_time) > strtotime($GrepServerTime)) {
      //$bTerminateIfEmpty = true;
      // The remote data has actually been reset to an older state!!
      // The current remote can result in negative diffs, resetting to the old values. so we should skip the update.
      Logger::debugInfo("Local data is newer then remote (?). [Local: ".$oWorld->etag." ".date("Y-m-d H:i:s", strtotime($oWorld->grep_server_time))."] [Remote: ".$GrepEtag." ".date("Y-m-d H:i:s", strtotime($GrepServerTime))."]");
      return false;
    } else if (strtotime('+10 minutes', strtotime($oWorld->grep_server_time)) > strtotime($GrepServerTime) && $bForceUpdate===false) {
      Logger::debugInfo("Remote data update time is within 10 minutes of local data, skipping update. [Local: ".$oWorld->etag." ".date("Y-m-d H:i:s", strtotime($oWorld->grep_server_time))."] [Remote: ".$GrepEtag." ".date("Y-m-d H:i:s", strtotime($GrepServerTime))."]");
      return false;
    } else if (strtotime('+50 minutes', strtotime($oWorld->grep_server_time)) > strtotime($GrepServerTime) && $bForceUpdate===false) {
      $bTerminateIfEmpty = true;
      Logger::debugInfo("Remote data update time is within 50 minutes (?). [Local: ".$oWorld->etag." ".date("Y-m-d H:i:s", strtotime($oWorld->grep_server_time))."] [Remote: ".$GrepEtag." ".date("Y-m-d H:i:s", strtotime($GrepServerTime))."]");
    } else {
      Logger::debugInfo("New data detected. [Local: ".$oWorld->etag." ".date("Y-m-d H:i:s", strtotime($oWorld->grep_server_time))."] [Remote: ".$GrepEtag." ".date("Y-m-d H:i:s", strtotime($GrepServerTime))."]");
    }

    // Load player data
    $aPlayerAttData = InnoData::loadPlayerAttData($oWorld->grep_id);
    if ($aPlayerAttData === false) {
      Logger::warning("Error getting player attack data.");
      return false;
    }

    // Save world status as updated
    $GrepServerTimeDate = date('Y-m-d H:i:s', strtotime($GrepServerTime));
    $oWorld->grep_server_time = $GrepServerTimeDate;
    $oWorld->etag = $GrepEtag;
    $oWorld->save();

    // Get diff date info
    $DiffDate = $oWorld->getServerTime();
    $DiffDayOfWeek = $DiffDate->dayOfWeek; // 0 for sunday, trough 6 for saturday
    $DiffHourOfDay = $DiffDate->hour; // 0 trough 23

    // Check scoreboard date; if there is a daily reset due to happen, use new diff instead of old_att/old_def
    $LastProcessedReset = $oWorld->last_reset_time;
    $bAwaitingDailyReset = False;
    if (strtotime("-26 hour") > strtotime($LastProcessedReset)) {
      Logger::silly("Awaiting daily reset, using diff values for update; server last reset time: " . $LastProcessedReset);
      $bAwaitingDailyReset = True;
    }

    // ==== 1. ATTACK
    $TotalAtt = sizeof($aPlayerAttData);
    Logger::silly("Updating player kills att: ".$TotalAtt.".");

    // Load last execution
    $aPreviousAttData = LocalData::getLocalPlayerAttackData($oWorld->grep_id);
    Logger::silly("Loaded local player data att: ".sizeof($aPreviousAttData).".");

    $NumNew = 0;
    $NumSkipped = 0;
    $NumUpdated = 0;

    $aPlayerAttDiffs = array();
    $AttSum = 0;
    $HourOfDay = null;
    foreach ($aPlayerAttData as $aData) {
      try {
        $bUpdate = false;
        $aPrevious = null;

        if ($aPreviousAttData != false && $aPlayer = $aPreviousAttData[$aData['player_id']] ?? null) {
          $aPrevious = $aPlayer;
        }

        if ($aPrevious == null) {
          $bUpdate = true;
          $NumNew++;
        } else if ($aPrevious['points'] != $aData['points']) {
          $bUpdate = true;
          $NumUpdated++;
        } else {
          $bUpdate = false;
          $NumSkipped++;
        }

        if ($aData['rank'] < 50) {
          // Always update top 50 to have some filler data for scoreboard
          $bUpdate = true;
        }

        if ($bUpdate === true || $bForceUpdate===true) {
          // Find player instance
          $oPlayer = Player::firstOrFail($aData['player_id'], $oWorld->grep_id);

          // Save att diff
          if ($oPlayer->att != null) {
            $Diff = (int) ($aData['points'] - $oPlayer->att);
          } else {
            $Diff = (int) $aData['points'];
          }
          try {
            if ($Diff > 0) {
              $HourOfDay = Diff::SaveAttDiff($oPlayer, $Diff, $DiffDate, $DiffDayOfWeek, $DiffHourOfDay);
              $AttSum += $Diff;
            }
          } catch (\Exception $e) {}

          // Update attack data
          $oPlayer->att = $aData['points'];
          if ($oPlayer->att_old == null) {
            $oPlayer->att_old = $oPlayer->att;
          }
          if ($Diff > 0) {
            // Update time since last attack point
            $oPlayer->att_point_date = Carbon::now();
          }

          $aPlayerAttDiffs[$aData['player_id']] = array(
            'i' => $aData['player_id'],
            's' => $bAwaitingDailyReset == true ? $Diff : ($oPlayer->att - $oPlayer->att_old),
            'n' => $oPlayer->name,
            'a_id' => $oPlayer->alliance_id,
            'r' => (int) $aData['rank']
          );

          $oPlayer->save();
        }

      } catch (ModelNotFoundException $e) {
        continue; // skip to next player
      }
    }

    $AttUpdated = $NumUpdated;
    if ($NumSkipped + $NumNew + $NumUpdated != $TotalAtt) {
      Logger::warning("Player att import: Update count mismatch");
    }
    Logger::silly("Player att records updated for world " .$oWorld->grep_id. ". Total: ".$TotalAtt.", Skipped: ".$NumSkipped.", Updated: ".$NumUpdated.", New: " . $NumNew);

    // Save new data to disk for next import
    LocalData::setLocalPlayerAttackData($oWorld->grep_id, $aPlayerAttData);
    unset($aPlayerAttData);
    unset($aPreviousAttData);

    if ($bTerminateIfEmpty && $NumUpdated <= 0 && $NumNew != $TotalAtt) {
      // Remote data file was updated but the file contained no new data.. early stopping
      Logger::debugInfo("Early stopping hourly import for this world: attack point update contained no new data.");
      return true;
    }

    // ==== 2. DEFENCE
    // Load player data
    $aPlayerDefData = InnoData::loadPlayerDefData($oWorld->grep_id);
    if ($aPlayerDefData === false) {
      Logger::warning("Error getting player defence data.");
      return false;
    }
    $TotalDef = sizeof($aPlayerDefData);
    Logger::silly("Updating player kills def: ".$TotalDef.".");

    // Load last execution
    $aPreviousDefData = LocalData::getLocalPlayerDefenceData($oWorld->grep_id);
    Logger::silly("Loaded local player data def: ".sizeof($aPreviousDefData).".");

    $NumNew = 0;
    $NumSkipped = 0;
    $NumUpdated = 0;

    $aPlayerDefDiffs = array();
    $DefSum = 0;
    foreach ($aPlayerDefData as $aData) {
      try {
        $bUpdate = false;
        $aPrevious = null;

        if ($aPreviousDefData != false && $aPlayer = $aPreviousDefData[$aData['player_id']] ?? null) {
          $aPrevious = $aPlayer;
        }

        if ($aPrevious == null) {
          $bUpdate = true;
          $NumNew++;
        } else if ($aPrevious['points'] != $aData['points']) {
          $bUpdate = true;
          $NumUpdated++;
        } else {
          $bUpdate = false;
          $NumSkipped++;
        }

        if ($aData['rank'] < 50) {
          // Always update top 50 to have some filler data for scoreboard
          $bUpdate = true;
        }

        if ($bUpdate === true || $bForceUpdate===true) {
          // Find player instance
          $oPlayer = Player::firstOrFail($aData['player_id'], $oWorld->grep_id);

          // Save def diff
          if ($oPlayer->def != null) {
            $Diff = (int) ($aData['points'] - $oPlayer->def);
          } else {
            $Diff = (int) $aData['points'];
          }
          try {
            if ($Diff > 0) {
              $HourOfDay = Diff::SaveDefDiff($oPlayer, $Diff, $DiffDate, $DiffDayOfWeek, $DiffHourOfDay);
              $DefSum += $Diff;
            }
          } catch (\Exception $e) {}

          // Update defence data
          $oPlayer->def = $aData['points'];
          if ($oPlayer->def_old == null) {
            $oPlayer->def_old = $oPlayer->def;
          }

          $aPlayerDefDiffs[$aData['player_id']] = array(
            'i' => $aData['player_id'],
            's' => $bAwaitingDailyReset == true ? $Diff : ($oPlayer->def - $oPlayer->def_old),
            'n' => $oPlayer->name,
            'a_id' => $oPlayer->alliance_id,
            'r' => (int) $aData['rank']
          );

          $oPlayer->save();
        }

      } catch (ModelNotFoundException $e) {
        continue; // skip to next player
      }
    }

    // Save new data to disk for next import
    LocalData::setLocalPlayerDefenceData($oWorld->grep_id, $aPlayerDefData);
    unset($aPlayerDefData);
    unset($aPreviousDefData);

    $DefUpdated = $NumUpdated;
    if ($NumSkipped + $NumNew + $NumUpdated != $TotalDef) {
      Logger::warning("Player def import: Update count mismatch");
    }
    Logger::silly("Player def records updated for world " .$oWorld->grep_id. ". Total: ".$TotalDef.", Skipped: ".$NumSkipped.", Updated: ".$NumUpdated.", New: " . $NumNew);

    // Get scoreboard objects
    $ScoreboardDate = World::getScoreboardDate($oWorld);
    $ScoreboardTime = World::getScoreboardTime($oWorld, $GrepServerTime);
    $oPlayerScoreboard = PlayerScoreboard::firstOrNew($ScoreboardDate, $oWorld->grep_id);
    $oPlayerScoreboard->server_time = $ScoreboardTime;
    $oAllianceScoreboard = AllianceScoreboard::firstOrNew($ScoreboardDate, $oWorld->grep_id);
    $oAllianceScoreboard->server_time = $ScoreboardTime;

    $bIsFirstOfDay = false;
    if (isset($oPlayerScoreboard->att) && isset($oAllianceScoreboard->att)) {
      if ($AttUpdated + $DefUpdated <= 0) {
        // No new data, skip building scoreboard; but only if there already is a scoreboard for today (just update time)
        Logger::debugInfo("No att/def records updated. Skipping scoreboard build");
        $oPlayerScoreboard->save();
        $oAllianceScoreboard->save();
        return true;
      }
    } else {
      $bIsFirstOfDay = true;
    }

    // Load alliance data
    $aAllianceAttData = InnoData::loadAllianceAttData($oWorld->grep_id);
    $aAllianceDefData = InnoData::loadAllianceDefData($oWorld->grep_id);
    Logger::silly("Updating alliance kills: (att: ".sizeof($aAllianceAttData).", def: ".sizeof($aAllianceDefData).").");

    $aAllianceDiffsAtt = [];
    foreach ($aAllianceAttData as $aData) {
      try {
        // Find alliance instance
        $oAlliance = Alliance::firstOrFail($aData['alliance_id'], $oWorld->grep_id);

        if ($oAlliance->att != null) {
          $Diff = (int) ($aData['points'] - $oAlliance->att);
        } else {
          $Diff = (int) $aData['points'];
        }

        $aAllianceDiffsAtt[$aData['alliance_id']] = array(
          'i' => $aData['alliance_id'],
          's' => $Diff,
          'n' => $oAlliance->name,
          'r' => (int) $aData['rank']
        );

        if ($Diff > 0) {

          // Update attack data
          $oAlliance->att = $aData['points'];
          $oAlliance->save();
        }

      } catch (ModelNotFoundException $e) {
        continue; // skip to next alliance
      }
    }
    unset($aAllianceAttData);

    $aAllianceDiffsDef = [];
    foreach ($aAllianceDefData as $aData) {
      try {
        // Find alliance instance
        $oAlliance = Alliance::firstOrFail($aData['alliance_id'], $oWorld->grep_id);

        if ($oAlliance->def != null) {
          $Diff = (int) ($aData['points'] - $oAlliance->def);
        } else {
          $Diff = (int) $aData['points'];
        }

        $aAllianceDiffsDef[$aData['alliance_id']] = array(
          'i' => $aData['alliance_id'],
          's' => $Diff,
          'n' => $oAlliance->name,
          'r' => (int) $aData['rank']
        );

        if ($Diff > 0) {
          // Update defence data
          $oAlliance->def = $aData['points'];
          $oAlliance->save();
        }
      } catch (ModelNotFoundException $e) {
        continue; // skip to next alliance
      }
    }
    unset($aAllianceDefData);
    Logger::silly("Finished updating alliance kills.");


    // ==== SCOREBOARDS

    // Format new scoreboard data
    $aScoreboardData = array(
      'player_att' => $aPlayerAttDiffs,
      'player_def' => $aPlayerDefDiffs,
      'alliance_att' => $aAllianceDiffsAtt,
      'alliance_def' => $aAllianceDiffsDef,
    );

    // Add existing entries to the data
    foreach (['att', 'def'] as $Type) {
      if ($oPlayerScoreboard->{$Type} != null) {
        $aExistingData = json_decode($oPlayerScoreboard->{$Type}, true);
        if (is_array($aExistingData)) {
          foreach ($aExistingData as $aExistingEntry) {
            if (!key_exists($aExistingEntry['i'], $aScoreboardData['player_'.$Type])) {
              $aScoreboardData['player_'.$Type][$aExistingEntry['i']] = $aExistingEntry;
            }
          }
        }
      }
    }
    foreach (['att', 'def'] as $Type) {
      if ($oAllianceScoreboard->{$Type} != null) {
        $aExistingData = json_decode($oAllianceScoreboard->{$Type}, true);
        if (is_array($aExistingData)) {
          foreach ($aExistingData as $aExistingEntry) {
            if (!key_exists($aExistingEntry['i'], $aScoreboardData['alliance_'.$Type])) {
              $aScoreboardData['alliance_'.$Type][$aExistingEntry['i']] = $aExistingEntry;
            } else {
              $aScoreboardData['alliance_'.$Type][$aExistingEntry['i']]['s'] += $aExistingEntry['s'];
            }
          }
        }
      }
    }

    // Sort diffs
    foreach ($aScoreboardData as $Type => $aData) {
      $aScoreboardData[$Type] = Common::scoreboardSort($aData);
    }

    // Slice arrays to top 50
    foreach ($aScoreboardData as $Type => $aData) {
      $Cut = 50;
      $aScoreboardData[$Type] = array_slice($aData, 0, $Cut, true);
    }

    if ($bIsFirstOfDay) {
      // Pad empty conquest scoreboard with players/alliances
      $aScoreboardData['player_con'] = Common::padConquestArray(array(), $aScoreboardData['player_att']);
      $aScoreboardData['player_los'] = Common::padConquestArray(array(), $aScoreboardData['player_def']);
      $aScoreboardData['alliance_con'] = Common::padConquestArray(array(), $aScoreboardData['alliance_att']);
      $aScoreboardData['alliance_los'] = Common::padConquestArray(array(), $aScoreboardData['alliance_def']);
    }

    // Drop rank and a_id columns and attach names
    foreach ($aScoreboardData as $Type => $aData) {
      $aTempScores = array();
      foreach ($aData as $TypeKey => $aScores) {
        $Name = '-';
        if (isset($aScores['n'])) {
          $Name = $aScores['n'];
        }
        else {
          try {
            if (strpos($Type, 'player') !== false) {
              if (!isset($aScores['n'])) {
                if (isset($aScoreboardData['player_att'][$aScores['i']]['n'])) {
                  $Name = $aScoreboardData['player_att'][$aScores['i']]['n'];
                } else if (isset($aScoreboardData['player_def'][$aScores['i']]['n'])) {
                  $Name = $aScoreboardData['player_def'][$aScores['i']]['n'];
                } else {
                  $oPlayer = Player::firstOrFail($aScores['i'], $oWorld->grep_id);
                  $Name = $oPlayer->name;
                }
              }
            }
            else if (strpos($Type, 'alliance') !== false) {
              if (!isset($aScores['n'])) {
                if (isset($aScoreboardData['alliance_att'][$aScores['i']]['n'])) {
                  $Name = $aScoreboardData['alliance_att'][$aScores['i']]['n'];
                } else if (isset($aScoreboardData['alliance_def'][$aScores['i']]['n'])) {
                  $Name = $aScoreboardData['alliance_def'][$aScores['i']]['n'];
                } else {
                  $oAlliance = Alliance::firstOrFail($aScores['i'], $oWorld->grep_id);
                  $Name = $oAlliance->name;
                }
              }
            }
          } catch (ModelNotFoundException $e) {
            Logger::warning("No model found for ".$Type." with id ".$aScores['i']." in world " . $oWorld->grep_id);
            // Encoding errors?
          }
        }

        // format
        $aTempScores[$aScores['i']] = array(
          'i' => $aScores['i'],
          's' => $aScores['s'],
          'n' => $Name,
        );
      }
      $aScoreboardData[$Type] = $aTempScores;
    }

    // Slice alliances once more
    foreach ($aScoreboardData as $Type => $aData) {
      if (strpos($Type, 'alliance') !== false) {
        $aScoreboardData[$Type] = array_slice($aData, 0, 20, true);
      }
    }

    // Save players
    $oPlayerScoreboard->att = json_encode(array_values($aScoreboardData['player_att']));
    $oPlayerScoreboard->def = json_encode(array_values($aScoreboardData['player_def']));
    if ($bIsFirstOfDay == true) {
      $oPlayerScoreboard->con = json_encode(array_values($aScoreboardData['player_con']));
      $oPlayerScoreboard->los = json_encode(array_values($aScoreboardData['player_los']));
    }

    // Parse overview
    if (!is_null($HourOfDay)) {
      $bAdded = false;
      $OverviewEntry = array('att' => $AttSum, 'def' => $DefSum, 'hour' => $HourOfDay);
      if (isset($oPlayerScoreboard->overview) && !is_null($oPlayerScoreboard->overview)) {
        try {
          $aOverview = json_decode($oPlayerScoreboard->overview, true);
          foreach ($aOverview as $aOverviewExists) {
            if (isset($aOverviewExists['hour']) && $aOverviewExists['hour'] == $HourOfDay) {
              // Overview already has an entry for this hour. Add info to the existing entry
              $aOverviewExists['att'] += $AttSum;
              $aOverviewExists['def'] += $DefSum;
              $bAdded = true;
            }
          }
          if ($bAdded === false) {
            // Append a new overview entry to the overview array
            $aOverview[] = $OverviewEntry;
          }
          $oPlayerScoreboard->overview = json_encode($aOverview);
        } catch (\Exception $e) {
          Logger::error("Unable to parse scoreboard overview for $oWorld->grep_id - $ScoreboardDate with message " . $e->getMessage());
        }
      } else {
        $oPlayerScoreboard->overview = json_encode(array($OverviewEntry));
      }
    }
    $oPlayerScoreboard->save();

    // Save alliance scoreboard
    $oAllianceScoreboard->att = json_encode(array_values($aScoreboardData['alliance_att']));
    $oAllianceScoreboard->def = json_encode(array_values($aScoreboardData['alliance_def']));
    if ($bIsFirstOfDay == true) {
      $oAllianceScoreboard->con = json_encode(array_values($aScoreboardData['alliance_con']));
      $oAllianceScoreboard->los = json_encode(array_values($aScoreboardData['alliance_los']));
    }
    $oAllianceScoreboard->save();

    Logger::silly("Finished processing hourly import for world ".$oWorld->grep_id.".");
    return true;
  }
}

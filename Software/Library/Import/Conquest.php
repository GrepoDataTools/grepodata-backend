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
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Conquest
{

  /**
   * @param \Grepodata\Library\Model\World $oWorld
   * @return bool
   * @throws \Exception
   */
  public static function DataImportConquest(\Grepodata\Library\Model\World $oWorld)
  {
    // Get scoreboard objects
    $ScoreboardDate = World::getScoreboardDate($oWorld);
    $oPlayerScoreboard = PlayerScoreboard::firstOrNew($ScoreboardDate, $oWorld->grep_id);
    $oAllianceScoreboard = AllianceScoreboard::firstOrNew($ScoreboardDate, $oWorld->grep_id);

    if (!isset($oPlayerScoreboard->att) || !isset($oAllianceScoreboard->att)) {
      // Scoreboards for today do not yet exist
      return false;
    }

    // Load conquers data
    $aConquestData = InnoData::loadConquersData($oWorld->grep_id);
    Logger::silly("Updating conquests: (".sizeof($aConquestData).").");

    $aPlayerCitiesGained    = array();
    $aPlayerCitiesLost      = array();
    $aAllianceCitiesGained  = array();
    $aAllianceCitiesLost    = array();
    if ($aConquestData == false) {
      $aConquestData = array();
    }

    // Find conquest day limits for today
    $ConquestLowerTimeLimit = strtotime($oWorld->last_reset_time);
    $ConquestUpperTimeLimit = Carbon::createFromTimestampUTC($ConquestLowerTimeLimit);
    $ConquestUpperTimeLimit->addHours(24);
    $ConquestUpperTimeLimit = strtotime($ConquestUpperTimeLimit->format('Y-m-d H:i:s'));
    $ScriptLimit = strtotime("-36 hours");

    foreach ($aConquestData as $aData) {
      try {
        $ConquestTime = strtotime($aData['time']);

        // Limit history (script runs every hour so we can ignore older records as they have already been processed by now)
        if ($ScriptLimit < $ConquestTime) {

          // Add today's conquests to scoreboard
          if ($ConquestLowerTimeLimit < $ConquestTime && $ConquestUpperTimeLimit > $ConquestTime) {
            (isset($aPlayerCitiesGained[$aData['n_p_id']])    ? $aPlayerCitiesGained[$aData['n_p_id']]['cities'] += 1   : $aPlayerCitiesGained[$aData['n_p_id']]['cities'] = 1 );
            (isset($aPlayerCitiesLost[$aData['o_p_id']])      ? $aPlayerCitiesLost[$aData['o_p_id']]['cities'] += 1     : $aPlayerCitiesLost[$aData['o_p_id']]['cities'] = 1 );
            (isset($aAllianceCitiesGained[$aData['n_a_id']])  ? $aAllianceCitiesGained[$aData['n_a_id']]['cities'] += 1 : $aAllianceCitiesGained[$aData['n_a_id']]['cities'] = 1 );
            (isset($aAllianceCitiesLost[$aData['o_a_id']])    ? $aAllianceCitiesLost[$aData['o_a_id']]['cities'] += 1   : $aAllianceCitiesLost[$aData['o_a_id']]['cities'] = 1 );
          }

          $oConquest = new \Grepodata\Library\Model\Conquest();
          $oConquest->world = $oWorld->grep_id;
          foreach ($aData as $Key => $Value) {
            $oConquest->$Key = $Value;
          }
          $oConquest->save();
        }
      } catch (\Exception $e) {
        if (strpos($e, "Duplicate entry") === false) {
          Logger::warning("Conquest import exception for conquest entry: " . $e->getMessage());
        }
        // Exception likely caused by duplicate entries caused by daylight savings time transitions. ignore for now
      }
    }
    unset($aConquestData);
    Logger::silly("Finished updating conquests. Starting scoreboard update.");

    // ==== SCOREBOARDS

    // Format new scoreboard data
    $aScoreboardData = array(
      'player_con' => Common::formatConquestScoresArray($aPlayerCitiesGained),
      'player_los' => Common::formatConquestScoresArray($aPlayerCitiesLost),
      'alliance_con' => Common::formatConquestScoresArray($aAllianceCitiesGained),
      'alliance_los' => Common::formatConquestScoresArray($aAllianceCitiesLost),
    );

    // Sort diffs
    foreach ($aScoreboardData as $Type => $aData) {
      $aScoreboardData[$Type] = Common::scoreboardSort($aData);
    }

    // Slice arrays to top 50
    foreach ($aScoreboardData as $Type => $aData) {
      $Cut = 50;
      $aScoreboardData[$Type] = array_slice($aData, 0, $Cut, true);
    }
    
    $aAttPlayers = json_decode($oPlayerScoreboard->att, true);
    $aDefPlayers = json_decode($oPlayerScoreboard->def, true);
    $aAttAlliances = json_decode($oAllianceScoreboard->att, true);
    $aDefAlliances = json_decode($oAllianceScoreboard->def, true);
      
    // Pad conquest scoreboard with 0-scoring players
    $aScoreboardData['player_con'] = Common::padConquestArray($aScoreboardData['player_con'], $aAttPlayers);
    $aScoreboardData['player_los'] = Common::padConquestArray($aScoreboardData['player_los'], $aDefPlayers);
    $aScoreboardData['alliance_con'] = Common::padConquestArray($aScoreboardData['alliance_con'], $aAttAlliances);
    $aScoreboardData['alliance_los'] = Common::padConquestArray($aScoreboardData['alliance_los'], $aDefAlliances);

    // Drop rank and a_id columns and attach names
    $PlayerNames = array();
    $AllianceNames = array();
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
                if (isset($PlayerNames[$aScores['i']])) {
                  $Name = $PlayerNames[$aScores['i']];
                } else {
                  $oPlayer = Player::firstOrFail($aScores['i'], $oWorld->grep_id);
                  $Name = $oPlayer->name;
                  $PlayerNames[$aScores['i']] = $Name;
                }
              }
            }
            else if (strpos($Type, 'alliance') !== false) {
              if (!isset($aScores['n'])) {
                if (isset($AllianceNames[$aScores['i']])) {
                  $Name = $AllianceNames[$aScores['i']];
                } else {
                  $oAlliance = Alliance::firstOrFail($aScores['i'], $oWorld->grep_id);
                  $Name = $oAlliance->name;
                  $AllianceNames[$aScores['i']] = $Name;
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
    $oPlayerScoreboard->con = json_encode(array_values($aScoreboardData['player_con']));
    $oPlayerScoreboard->los = json_encode(array_values($aScoreboardData['player_los']));
    $oPlayerScoreboard->save();

    // Save alliance scoreboard
    $oAllianceScoreboard->con = json_encode(array_values($aScoreboardData['alliance_con']));
    $oAllianceScoreboard->los = json_encode(array_values($aScoreboardData['alliance_los']));
    $oAllianceScoreboard->save();

    Logger::silly("Finished processing conquest import for world ".$oWorld->grep_id.".");
    return true;
  }
}
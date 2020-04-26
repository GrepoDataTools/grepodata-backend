<?php

namespace Grepodata\Library\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;

class SiegeParser
{

  /**
   * this method parses the conquest record and searches for an existing conquest entry
   * If this is the first attack then a conquest entry is created
   * @param ConquestDetails $oConquestDetails
   * @param City $oCity
   * @param IndexInfo $oIndex
   * @param $ReportHash
   * @return mixed
   */
  public static function saveSiegeAttack(ConquestDetails $oConquestDetails, City $oCity, IndexInfo $oIndex, $ReportHash, $aReceiverDetails = array())
  {
    try {
      // Ignore non-intel
      if (key_exists('unknown_naval', $oConquestDetails->siegeUnits) && key_exists('unknown', $oConquestDetails->siegeUnits)) {
        return null;
      }

      // ignore older reports
      $ReportDate = $oCity->parsed_date;
      if (Carbon::now()->diffInDays($ReportDate) > 14) {
        return null;
      }

      // try to find an existing conquest entry for the besieged town
      $aConquests = \Grepodata\Library\Controller\Indexer\Conquest::allByTownId($oConquestDetails->siegeTownId, $oCity->index_key);

      $oConquest = null;
      if (!empty($aConquests) && count($aConquests) > 0) {
        // check if the current record fits into any of the existing conquests
        $ReportDate = $oCity->parsed_date;
        foreach ($aConquests as $ExistingConquest) {
          if ($ExistingConquest->cs_killed == true) {
            continue;
          }

          $ConquestDate = Carbon::parse($ExistingConquest->first_attack_date);
          if ($ReportDate == null) continue;
          $HourDiff = $ReportDate->diffInHours($ConquestDate);
          if ($HourDiff > 24) {
            continue;
          }

          $oConquest = $ExistingConquest;
          if ($ReportDate < $oConquest->first_attack_date) {
            $oConquest->first_attack_date = $ReportDate;
          }
        }
      }

      // Create a new conquest
      if ($oConquest == null) {
        $oConquest = new \Grepodata\Library\Model\Indexer\Conquest();
        $oConquest->index_key = $oCity->index_key;
        $oConquest->num_attacks_counted = 0;
        $oConquest->cs_killed = false;
        $oConquest->first_attack_date = $oCity->parsed_date;

        // Besieged town info
        $oConquest->town_id = $oConquestDetails->siegeTownId;
        $oConquest->town_name = $oConquestDetails->siegeTownName;
        if (key_exists('player_id', $aReceiverDetails)) {
          $oConquest->player_id = $aReceiverDetails['player_id'];
          $oConquest->player_name = $aReceiverDetails['player_name'];
          $oConquest->alliance_id = $aReceiverDetails['alliance_id'];
          $oConquest->alliance_name = $aReceiverDetails['alliance_name'];
        } else {
          // TODO: get town from conquests (get all by town id, filter to report->parsed_date)
          $oTown = Town::firstById($oIndex->world, $oConquestDetails->siegeTownId);
          $oConquest->player_id = $oTown->player_id;
          $oPlayer = Player::firstById($oIndex->world, $oTown->player_id);
          $oConquest->player_name = $oPlayer->name;
          $oConquest->alliance_id = $oPlayer->alliance_id;
          $oAlliance = Alliance::first($oPlayer->alliance_id, $oIndex->world);
          if (!empty($oAlliance)) {
            $oConquest->alliance_name = $oAlliance->name;
          } else {
            $oConquest->alliance_name = '';
          }
        }

        // Belligerent info
        $oConquest->belligerent_player_id = $oConquestDetails->siegePlayerId;
        $oConquest->belligerent_player_name = $oConquestDetails->siegePlayerName;
        $oConquest->belligerent_alliance_id = 0;
        $oConquest->belligerent_alliance_name = '';
        try {
          // find alliance id
          $oPlayer = Player::firstOrFail($oConquestDetails->siegePlayerId, $oIndex->world);
          $oConquest->belligerent_alliance_id = $oPlayer->alliance_id;
          $oAlliance = Alliance::firstOrFail($oPlayer->alliance_id, $oIndex->world);
          $oConquest->belligerent_alliance_name = $oAlliance->name;
        } catch (Exception $e) {
          Logger::warning("SiegeParser $ReportHash: error parsing alliance for new siege; " . $e->getMessage());
        }
      }

      if ($oConquest == null) {
        throw new Exception("Unable to find or create conquest object");
      }

      // Update the conquest with the new intel
      $oConquest->num_attacks_counted += 1;

      $aTotalLossesAtt = array();
      $aTotalLossesDef = array();
      if (!empty($oConquest->total_losses_att) && $oConquest->total_losses_att != "[]") {
        $aTotalLossesAtt = json_decode($oConquest->total_losses_att, true);
      }
      if (!empty($oConquest->total_losses_def) && $oConquest->total_losses_def != "[]") {
        $aTotalLossesDef = json_decode($oConquest->total_losses_def, true);
      }
      $aAttUnits = CityInfo::getMergedUnits($oCity);
      foreach ($aAttUnits as $Key => $Value) {
        preg_match('/\(-[0-9]{1,6}\)/', $Value, $aMatch);
        if (!empty($aMatch)) {
          $LostUnits = abs((int) filter_var($aMatch[0], FILTER_SANITIZE_NUMBER_INT));
          if (!is_nan($LostUnits)) {
            $aTotalLossesAtt[$Key] += $LostUnits;
          }
        }
      }
      $aDefUnits = $oConquestDetails->siegeUnits;
      foreach ($aDefUnits as $Key => $Value) {
        preg_match('/\(-[0-9]{1,6}\)/', $Value, $aMatch);
        if (!empty($aMatch)) {
          $LostUnits = abs((int) filter_var($aMatch[0], FILTER_SANITIZE_NUMBER_INT));
          if (!is_nan($LostUnits) && $LostUnits > 0) {
            $aTotalLossesDef[$Key] += $LostUnits;
            if ($Key === 'colonize_ship') {
              $oConquest->cs_killed = true;
              $oConquest->new_owner_player_id = $oConquest->player_id; // player keeps town
            }
          }
        }
      }
      $oConquest->total_losses_att = json_encode($aTotalLossesAtt);
      $oConquest->total_losses_def = json_encode($aTotalLossesDef);

      // Add alliance to belligerents if needed
      if (!empty($oCity->alliance_id)
        && $oCity->alliance_id > 0
        && $oCity->alliance_id != $oConquest->belligerent_alliance_id
      ) {
        try {
          $oAlliance = Alliance::firstOrFail($oCity->alliance_id, $oIndex->world);

          $aBelligerentAll = array();
          if (!empty($oConquest->belligerent_all) && $oConquest->belligerent_all != "[]") {
            $aBelligerentAll = json_decode($oConquest->belligerent_all, true);
          }
          $aBelligerentAll[$oAlliance->grep_id] = array(
            'alliance_id' => $oAlliance->grep_id,
            'alliance_name' => $oAlliance->name,
          );
          $oConquest->belligerent_all = json_encode($aBelligerentAll);
        } catch (Exception $e) {}
      }

      $bDoSave = true;
      if (!bDevelopmentMode || $bDoSave) {
        $oConquest->save();
      }
      return $oConquest->id;
    } catch (\Exception $e) {
      Logger::warning("SiegeParser $ReportHash: error parsing conquest details for siege; " . $e->getMessage());
    }
  }

}
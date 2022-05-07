<?php

namespace Grepodata\Library\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\IndexV2\Conquest;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\ConquestOverview;
use Grepodata\Library\Model\IndexV2\Intel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SiegeParser
{

  /**
   * this method parses the conquest record and searches for an existing conquest entry
   * If this is the first attack then a conquest entry is created
   * @param ConquestDetails $oConquestDetails
   * @param Intel $oCity
   * @param string[] $aIndexKeys list of index keys
   * @param $ReportHash
   * @return mixed
   */
  public static function saveSiegeAttack(ConquestDetails $oConquestDetails, Intel $oCity, $aIndexKeys = array(), $ReportHash = '', $aReceiverDetails = array())
  {
    try {
      // Ignore non-intel
      if (key_exists('unknown_naval', $oConquestDetails->siegeUnits) && key_exists('unknown', $oConquestDetails->siegeUnits)) {
        return null;
      }

      // ignore older reports
      // TODO: check if this limit can be removed
      $ReportDate = $oCity->parsed_date;
//      if (Carbon::now()->diffInDays($ReportDate) > 14) {
//        return null;
//      }
      if (is_string($ReportDate)) {
        try {
          Logger::warning("SiegeParser $ReportHash: converting parsed intel date; ".$ReportDate);
          $ReportDate = Carbon::createFromTimestampUTC(strtotime($ReportDate));
        } catch (Exception $e) {
          Logger::warning("SiegeParser $ReportHash: error converting parsed intel date; ".$e->getMessage());
          return null;
        }
      }

      // try to find an existing conquest entry for the besieged town
      $aConquests = Conquest::allByTownIdByWorld($oConquestDetails->siegeTownId, $oCity->world);

      $oConquest = null;
      if (!empty($aConquests) && count($aConquests) > 0) {
        // check if the current record fits into any of the existing conquests
        $ReportDate = $oCity->parsed_date;
        foreach ($aConquests as $ExistingConquest) {
          $ConquestDate = Carbon::parse($ExistingConquest->first_attack_date);
          if ($ReportDate == null) continue;

          if ($ReportDate > $ConquestDate && $ExistingConquest->cs_killed == true) {
            // This new report came after the cs was already down, must be a new conquest
            continue;
          }

          $HourDiff = $ReportDate->diffInHours($ConquestDate);
          if ($HourDiff > 16) {
            // TODO: use world settings to make this search interval more accurate depending on the world
            // no overlap, must be new conquest
            continue;
          }

          $oConquest = $ExistingConquest;
          if ($ReportDate > $ConquestDate) {
            // first_attack_date = latest parsed date in this conquest
            $oConquest->first_attack_date = $ReportDate;
          }
          break;
        }
      }

      // Create a new conquest
      if ($oConquest == null) {
        $oConquest = new \Grepodata\Library\Model\IndexV2\Conquest();
        $oConquest->world = $oCity->world;
        $oConquest->cs_killed = false;
        $oConquest->first_attack_date = $oCity->parsed_date;

        // Besieged town info
        $oConquest->town_id = $oConquestDetails->siegeTownId;
        $oConquest->town_name = $oConquestDetails->siegeTownName;

        // Check conquest history for besieged town
        $oMatchingTownConquest = null;
        try {
          $oWorld = World::getWorldById($oCity->world);
          $LastAttack = Carbon::parse($oCity->parsed_date, $oWorld->php_timezone);
          $aTownConquests = \Grepodata\Library\Controller\Conquest::getTownConquests($oConquest->town_id, $oCity->world);
          if (!empty($aTownConquests)) {
            foreach ($aTownConquests as $oTownConquest) {
              $oConquestTime = Carbon::parse($oTownConquest->time)->setTimezone($oWorld->php_timezone);

              $DiffToSiege = $LastAttack->diffInMinutes($oConquestTime, false);
              if ($DiffToSiege > 0 && $DiffToSiege < 24 * 60) {
                // TODO use world speed to shorten this search interval = more accurate
                // Conquest is within 20 hours AFTER the attack on the siege
                // assume that the siege was successful and the town now has a new owner
                $oConquest->new_owner_player_id = $oTownConquest->n_p_id;
                $oMatchingTownConquest = $oTownConquest;
                break;
              }

            }
          }
        } catch (Exception $e) {
          Logger::warning("SiegeParser $ReportHash: error parsing town conquests for new siege; " . $e->getMessage());
        }

        // Besieged town owner info
        if (key_exists('player_id', $aReceiverDetails)) {
          // get town ownership from inbox data
          $oConquest->player_id = $aReceiverDetails['player_id'];
          $oConquest->player_name = $aReceiverDetails['player_name'];
          $oConquest->alliance_id = $aReceiverDetails['alliance_id'];
          $oConquest->alliance_name = $aReceiverDetails['alliance_name'];
        } else {
          if ($oMatchingTownConquest != null) {
            // Get town ownership from matching conquest record
            $oConquest->player_id = $oMatchingTownConquest->o_p_id ?? 0;
            $oPlayer = $oConquest->player_id > 0 ? Player::firstById($oCity->world, $oConquest->player_id) : null;
            $oConquest->player_name = !empty($oPlayer) ? $oPlayer->name : '';
            $oConquest->alliance_id = $oMatchingTownConquest->o_a_id ?? 0;
            $oAlliance = $oConquest->alliance_id > 0 ? Alliance::first($oConquest->alliance_id, $oCity->world) : null;
          } else {
            // get town ownership from current database state
            $oTown = Town::firstById($oCity->world, $oConquest->town_id);
            $oConquest->player_id = $oTown->player_id;
            $oPlayer = $oTown->player_id > 0 ? Player::firstById($oCity->world, $oTown->player_id) : null;
            $oConquest->player_name = !empty($oPlayer) ? $oPlayer->name : '';
            $oConquest->alliance_id = !empty($oPlayer) ? $oPlayer->alliance_id : 0;
            $oAlliance = !empty($oPlayer) && $oPlayer->alliance_id > 0 ? Alliance::first($oPlayer->alliance_id, $oCity->world) : null;
          }
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
          if ($oMatchingTownConquest != null) {
            // Get new owner from conquest info
            $oConquest->belligerent_player_id = $oMatchingTownConquest->n_p_id;
            $oPlayer = Player::firstOrFail($oConquest->belligerent_player_id, $oCity->world);
            $oConquest->belligerent_player_name = $oPlayer->name;
            $oConquest->belligerent_alliance_id = $oMatchingTownConquest->n_a_id;
            $oAlliance = Alliance::firstOrFail($oConquest->belligerent_alliance_id, $oCity->world);
            $oConquest->belligerent_alliance_name = $oAlliance->name;
          } else {
            // find alliance id in database
            $oPlayer = Player::firstOrFail($oConquestDetails->siegePlayerId, $oCity->world);
            $oConquest->belligerent_alliance_id = $oPlayer->alliance_id;
            $oAlliance = Alliance::firstOrFail($oPlayer->alliance_id, $oCity->world);
            $oConquest->belligerent_alliance_name = $oAlliance->name;
          }
        } catch (ModelNotFoundException $e) {
          Logger::debugInfo("SiegeParser $ReportHash: player or alliance model of belligerent not found; " . $e->getMessage());
        } catch (Exception $e) {
          Logger::warning("SiegeParser $ReportHash: error parsing alliance for new siege; " . $e->getMessage());
        }
      }

      if ($oConquest == null) {
        throw new Exception("Unable to find or create conquest object");
      }

      $Saved = $oConquest->save();

      // Update all conquest overviews with the new intel
      $bCsIsKilled = false;
      if ($Saved) {
        $ConquestId = $oConquest->id;
        foreach ($aIndexKeys as $IndexKey) {
          try {
            /** @var ConquestOverview $oConquestOverview */
            $oConquestOverview = ConquestOverview::firstOrNew(array(
              'conquest_id' => $ConquestId,
              'index_key' => $IndexKey
            ));
            if ($oConquestOverview->uid == null) {
              $oConquestOverview->uid = md5(IndexBuilderV2::generateIndexKey(32) . time());
            }
            $oConquestOverview->num_attacks_counted += 1;

            $aTotalLossesAtt = array();
            $aTotalLossesDef = array();
            if (!empty($oConquestOverview->total_losses_att) && $oConquestOverview->total_losses_att != "[]") {
              $aTotalLossesAtt = json_decode($oConquestOverview->total_losses_att, true);
            }
            if (!empty($oConquestOverview->total_losses_def) && $oConquestOverview->total_losses_def != "[]") {
              $aTotalLossesDef = json_decode($oConquestOverview->total_losses_def, true);
            }
            $aAttUnits = \Grepodata\Library\Controller\IndexV2\Intel::getMergedUnits($oCity);
            foreach ($aAttUnits as $Key => $Value) {
              preg_match('/\(-[0-9]{1,6}\)/', $Value, $aMatch);
              if (!empty($aMatch)) {
                $LostUnits = abs((int) filter_var($aMatch[0], FILTER_SANITIZE_NUMBER_INT));
                if (!is_nan($LostUnits)) {
                  if (!isset($aTotalLossesAtt[$Key])) $aTotalLossesAtt[$Key] = 0;
                  $aTotalLossesAtt[$Key] += $LostUnits;
                }
              }
            }
            $aDefUnits = $oConquestDetails->siegeUnits;
            foreach ($aDefUnits as $Key => $Value) {
              $Count = 0;
              $Killed = 0;
              $KilledNew = 0;
              $aParts = explode('(', $Value);
              if (!empty($aParts) && isset($aParts[0])) {
                $Count = trim($aParts[0]);
                if (isset($aParts[1])) {
                  $KilledRaw = str_replace(')', '', trim($aParts[1]));
                  $KilledRaw = str_replace('-', '', $KilledRaw);

                  // Parse thousands indicator:
                  if (strpos($KilledRaw, 'k')!==false) {
                    $KilledRaw = str_replace('k', '', $KilledRaw);
                    if (strpos($KilledRaw, '.')!==false||strpos($KilledRaw, ',')!==false) {
                      $KilledRaw = str_replace('.', '', $KilledRaw);
                      $KilledRaw = str_replace(',', '', $KilledRaw);
                      $KilledRaw .= '00';
                    } else {
                      $KilledRaw .= '000';
                    }
                  }
                  if (is_numeric($KilledRaw)) {
                    $LostUnits = (int) $KilledRaw;
                    if (!is_nan($LostUnits) && $LostUnits > 0) {
                      if (!isset($aTotalLossesDef[$Key])) $aTotalLossesDef[$Key] = 0;
                      $aTotalLossesDef[$Key] += $LostUnits;
                      if ($Key === 'colonize_ship') {
                        $bCsIsKilled = true;
                      }
                    }
                  }

                }
              }
            }
            $oConquestOverview->total_losses_att = json_encode($aTotalLossesAtt);
            $oConquestOverview->total_losses_def = json_encode($aTotalLossesDef);

            // Add alliance to belligerents if needed
            if (!empty($oCity->alliance_id)
              && $oCity->alliance_id > 0
            ) {
              try {
                $oAlliance = Alliance::firstOrFail($oCity->alliance_id, $oCity->world);

                $aBelligerentAll = array();
                if (!empty($oConquestOverview->belligerent_all) && $oConquestOverview->belligerent_all != "[]") {
                  $aBelligerentAll = json_decode($oConquestOverview->belligerent_all, true);
                }
                $aBelligerentAll[$oAlliance->grep_id] = array(
                  'alliance_id' => $oAlliance->grep_id,
                  'alliance_name' => $oAlliance->name,
                );
                $oConquestOverview->belligerent_all = json_encode($aBelligerentAll);
              } catch (Exception $e) {}
            }

            $bDoSave = false;
            if (!bDevelopmentMode || $bDoSave) {
              $oConquestOverview->save();
            }

          } catch (Exception $e) {
            Logger::warning("SiegeParser $ReportHash: error adding to conquest overview for siege; " . $e->getMessage());
          }
        }
      }
      // END create index conquest overview

      if ($bCsIsKilled) {
        $oConquest->cs_killed = true;
        $oConquest->new_owner_player_id = $oConquest->player_id; // player keeps town
        $oConquest->save();
      }

      return $oConquest->id;

    } catch (\Exception $e) {
      Logger::warning("SiegeParser $ReportHash: error parsing conquest details for siege; " . $e->getMessage());
    }
    return null;
  }

}

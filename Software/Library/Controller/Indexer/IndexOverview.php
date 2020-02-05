<?php

namespace Grepodata\Library\Controller\Indexer;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Player;
use Illuminate\Database\Eloquent\Model;

class IndexOverview
{

  /**
   * @param $Key string Index identifier
   * @return \Grepodata\Library\Model\Indexer\IndexOverview
   */
  public static function firstOrFail($Key)
  {
    return \Grepodata\Library\Model\Indexer\IndexOverview::where('key_code', '=', $Key)
      ->firstOrFail();
  }

  /**
   * @param $Key string Index identifier
   * @return \Grepodata\Library\Model\Indexer\IndexOverview | Model
   */
  public static function firstOrNew($Key)
  {
    return \Grepodata\Library\Model\Indexer\IndexOverview::firstOrNew(array(
      'key_code'    => $Key
    ));
  }

  /**
   * @param $Key string Index identifier
   * @return array
   */
  public static function getOwnerAllianceIds($Key)
  {
    $oIndexOverview = \Grepodata\Library\Model\Indexer\IndexOverview::where('key_code', '=', $Key)->first();
    $aOwnerIds = array();
    if ($oIndexOverview != null && $oIndexOverview != false) {
      $aOwners = json_decode($oIndexOverview->owners, true);
      foreach ($aOwners as $aOwner) {
        $aOwnerIds[] = $aOwner['alliance_id'];
      }
    }
    return $aOwnerIds;
  }

  /**
   * @param $oIndex \Grepodata\Library\Model\Indexer\IndexInfo
   */
  public static function buildIndexOverview($oIndex)
  {
    $Key = $oIndex->key_code;
    $oWorld = World::getWorldById($oIndex->world);
    $aCityRecords = City::where('index_key', '=', $Key)->orderBy('id', 'desc')->get();

    $aOldOwners = IndexOverview::getOwnerAllianceIds($oIndex->key_code);

    // Process all records
    $aOwners = array();
    $aContributors = array();
    $aIndexedAlliances = array();
    $aIndexedPlayers = array();
    $aRecentIntel = array();
    $LatestUpdate = '0';
    $numSpies = 0;
    $numAttacking = 0;
    $numAttacked = 0;
    $numTotal = 0;
    $aBuildings = array();
    /** @var City $oCity */
    foreach ($aCityRecords as $oCity) {
      if (strtotime($oCity->created_at) > strtotime($LatestUpdate)) $LatestUpdate = $oCity->created_at;

      // Check owners
      if ($oCity->poster_alliance_id !== null) {
        if (isset($aOwners[$oCity->poster_alliance_id])) {
          $aOwners[$oCity->poster_alliance_id] += 1;
        } else {
          $aOwners[$oCity->poster_alliance_id] = 1;
        }

        if (isset($aContributors[$oCity->poster_player_id])) {
          $aContributors[$oCity->poster_player_id] += 1;
        } else {
          $aContributors[$oCity->poster_player_id] = 1;
        }
      }

      // Check indexed alliance
      if ($oCity->alliance_id !== null) {
        if (isset($aIndexedAlliances[$oCity->alliance_id])) $aIndexedAlliances[$oCity->alliance_id] += 1;
        else $aIndexedAlliances[$oCity->alliance_id] = 1;
      }

      // Check indexed players
      if ($oCity->player_id !== null) {
        if (isset($aIndexedPlayers[$oCity->player_id])) $aIndexedPlayers[$oCity->player_id] += 1;
        else $aIndexedPlayers[$oCity->player_id] = 1;
      }

      // Counts
      switch($oCity->report_type) {
        case 'friendly_attack':
          $numAttacking += 1; break;
        case 'enemy_attack':
          $numAttacked += 1; break;
        case 'attack_on_conquest':
          $numAttacked += 1; break;
        case 'spy':
          $numSpies += 1; break;
        default:
          $numTotal += 1;
          break;
      }

      if (sizeof($aRecentIntel)<20 && !in_array($oCity->alliance_id, $aOldOwners)) {
        $aFormatted = CityInfo::formatAsTownIntel($oCity, $oWorld, $aBuildings);
        $aFormatted['town_id'] = $oCity->town_id;
        $aFormatted['town_name'] = $oCity->town_name;
        $aFormatted['player_id'] = $oCity->player_id;
        $aFormatted['player_name'] = $oCity->player_name;
        $aFormatted['alliance_id'] = $oCity->alliance_id;
        $aFormatted['alliance_name'] = Alliance::first($oCity->alliance_id, $oWorld->grep_id)->name ?? '';
        $aRecentIntel[] = $aFormatted;
      }
    }

    // Handle results
    $numTotal = $numTotal + $numAttacked + $numAttacking + $numSpies;
    arsort($aOwners);
    $aRealOwners = array();
    $numOwned = 0;
    foreach ($aOwners as $Owner => $Count) { $numOwned = $numOwned + $Count; }
    foreach ($aOwners as $Owner => $Count) {
      // Real owners have atleast a 5% contribution
      if ($Count >= (0.05 * $numOwned)) {
        try {
          $oAlliance = Alliance::first($Owner, $oIndex->world);
          if ($oAlliance !== null) {
            $aRealOwners[] = array(
              'alliance_id' => $Owner,
              'alliance_name' => $oAlliance->name,
              'contributions' => ($Count / $numOwned) * 100,
            );
          }
        } catch (\Exception $e) {}
      }
    }

    $aRealContributors = array();
    arsort($aContributors);
    $aContributors = array_slice($aContributors, 0, 20, true);
    foreach ($aContributors as $Contributor => $Count) {
      if ($Contributor == '0') {
        continue;
      }
      try {
        $oPlayer = \Grepodata\Library\Controller\Player::first($Contributor, $oIndex->world);
        if ($oPlayer !== null) {
          $isOwner = true;
//          $isOwner = false;
//          foreach ($aRealOwners as $Owner) {
//            if ($oPlayer->alliance_id == $Owner['alliance_id']) $isOwner = true;
//          }
          if ($isOwner) {
            $aRealContributors[] = array(
              'player_id' => $Contributor,
              'player_name' => $oPlayer->name,
              'count' => $Count,
            );
          }
        }
      } catch (\Exception $e) {}
    }


    // Check owner custom exceptions
    try {
      $aOwnersComputed = $aRealOwners;
      $oCustomOwners = IndexOwners::firstOrFail($oIndex->key_code);
      if ($oCustomOwners == null || $oCustomOwners == false) throw new \Exception('No custom owners for index ' . $oIndex->key_code);
      $oCustomOwners->setOwnersGenerated($aRealOwners);

      // handle inclusions
      $aIncludes = json_decode($oCustomOwners->getOwnersIncluded());
      if ($aIncludes != null && is_array($aIncludes)) {
        foreach ($aIncludes as $aInclude) {
          $aInclude = (array)$aInclude;
          $bIncluded = false;
          foreach ($aOwnersComputed as $aRealOwner) {
            if ($aRealOwner['alliance_id'] == $aInclude['alliance_id']) {
              $bIncluded = true;
            }
          }
          if ($bIncluded === false) {
            $aOwnersComputed[] = array(
              'alliance_id' => $aInclude['alliance_id'],
              'alliance_name' => $aInclude['alliance_name'],
              'contributions' => 0,
            );
          }
        }
      }

      // Handle exclusions
      $aExcludes = json_decode($oCustomOwners->getOwnersExcluded());
      if ($aExcludes != null && is_array($aExcludes)) {
        $aOwnersExcluded = array();
        foreach ($aOwnersComputed as $aRealOwner) {
          $bExcluded = false;
          foreach ($aExcludes as $aExclude) {
            $aExclude = (array)$aExclude;
            if ($aRealOwner['alliance_id'] == $aExclude['alliance_id']) {
              $bExcluded = true;
            }
          }
          if ($bExcluded === false) {
            $aOwnersExcluded[] = $aRealOwner;
          }
        }
        $aOwnersComputed = $aOwnersExcluded;
      }

      // Save results
      $oCustomOwners->setOwnersComputed($aOwnersComputed);
      $aRealOwners = $aOwnersComputed;
      $oCustomOwners->save();
    } catch (\Exception $e) {
//      Logger::warning('error building index overview: ' . $e->getMessage());
    }

    // Handle indexed alliances
    $aRealAlliancesIndexed = array();
    arsort($aIndexedAlliances);
    $aIndexedAlliances = array_slice($aIndexedAlliances, 0, 15, true);
    foreach ($aIndexedAlliances as $Alliance => $Count) {
      if ($Alliance == '0') continue;
      $isOwner = false;
      foreach ($aRealOwners as $Owner) {
        if ($Alliance == $Owner['alliance_id']) $isOwner = true;
      }
      if (!$isOwner) {
        try {
          $oAlliance = Alliance::first($Alliance, $oIndex->world);
          if ($oAlliance !== null) {
            $aRealAlliancesIndexed[] = array(
              'alliance_id' => $Alliance,
              'alliance_name' => $oAlliance->name,
              'count' => $Count,
            );
          }
        } catch (\Exception $e) {}
      }
    }

    $aRealPlayersIndexed = array();
    arsort($aIndexedPlayers);
    $aIndexedPlayers = array_slice($aIndexedPlayers, 0, 30, true);
    foreach ($aIndexedPlayers as $Player => $Count) {
      if ($Player == '0') {
        $aRealPlayersIndexed[] = array(
          'player_id' => $Player,
          'player_name' => 'Ghost',
          'count' => $Count,
        );
        continue;
      }
      try {
        $oPlayer = \Grepodata\Library\Controller\Player::first($Player, $oIndex->world);
        if ($oPlayer !== null) {
          $isOwner = false;
          foreach ($aRealOwners as $Owner) {
            if ($oPlayer->alliance_id == $Owner['alliance_id']) $isOwner = true;
          }
          if (!$isOwner) {
            $aRealPlayersIndexed[] = array(
              'player_id' => $Player,
              'player_name' => $oPlayer->name,
              'count' => $Count,
            );
          }
        }
      } catch (\Exception $e) {}
    }

    // Check max version
    try {
      $v = \Grepodata\Library\Model\Indexer\Report::where('index_code','=',$oIndex->key_code)->max('script_version');
      if ($v===null || $v==='') {
        $v = USERSCRIPT_VERSION;
      }
    } catch (\Exception $e) {
      $v = USERSCRIPT_VERSION;
    }


    // Convert latest report time to server timezone
    $oWorld = World::getWorldById($oIndex->world);
    if (sizeof($aCityRecords) > 0) $LatestUpdate = World::utcTimestampToServerTime($oWorld, strtotime($LatestUpdate));

    $oIndexOverview = IndexOverview::firstOrNew($Key);
    $oIndexOverview->key_code           = $Key;
    $oIndexOverview->world              = $oIndex->world;
    if (sizeof($aCityRecords) > 0) $oIndexOverview->latest_report = $LatestUpdate;
    $oIndexOverview->owners             = json_encode($aRealOwners);
    $oIndexOverview->contributors       = json_encode($aRealContributors);
    $oIndexOverview->alliances_indexed  = json_encode($aRealAlliancesIndexed);
    $oIndexOverview->players_indexed    = json_encode($aRealPlayersIndexed);
    $oIndexOverview->latest_intel       = json_encode($aRecentIntel);
    $oIndexOverview->total_reports      = $numTotal;
    $oIndexOverview->spy_reports        = $numSpies;
    $oIndexOverview->enemy_attacks      = $numAttacked;
    $oIndexOverview->friendly_attacks   = $numAttacking;
    $oIndexOverview->max_version        = $v;
    $oIndexOverview->save();

  }

}
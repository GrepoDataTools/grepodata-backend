<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\Player;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Intel extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns all intel collected by this user, ordered by index date
   */
  public static function GetIntelForUserGet()
  {
    try {
      $aParams = self::validateParams(array('access_token'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $From = $aParams['from'] ?? 0;
      $Size = $aParams['size'] ?? 20;

      if ($From > 1000 || $Size > 50) {
        throw new ModelNotFoundException();
      }
      $Start = round(microtime(true) * 1000);
      $aIntelResult = \Grepodata\Library\Controller\IndexV2\Intel::allByUser($oUser, $From, $Size);
      $aIntel = $aIntelResult[0];
      $Total = $aIntelResult[1];
      $ElapsedMs = round(microtime(true) * 1000) - $Start;

      $aIntelData = array();
      $aWorlds = array();
      foreach ($aIntel as $oIntel) {
        try {
          if (!key_exists($oIntel->world, $aWorlds)) {
            $aWorlds[$oIntel->world] = World::getWorldById($oIntel->world);
          }
          $aBuildings = array();
          $aTownIntelRecord = \Grepodata\Library\Controller\IndexV2\Intel::formatAsTownIntel($oIntel, $aWorlds[$oIntel->world], $aBuildings);
          $aTownIntelRecord['source_type'] = $oIntel->source_type;
          $aTownIntelRecord['parsed'] = ($oIntel->parsing_failed==0?true:false);
          $aTownIntelRecord['world'] = $oIntel->world;
          $aTownIntelRecord['town_id'] = $oIntel->town_id;
          $aTownIntelRecord['town_name'] = $oIntel->town_name;
          $aTownIntelRecord['player_id'] = $oIntel->player_id;
          $aTownIntelRecord['player_name'] = $oIntel->player_name;
          $aTownIntelRecord['alliance_id'] = $oIntel->alliance_id;
          $aTownIntelRecord['alliance_name'] = Alliance::first($oIntel->alliance_id, $oIntel->world)->name ?? '';
          if (isset($oIntel['shared_via_indexes'])) {
            $aTownIntelRecord['shared_via_indexes'] = $oIntel['shared_via_indexes'];
          }
          $aIntelData[] = $aTownIntelRecord;
        } catch (\Exception $e) {
          Logger::warning("Unable to render user intel record: " . $e->getMessage());
        }
      }

      $aResponse = array(
        'query_ms'    => $ElapsedMs,
        'batch_size'  => sizeof($aIntel),
        'total_items' => $Total,
        'items'       => $aIntelData
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetTownGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'town_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // get world
      $oWorld = World::getWorldById($aParams['world']);

      // get town
      $oTown = Town::firstOrFail($aParams['town_id'], $oWorld->grep_id);

      // get intel
      $Start = round(microtime(true) * 1000);
      $aIntel = \Grepodata\Library\Controller\IndexV2\Intel::allByUserForTown($oUser, $oWorld->grep_id, $oTown->grep_id, true);
      $ElapsedMs = round(microtime(true) * 1000) - $Start;

      // Parse cities
      $oNow = Carbon::now();
      $aResponse = array(
        'query_ms' => $ElapsedMs,
        'world' => $oWorld->grep_id,
        'town_id' => $oTown->grep_id,
        'name' => $oTown->name,
        'ix' => $oTown->island_x,
        'iy' => $oTown->island_y,
        'player_id' => $oTown->player_id,
        'alliance_id' => 0,
        'player_name' => '',
        'alliance_name' => '',
        'has_stonehail' => false,
        'notes' => array(),
        'buildings' => array(),
        'intel' => array(),
        'teams' => array(),
        'latest_version' => USERSCRIPT_VERSION,
        'update_message' => USERSCRIPT_UPDATE_INFO,
      );
      $bHasIntel = false;
      $aDuplicateCheck = array();
      /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
      foreach ($aIntel as $oCity) {
        if ($oCity->soft_deleted != null) {
          $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
          if ($oNow->diffInHours($oSoftDeleted) > 24) {
            continue;
          }
        }
        $bHasIntel = true;

        // add teams to set
        if ($oCity->shared_via_indexes!=null) {
          $aTeams = explode(', ', $oCity->shared_via_indexes);
          foreach ($aTeams as $Team) {
            if (!in_array($Team, $aResponse['teams'])) {
              $aResponse['teams'][] = $Team;
            }
          }
        }

        // Override newest info
        $aResponse['player_id'] = $oCity->player_id;
        $aResponse['player_name'] = $oCity->player_name;
        $aResponse['alliance_id'] = $oCity->alliance_id;
        $aResponse['name'] = $oCity->town_name;
        $aResponse['latest_version'] = $oCity->script_version;

        $citystring = "_".$oCity->town_id.$oCity->parsed_date;
        $cityhash = md5($citystring);
        if (!in_array($cityhash, $aDuplicateCheck)) {
          $aDuplicateCheck[] = $cityhash;

          $aRecord = \Grepodata\Library\Controller\IndexV2\Intel::formatAsTownIntel($oCity, $oWorld, $aResponse['buildings']);
          if (!empty($aRecord['stonehail'])) {
            $aResponse['has_stonehail'] = true;
          }

          if (isset($oCity['shared_via_indexes'])) {
            $aRecord['shared_via_indexes'] = $oCity['shared_via_indexes'];
          }
          if (isset($oCity['indexed_by_users'])) {
            $aRecord['indexed_by_users'] = $oCity['indexed_by_users'];
          }

          $aResponse['intel'][] = $aRecord;
        }
      }
      $aResponse['has_intel'] = $bHasIntel;

      if ($bHasIntel == false) {
        if ($oTown->player_id > 0) {
          $oPlayer = \Grepodata\Library\Controller\Player::firstById($oWorld->grep_id, $oTown->player_id);
          if ($oPlayer !== false) {
            $aResponse['player_name'] = $oPlayer->name;
            $aResponse['alliance_id'] = $oPlayer->alliance_id;
          }
        }
      }

      // Get notes
      $aNotes = \Grepodata\Library\Controller\IndexV2\Notes::allByUserForTown($oUser, $oWorld->grep_id, $oTown->grep_id);
      $aDuplicates = array();
      /** @var \Grepodata\Library\Model\IndexV2\Notes $Note */
      foreach ($aNotes as $Note) {
        $aNote = $Note->getPublicFields();
        $Created = $Note->created_at;
        $Created->setTimezone($oWorld->php_timezone);
        $aNote['date'] = $Created->format('d-m-y H:i');
        if (!in_array($Note->note_id, $aDuplicates)) {
          $aResponse['notes'][] = $aNote;
          $aDuplicates[] = $Note->note_id;
        }
      }

      // Sort intel by sort_date descending
      //$aResponse['intel'] = array_reverse($aResponse['intel']);
      usort($aResponse['intel'], function ($a, $b) {
        return ($a['sort_date'] > $b['sort_date']) ? -1 : 1;
      });

      // Give newest record a cost boost
      if (sizeof($aResponse['intel'])>0) {
        $aResponse['intel'][0]['cost'] *= 5;
      }

      // try to expand teams
      $aTeams = $aResponse['teams'];
      $aParsedTeams = array();
      foreach ($aTeams as $Team) {
        try {
          $oIndex = IndexInfo::where('key_code', '=', $Team)->firstOrFail();
          $aParsedTeams[] = array(
            'index_key' => $oIndex->key_code,
            'index_name' => $oIndex->index_name,
          );
        } catch (Exception $e) {}
      }
      $aResponse['teams'] = $aParsedTeams;

      // get alliance
      try {
        $oAlliance = Alliance::firstOrFail($aResponse['alliance_id'], $oWorld->grep_id);
        $aResponse['alliance_name'] = $oAlliance->name;
      } catch (Exception $e) {}

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

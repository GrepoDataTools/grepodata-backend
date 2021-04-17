<?php

namespace Grepodata\Application\API\Route\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\IndexV2\Intel;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexApiV1 extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * This backwards compatible route ensures that V1 town intel remains available to the old userscript.
   * Once the majority of users have updated to the V2 script, this route will be removed.
   * @return false|string
   * @throws Exception
   * @deprecated
   */
  public static function GetTownGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams();
      Logger::v2Migration("GetTownV1 ".json_encode($aParams));

      $aInputKeys = array();
      if (isset($aParams['key'])) {
        $aInputKeys[] = $aParams['key'];
      } else if (isset($aParams['keys'])) {
        $aKeys = json_decode($aParams['keys']);
        $aInputKeys = $aKeys;
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: key or keys'
        ), 400));
      }

      /** @var IndexInfo[] $aIndexList */
      $aIndexList = array();
      foreach ($aInputKeys as $Key) {
        // Validate indexes with rerouting
        $SearchKey = $Key;
        $bValidIndex = false;
        $oIndex = null;
        $Attempts = 0;
        while (!$bValidIndex && $Attempts <= 30) {
          $Attempts += 1;
          $oIndex = Validator::IsValidIndex($SearchKey);
          if ($oIndex === null || $oIndex === false) {
            continue 2;
          }
          if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
            if ($oIndex->moved_to_index == 'deleted1') {
              continue 2;
            }
            $SearchKey = $oIndex->moved_to_index; // redirect to new index
          } else {
            $bValidIndex = true;
          }
        }
        $aIndexList[] = $oIndex;
      }
      $oPrimaryIndex = $aIndexList[0];

      // World
      /** @var \Grepodata\Library\Model\World $oWorld */
      $oWorld = World::getWorldById($oPrimaryIndex->world);

      $TownId = 0;
      if (isset($aParams['id']) && $aParams['id'] != '') {
        // Get by id
        $TownId = $aParams['id'];
      } elseif (isset($aParams['name']) && $aParams['name'] != '') {
        throw new ModelNotFoundException();
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: id or name'
        ), 400));
      }

      // Get town
      $oTown = Town::firstById($oWorld->grep_id, $TownId);
      if ($oTown == false || is_null($oTown)) {
        throw new ModelNotFoundException();
      }

      // Find cities
      $aRawKeys = array();
      foreach ($aIndexList as $oIndex) {
        $aRawKeys[] = $oIndex->key_code;
      }
      $aCities = Intel::allByTownIdByV1IndexKeys($aRawKeys, $TownId);

      // Parse cities
      $oNow = Carbon::now();
      $aResponse = array(
        'world' => $oWorld->grep_id,
        'town_id' => $TownId,
        'name' => $oTown->name,
        'ix' => $oTown->island_x,
        'iy' => $oTown->island_y,
        'player_id' => $oTown->player_id,
        'alliance_id' => 0,
        'player_name' => '',
        'has_stonehail' => false,
        'notes' => array(),
        'buildings' => array(),
        'intel' => array(),
        'latest_version' => $oPrimaryIndex->script_version,
        'update_message' => USERSCRIPT_UPDATE_INFO,
      );
      $bHasIntel = false;
      $aDuplicateCheck = array();
      /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
      foreach ($aCities as $oCity) {
        if ($oCity->soft_deleted != null) {
          $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
          if ($oNow->diffInHours($oSoftDeleted) > 24) {
            continue;
          }
        }
        $bHasIntel = true;

        // Override newest info
        $aResponse['player_id'] = $oCity->player_id;
        $aResponse['player_name'] = $oCity->player_name;
        $aResponse['alliance_id'] = $oCity->alliance_id;
        $aResponse['name'] = $oCity->town_name;

        $citystring = "_".$oCity->town_id.$oCity->parsed_date;
        $cityhash = md5($citystring);
        if (!in_array($cityhash, $aDuplicateCheck)) {
          $aDuplicateCheck[] = $cityhash;

          $aRecord = Intel::formatAsTownIntel($oCity, $oWorld, $aResponse['buildings']);
          if (!empty($aRecord['stonehail'])) {
            $aResponse['has_stonehail'] = true;
          }

          $aResponse['intel'][] = $aRecord;
        }
      }
      $aResponse['has_intel'] = $bHasIntel;

      if ($bHasIntel == false) {
        if ($oTown->player_id > 0) {
          $oPlayer = Player::firstById($oPrimaryIndex->world, $oTown->player_id);
          if ($oPlayer !== false) {
            $aResponse['player_name'] = $oPlayer->name;
            $aResponse['alliance_id'] = $oPlayer->alliance_id;
          }
        }
      }

      // Find notes
      try {
        $aNotes = \Grepodata\Library\Controller\IndexV2\Notes::allByTownIdByKeys($aRawKeys, $TownId);
        $aDuplicates = array();
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
      } catch (Exception $e) {}

      // Sort intel by sort_date descending
      //$aResponse['intel'] = array_reverse($aResponse['intel']);
      usort($aResponse['intel'], function ($a, $b) {
        return ($a['sort_date'] > $b['sort_date']) ? -1 : 1;
      });

      // Give newest record a cost boost
      if (sizeof($aResponse['intel'])>0) {
        $aResponse['intel'][0]['cost'] *= 5;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

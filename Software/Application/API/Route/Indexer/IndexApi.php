<?php

namespace Grepodata\Application\API\Route\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexApi extends \Grepodata\Library\Router\BaseRoute
{
  public static function DeleteGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('csa', 'key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Index has moved!'
        ), 200));
      }

      // Validate CSA
      if (!isset($oIndex->csa) || $oIndex->csa == null || $oIndex->csa == '' || $oIndex->csa != $aParams['csa']) {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Invalid csa token'
        ), 200));
      }

      // Set soft delete
      $oCity = CityInfo::getById($aParams['key'], $aParams['id']);
      $oCity->soft_deleted = Carbon::now();
      $oCity->save();

      return self::OutputJson(array('success' => true));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function DeleteUndoGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('csa', 'key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Index has moved!'
        ), 200));
      }

      // Validate CSA
      if (!isset($oIndex->csa) || $oIndex->csa == null || $oIndex->csa == '' || $oIndex->csa != $aParams['csa']) {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Invalid csa token'
        ), 200));
      }

      // Undo soft delete
      $oCity = CityInfo::getById($aParams['key'], $aParams['id']);
      $oCity->soft_deleted = null;
      $oCity->save();

      return self::OutputJson(array('success' => true));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found for this id.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetTownGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams();

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
            die(self::OutputJson(array(
              'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
            ), 401));
          }
          if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
            $SearchKey = $oIndex->moved_to_index; // redirect to new index
          } else {
            $bValidIndex = true;
          }
        }
        $aIndexList[] = $oIndex;
      }
      $oPrimaryIndex = $aIndexList[0];

      if (isset($aParams['id']) && $aParams['id'] != '') {
        // Get by id
        try {
          if (isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], 'grepolis.com')!==false) {
            Logger::silly("Get town intel: https://grepodata.com/indexer/town/" . $aParams['key'] . "/".$oIndex->world."/" . $aParams['id'] . ". Referer: " . $_SERVER["HTTP_REFERER"]);
          }
        } catch (Exception $e) {}
      } elseif (isset($aParams['name']) && $aParams['name'] != '') {
        // Get by name
        $aTowns = CityInfo::allByName($oPrimaryIndex->key_code, $aParams['name']);
        if (!is_null($aTowns) && sizeof($aTowns) > 0) {
          /** @var City $oTown */
          $oTown = $aTowns[0];
          $aParams['id'] = $oTown->town_id;
        } else {
          throw new ModelNotFoundException();
        }
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: id or name'
        ), 400));
      }

      // World
      /** @var \Grepodata\Library\Model\World $oWorld */
      $oWorld = World::getWorldById($oPrimaryIndex->world);

      // Find cities
      $aRawKeys = array();
      foreach ($aIndexList as $oIndex) {
        $aRawKeys[] = $oIndex->key_code;
      }
      $aCities = CityInfo::allByTownIdByKeys($aRawKeys, $aParams['id']);
      if ($aCities === null || sizeof($aCities) <= 0) {
        throw new ModelNotFoundException();
      }

      $oNow = Carbon::now();
      $aResponse = array();
      $bHasIntel = false;
      $aDuplicateCheck = array();
      /** @var City $oCity */
      foreach ($aCities as $oCity) {
        if ($oCity->soft_deleted != null) {
          $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
          if ($oNow->diffInHours($oSoftDeleted) > 24) {
            continue;
          }
        }
        $bHasIntel = true;

        if (empty($aResponse)) {
          $aResponse['world'] = $oIndex->world;
          $aResponse['name'] = $oCity->town_name;
          $aResponse['town_id'] = $oCity->town_id;
          $aResponse['player_id'] = $oCity->player_id;
          $aResponse['player_name'] = $oCity->player_name;
          $aResponse['intel'] = array();
          $aResponse['buildings'] = array();
          $aResponse['latest_version'] = USERSCRIPT_VERSION;
          $aResponse['update_message'] = USERSCRIPT_UPDATE_INFO;
        }
        $aResponse['alliance_id'] = $oCity->alliance_id;

        $citystring = "_".$oCity->town_id.$oCity->parsed_date.$oCity->land_units.$oCity->sea_units.$oCity->mythical_units.$oCity->fireships.$oCity->buildings;
        $cityhash = md5($citystring);
        if (!in_array($cityhash, $aDuplicateCheck)) {
          $aDuplicateCheck[] = $cityhash;

          $aRecord = CityInfo::formatAsTownIntel($oCity, $oWorld, $aResponse['buildings']);

          $aResponse['intel'][] = $aRecord;
        }
      }

      if ($bHasIntel === false) {
        throw new ModelNotFoundException("All intel was deleted");
      }

      try {
        // Hide owner intel
        $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);
        if (isset($aResponse['alliance_id']) && $aResponse['alliance_id']!==null) {
          if (in_array($aResponse['alliance_id'], $aOwners)) {
            die(self::OutputJson(array(
              'message'     => 'No intel found on this town in this index.',
              'parameters'  => $aParams
            ), 404));
          }
        }
      } catch (Exception $e) {}

      // Sort intel by sort_date descending
      //$aResponse['intel'] = array_reverse($aResponse['intel']);
      usort($aResponse['intel'], function ($a, $b) {
        return ($a['sort_date'] > $b['sort_date']) ? -1 : 1;
      });

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }



}
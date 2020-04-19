<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Town extends \Grepodata\Library\Router\BaseRoute
{
  public static function PlayerTownsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'id'));
      $aTowns = \Grepodata\Library\Controller\Town::allByPlayer($aParams['id'], $aParams['world']);
      $aResponse = array();
      foreach ($aTowns as $oTown) {
        $aResponse[] = $oTown->getMinimalFields();
      }
//      die(json_encode($aResponse));
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetTownInfoGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'id'));
      $oTown = \Grepodata\Library\Controller\Town::firstOrFail($aParams['id'], $aParams['world']);

      $aResponse = $oTown->getPublicFields();
      $aResponse['player_name'] = '';
      $aResponse['alliance_id'] = '';
      $aResponse['alliance_name'] = '';

      try {
        if ($oTown->player_id > 0) {
          $oPlayer = \Grepodata\Library\Controller\Player::firstById($aParams['world'], $aParams['id']);
          if ($oPlayer != false && isset($oPlayer->name)) {
            $aResponse['player_name'] = $oPlayer->name;
            $aResponse['alliance_id'] = $oPlayer->alliance_id;
            $aResponse['player_data'] = $oPlayer->getPublicFields();
            if ($oPlayer->alliance_id > 0) {
              $oAlliance = \Grepodata\Library\Controller\Alliance::first($aParams['id'], $aParams['world']);
              if ($oAlliance != false && isset($oAlliance->name)) {
                $aResponse['alliance_name'] = $oAlliance->name;
                $aResponse['alliance_data'] = $oAlliance->getPublicFields();
              }
            }
          }
        }
      } catch (\Exception $e) {
        Logger::warning("Error expanding town info with params: ". json_encode($aParams));
      }

      try {
        if (isset($aParams['index_key'])) {
          // Check intel
          $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
          $oWorld = \Grepodata\Library\Controller\World::getWorldById($oIndex->world);

          $aIntel = CityInfo::allByTownIdByKeys(array($oIndex->key_code), $aParams['id']);
          $aResponse['intel'] = array();
          $aBuildings = array();
          foreach ($aIntel as $oCity) {
            $aResponse['intel'][] = CityInfo::formatAsTownIntel($oCity, $oWorld, $aBuildings);
          }

        }
      } catch (\Exception $e) {
        Logger::warning("Error expanding town info with params: ". json_encode($aParams));
      }

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /** @Unused */
  public static function AllianceTownsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'id'));
      $aResponse = \Grepodata\Library\Controller\Town::allByAlliance($aParams['id'], $aParams['world']);
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /** @Unused */
  public static function TownDetailsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'id'));
      $aResponse = \Grepodata\Library\Controller\Town::firstOrFail($aParams['id'], $aParams['world']);
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /** @Unused */
  public static function SearchGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('query', 'index'));

      $oIndex = IndexInfo::firstOrFail($aParams['index']);

      $aTowns = \Grepodata\Library\Controller\Town::search($oIndex->world, $aParams['query']);
      if ($aTowns == null || sizeof($aTowns) <= 0) throw new ModelNotFoundException();

      // Format sql results
      $aResponse = array(
        'success' => true,
        'count'   => sizeof($aTowns),
        'results' => array(),
      );
      foreach ($aTowns as $oTown) {
        $aData = $oTown->getPublicFields();
        $aData['id'] = $aData['grep_id']; unset($aData['grep_id']);
        $aData['server'] = substr($aData['world'], 0, 2);
        try {
          $oPlayer = \Grepodata\Library\Controller\Player::firstOrFail($aData['player_id'], $aData['world']);
          if ($oPlayer !== null) $aData['player_name'] = $oPlayer->name;
        } catch (\Exception $e) {}
        $aResponse['results'][] = $aData;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
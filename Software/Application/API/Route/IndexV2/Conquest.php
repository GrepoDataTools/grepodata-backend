<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\IndexV2\Event;
use Grepodata\Library\Controller\World;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Conquest extends \Grepodata\Library\Router\BaseRoute
{

  public static function UpdateConquestOverviewPOST()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'conquest_uid', 'action'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Get the conquest overview & related objects
      $oConquestOverview = \Grepodata\Library\Controller\IndexV2\Conquest::firstByUid($aParams['conquest_uid']);

      // Verify if the user is indeed an admin that can edit this overview
      IndexManagement::verifyUserIsAdmin($oUser, $oConquestOverview->index_key);

      // Execute update
      switch ($aParams['action']) {
        case 'publish':
          $oConquestOverview->published = true;
          $oConquestOverview->save();
          break;
        case 'unpublish':
          $oConquestOverview->published = false;
          $oConquestOverview->save();
          break;
        default:
          // Invalid command action
          Logger::warning('Unhandled update action for conquest overview: ' .$aParams['action']);
          ResponseCode::errorCode(8130);
      }

      // Add team event
      try {
        $oIndex = IndexInfo::first($oConquestOverview->index_key);
        $oConquest = \Grepodata\Library\Controller\IndexV2\Conquest::first($oConquestOverview->conquest_id);
        Event::addSiegeUpdateEvent($oIndex, $oUser, $aParams['action'], $oConquest);
      } catch (\Exception $e) {
        Logger::warning('Error adding siege update event: ' .$e->getMessage());
      }

      ResponseCode::success(array(
        'published' => $oConquestOverview->published
      ), 1600);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No conquest overview found for this conquest uid.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetConquestReportsGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'conquest_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $ConquestId = $aParams['conquest_id'];

      $oConquest = \Grepodata\Library\Controller\IndexV2\Conquest::getByUserByConquestId($oUser, $ConquestId);
      $oWorld = World::getWorldById($oConquest->world);

      // format response
      $aResponse = array(
        'world' => $oWorld->grep_id,
        'conquest' => \Grepodata\Library\Model\IndexV2\Conquest::getMixedConquestFields($oConquest, $oWorld),
        'intel' => array()
      );

      $bHideRemainingDefences = false;
      if (isset($aResponse['conquest']['hide_details']) && $aResponse['conquest']['hide_details'] == true) {
        $bHideRemainingDefences = true;
      }

      // Find reports
      $aReports = \Grepodata\Library\Controller\IndexV2\Intel::allByUserForConquest($oUser, $ConquestId, false);
      if (empty($aReports) || count($aReports) <= 0) {
        return self::OutputJson($aResponse);
      }

      foreach ($aReports as $oCity) {
        $aConqDetails = json_decode($oCity->conquest_details, true);
        $aAttOnConq = array(
          'date' => $oCity->parsed_date,
          'sort_date' => Carbon::parse($oCity->parsed_date),
          'attacker' => array(
            'hash' => $oCity->hash,
            'town_id' => $oCity->town_id,
            'town_name' => $oCity->town_name,
            'player_id' => $oCity->player_id,
            'player_name' => $oCity->player_name,
            'alliance_id' => $oCity->alliance_id,
            'luck' => $oCity->luck,
            'attack_type' => 'attack',
            'friendly' => false,
            'units' => \Grepodata\Library\Controller\IndexV2\Intel::parseUnitLossCount(\Grepodata\Library\Controller\IndexV2\Intel::getMergedUnits($oCity)),
          ),
          'defender' => array(
            'units' => array(),
            'wall' => $aConqDetails['wall'] ?? 0,
            'hidden' => $bHideRemainingDefences
          )
        );

        if (!empty($aAttOnConq['attacker']['units'])) {
          foreach ($aAttOnConq['attacker']['units'] as $aUnit) {
            if (isset($aUnit['name']) && (
                in_array($aUnit['name'], \Grepodata\Library\Controller\IndexV2\Intel::sea_units) ||
                $aUnit['name'] == \Grepodata\Library\Controller\IndexV2\Intel::sea_monster)) {
              $aAttOnConq['attacker']['attack_type'] = 'sea_attack';
              break;
            }
          }
        }

        if ($bHideRemainingDefences == false) {
          $aAttOnConq['defender']['units'] = \Grepodata\Library\Controller\IndexV2\Intel::splitLandSeaUnits($aConqDetails['siege_units']) ?? array();
        }

        $aResponse['intel'][] = $aAttOnConq;
      }

      // sort by sort_date desc
      usort($aResponse['intel'], function ($a, $b) {
        if ($a['sort_date'] == $b['sort_date']) {
          return 0;
        }
        return ($a['sort_date'] < $b['sort_date']) ? 1 : -1;
      });

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No reports found for this conquest.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetSiegelistGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($aParams['index_key']);

      if (isset($aParams['from'])) $From = $aParams['from']; else $From = 0;
      if (isset($aParams['size'])) $Size = $aParams['size']; else $Size = 30;

      $aConquestsList = array();

      $oWorld = World::getWorldById($oIndex->world);
      $aConquests = \Grepodata\Library\Controller\IndexV2\Conquest::allByIndex($oIndex, $From, $Size);
      foreach ($aConquests as $oConquestOverview) {
        $aConquestOverview = \Grepodata\Library\Model\IndexV2\Conquest::getMixedConquestFields($oConquestOverview, $oWorld);
        $aConquestsList[] = $aConquestOverview;
      }

      $Total = null;
      if ($From == 0) {
        $Total = \Grepodata\Library\Controller\IndexV2\Conquest::countByIndex($oIndex);
      }

      $aResponse = array(
        'success' => true,
        'batch_size'  => sizeof($aConquestsList),
        'total_items' => $Total,
        'items'       => $aConquestsList
      );

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No conquests found for this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Return a list of published sieges to display on the daily scoreboard
   * API route: /indexer/v2/siegestoday
   * Method: GET
   */
  public static function SiegesTodayGET()
  {
    $aParams = array();

    try {
      // Validate params
      $aParams = self::validateParams(array());

      // Optional world
      if (!isset($aParams['world']) || $aParams['world'] == '') {
        $aParams['world'] = DEFAULT_WORLD;
        try {
          // optional server
          $Server = DEFAULT_SERVER;
          if (isset($aParams['server']) && $aParams['server'] != '') {
            $Server = $aParams['server'];
          }

          $oLatestWorld = \Grepodata\Library\Controller\World::getLatestByServer($Server);
          $aParams['world'] = $oLatestWorld->grep_id;
        } catch (\Exception $e) {
          $aParams['world'] = DEFAULT_WORLD;
        }
      }

      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);

      $MaxDate = Carbon::now($oWorld->php_timezone);
      try {
        if (isset($aParams['date'])) {
          $MaxDate = Carbon::parse($aParams['date'], $oWorld->php_timezone)->addHours(24);
        }
      } catch (\Exception $e) {
        Logger::warning("Error parsing sieges date: ".$e->getMessage());
      }

      // Get published sieges
      $aConquestsList = array();
      try {
        $aConquests = \Grepodata\Library\Controller\IndexV2\Conquest::allRecentByWorld($aParams['world'], $MaxDate);
        foreach ($aConquests as $oConquestOverview) {
          $aConquestOverview = \Grepodata\Library\Model\IndexV2\Conquest::getMixedConquestFields($oConquestOverview, $oWorld);
          $aConquestsList[] = $aConquestOverview;
        }
      } catch (\Exception $e) {
        Logger::warning("Error loading recent sieges: ".$e->getMessage());
      }

      // Format output
      $aResponse = array(
        'count' => count($aConquestsList),
        'items' => $aConquestsList
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No recent sieges found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\User;
use Grepodata\Library\Elasticsearch\Search;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Town extends \Grepodata\Library\Router\BaseRoute
{
  public static function TownsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'x_min', 'x_max', 'y_min', 'y_max'));
      if ($aParams['x_max']-$aParams['x_min']>100) {
        ResponseCode::errorCode(9010, array('invalid_range'=>'window width exceeds 100 (x_max - x_min > 100). please specify a smaller window'), 400);
      }
      if ($aParams['y_max']-$aParams['y_min']>100) {
        ResponseCode::errorCode(9020, array('invalid_range'=>'window height exceeds 100 (y_max - y_min > 100). please specify a smaller window'), 400);
      }
      $aTowns = \Grepodata\Library\Controller\Town::allInRange($aParams['world'], $aParams['x_min'], $aParams['x_max'], $aParams['y_min'], $aParams['y_max']);
      $aResponse = array();
      foreach ($aTowns as $oTown) {
        $aResponse[] = $oTown->getMinimalFields();
      }
      $aResponse = array(
        'count' => count($aResponse),
        'items' => $aResponse
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Route: /town/radar
   * Method: GET
   * Params: world, town_id, margin
   * E.g.: https://api.grepodata.com/town/radar?world=fr159&town_id=1124&margin=1000
   */
  public static function TownRadarGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'town_id', 'margin'));

      $TownFrom = \Grepodata\Library\Controller\Town::firstById($aParams['world'], $aParams['town_id']);
      $aTowns = \Grepodata\Library\Controller\Town::allInRangeExpanded($aParams['world'], $TownFrom->absolute_x, $TownFrom->absolute_y, (int) $aParams['margin']);

      $aResponse = array();
      $margin = (int) $aParams['margin'];
      foreach ($aTowns as $oTown) {

        $dist = (int) sqrt(pow(($TownFrom->absolute_x - $oTown->absolute_x), 2) + pow(($TownFrom->absolute_y - $oTown->absolute_y), 2));

        if ($dist > $margin) {
          continue;
        }

        $aTownData = array(
          "alliance_id" => $oTown->alliance_id,
          "alliance_name" => $oTown->alliance_name,
          //"dir" => "sw",
          //"fc" => "BB5511",
          //"fx" => 65,
          //"fy" => 15,
          "id" => $oTown->grep_id,
          "name" => $oTown->name,
          "nr" => $oTown->island_i,
          //"ox" => $oTown->island_x,
          //"oy" => $oTown->island_y,
          "player_id" => $oTown->player_id > 0 ? $oTown->player_id : null,
          "player_name" => $oTown->player_name,
          "points" => $oTown->points,
          //"reservation" => null,
          "x" => $oTown->island_x,
          "y" => $oTown->island_y,
          "tp" => $oTown->player_id > 0 ? "town" : "ghost_town",
          "abs_x" => $oTown->absolute_x,
          "abs_y" => $oTown->absolute_y,
          "distance" => $dist
        );
        $aResponse[] = $aTownData;
      }

      usort($aResponse, function ($a, $b) {
        return ($a['distance'] < $b['distance']) ? -1 : 1;
      });

      $aResponse = array(
        'towns' => $aResponse
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

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
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /** @Unused */
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
          $oPlayer = \Grepodata\Library\Controller\Player::firstById($aParams['world'], $oTown->player_id);
          if ($oPlayer != false && isset($oPlayer->name)) {
            $aResponse['player_name'] = $oPlayer->name;
            $aResponse['alliance_id'] = $oPlayer->alliance_id;
            $aResponse['player_data'] = $oPlayer->getPublicFields();
            if ($oPlayer->alliance_id > 0) {
              $oAlliance = \Grepodata\Library\Controller\Alliance::first($oPlayer->alliance_id, $aParams['world']);
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
          $aResponse['intel'] = array();
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

  public static function SearchGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array(), array('access_token'));

      if (isset($aParams['query']) && strlen($aParams['query']) > 30) {
        Logger::debugInfo("Search input exceeds limit: " . substr($aParams['query'], 0, 200));
        $aParams['query'] = substr($aParams['query'], 0, 30);
      }

      // Option filter by user worlds
      if (isset($aParams['access_token'])) {
        try {
          $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

          // Get worlds user is active in
          $aWorlds = User::GetActiveWorldsByUser($oUser);

          $aParams['user_worlds'] = array();
          foreach (((array) $aWorlds) as $aWorld) {
            $aParams['user_worlds'][] = ((array) $aWorld)['world'];
          }
        } catch (\Exception $e) {}
      }

      try {
        $aElasticsearchResults = Search::FindTowns($aParams);
      } catch (\Exception $e) {}

      if (isset($aElasticsearchResults) && $aElasticsearchResults != false) {
        $aResponse = $aElasticsearchResults;
      } else {
        // SQL fallback: Find model
        $aTowns = \Grepodata\Library\Controller\Town::search($aParams['query'], 30);
        if ($aTowns == null || sizeof($aTowns) <= 0) throw new ModelNotFoundException();

        // Format sql results
        $aResponse = array(
          'success' => true,
          'count'   => sizeof($aTowns),
          'status'  => 'sql_fallback',
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
      }

      return self::OutputJson($aResponse);

    } catch (\Exception $e) {
      //ResponseCode::errorCode(GD_ERROR_6300, array(), 404);
      die(self::OutputJson(array(
        'message'     => 'No towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

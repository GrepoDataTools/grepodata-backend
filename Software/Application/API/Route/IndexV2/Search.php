<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Exception;

class Search extends \Grepodata\Library\Router\BaseRoute
{

  public static function SearchPlayersGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'query'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      if (!isset($aParams['world'])) {
        $aParams['world'] = null;
      }

      // Find cities
      $aCities = \Grepodata\Library\Controller\IndexV2\Intel::searchPlayer($oUser, $aParams['query'], $aParams['world']);
      if ($aCities === null || sizeof($aCities) <= 0) throw new Exception();

      // TODO: Block owners
      $aOwners =  array();
//      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);

      // Expand
      $aPlayers = array();
      $aCounts = array();
      /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
      foreach ($aCities as $oCity) {
        if (!isset($aPlayers[$oCity->player_id])) {
          $aPlayers[$oCity->player_id] = array(
            'name' => $oCity->player_name,
            'world' => $oCity->world,
            'alliance_id' => $oCity->alliance_id,
          );
          $aCounts[$oCity->player_id] = 1;
        } else {
          $aCounts[$oCity->player_id] = $aCounts[$oCity->player_id] + 1;
        }
      }
      arsort($aCounts);
      foreach ($aCounts as $PlayerId => $Count) {
        if (!in_array($aPlayers[$PlayerId]['alliance_id'], $aOwners)) {
          $aCounts[$PlayerId] = array(
            'name' => $aPlayers[$PlayerId]['name'],
            'id' => $PlayerId,
            'world' => $aPlayers[$PlayerId]['world'],
            'count' => $Count
          );
        } else {
          unset($aCounts[$PlayerId]); // Hide owner intel
        }
      }
      $aCounts = array_values($aCounts);

      $aResponse = array(
        'success' => true,
        'count'   => sizeof($aCounts),
        'results' => $aCounts,
      );
      return self::OutputJson($aResponse);

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No indexed players found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function SearchTownsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'query'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      if (!isset($aParams['world'])) {
        $aParams['world'] = null;
      }

      // Find cities
      $aCities = \Grepodata\Library\Controller\IndexV2\Intel::searchTown($oUser, $aParams['query'], $aParams['world']);
      if ($aCities === null || sizeof($aCities) <= 0) throw new Exception();

      // TODO: Block owners
      $aOwners =  array();
//      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);

      // Expand
      $aTowns = array();
      $aCounts = array();
      /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
      foreach ($aCities as $oCity) {
        if (!isset($aTowns[$oCity->town_id])) {
          $aTowns[$oCity->town_id] = array(
            'name' => $oCity->town_name,
            'world' => $oCity->world,
            'player_id' => $oCity->player_id,
            'player_name' => $oCity->player_name,
            'alliance_id' => $oCity->alliance_id,
          );
          $aCounts[$oCity->town_id] = 1;
        } else {
          $aCounts[$oCity->town_id] = $aCounts[$oCity->town_id] + 1;
        }
      }
      arsort($aCounts);
      foreach ($aCounts as $TownId => $Count) {
        if (!in_array($aTowns[$TownId]['alliance_id'], $aOwners)) {
          $aCounts[$TownId] = array(
            'name' => $aTowns[$TownId]['name'],
            'world' => $aTowns[$TownId]['world'],
            'player_id' => $aTowns[$TownId]['player_id'],
            'player_name' => $aTowns[$TownId]['player_name'],
            'id' => $TownId,
            'count' => $Count
          );
        } else {
          unset($aCounts[$TownId]); // Hide owner intel
        }
      }
      $aCounts = array_values($aCounts);

      $aResponse = array(
        'success' => true,
        'count'   => sizeof($aCounts),
        'results' => $aCounts,
      );
      return self::OutputJson($aResponse);


    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No indexed towns found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
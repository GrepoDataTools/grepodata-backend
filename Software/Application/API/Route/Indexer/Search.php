<?php

namespace Grepodata\Application\API\Route\Indexer;

use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\Island;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Indexer\Validator;

class Search extends \Grepodata\Library\Router\BaseRoute
{

  public static function SearchPlayersGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('query', 'key'));

      // Validate
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }

      // Find cities
      $aCities = CityInfo::searchPlayer($aParams['query'], $aParams['key']);
      if ($aCities === null || sizeof($aCities) <= 0) throw new Exception();

      // Block owners
      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);

      // Expand
      $aPlayers = array();
      $aCounts = array();
      foreach ($aCities as $oCity) {
        if (!isset($aPlayers[$oCity->player_id])) {
          $aPlayers[$oCity->player_id] = array(
            'name' => $oCity->player_name,
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
            'count' => $Count
          );
        } else {
          unset($aCounts[$PlayerId]); // Hide owner intel
        }
      }
      $aCounts = array_values($aCounts);

      $aResponse = array(
        'success' => true,
        'world'   => $oIndex->world,
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

  public static function SearchIslandsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('query', 'key'));

      // Validate
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }

      // Find island
      $oIsland = Island::first($aParams['query'], $oIndex->world);
      if ($oIsland === null || $oIsland === false) {
        die(self::OutputJson(array(
          'message'     => 'No island found',
          'parameters'  => $aParams
        ), 200));
      }

      // Find towns
      $aTowns = Town::allByIsland($oIsland);

      // Find cities
      $aCities = new \Illuminate\Database\Eloquent\Collection();
      /** @var \Grepodata\Library\Model\Town $oTown */
      foreach ($aTowns as $oTown) {
        $aTownCities = CityInfo::allByTownId($aParams['key'], $oTown->grep_id);
        $aCities = $aCities->merge($aTownCities);
      }
      if ($aCities === null || sizeof($aCities) <= 0) {
        throw new Exception();
      }

      // Block owners
      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);

      // Expand
      $aTowns = array();
      $aCounts = array();
      foreach ($aCities as $oCity) {
        if (!isset($aTowns[$oCity->town_id])) {
          $aTowns[$oCity->town_id] = array(
            'name' => $oCity->town_name,
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
        'world'   => $oIndex->world,
        'count'   => sizeof($aCounts),
        'results' => $aCounts,
      );
      return self::OutputJson($aResponse);


    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No indexed islands found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function SearchTownsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('query', 'key'));

      // Validate
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }

      // Find cities
      $aCities = CityInfo::searchTown($aParams['query'], $aParams['key']);
      if ($aCities === null || sizeof($aCities) <= 0) throw new Exception();

      // Block owners
      $aOwners = IndexOverview::getOwnerAllianceIds($aParams['key']);

      // Expand
      $aTowns = array();
      $aCounts = array();
      foreach ($aCities as $oCity) {
        if (!isset($aTowns[$oCity->town_id])) {
          $aTowns[$oCity->town_id] = array(
            'name' => $oCity->town_name,
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
        'world'   => $oIndex->world,
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
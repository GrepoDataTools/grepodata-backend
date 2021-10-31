<?php

namespace Grepodata\Application\API\Route;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Elasticsearch\Search;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\PlayerHistory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Capsule\Manager as DB;

class Player extends \Grepodata\Library\Router\BaseRoute
{
  public static function PlayerInfoGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      // Find model
      $oPlayer = \Grepodata\Library\Controller\Player::firstOrFail($aParams['id'], $aParams['world']);
      $aResponse = $oPlayer->getPublicFields();

      // Attach alliance name
      if (isset($aParams['a_name']) && ($aParams['a_name'] === true || $aParams['a_name'] === 'true')) {
        $aResponse['alliance_name'] = '';
        if ($aResponse['alliance_id'] != '') {
          try {
            $oAlliance = \Grepodata\Library\Controller\Alliance::firstOrFail($aResponse['alliance_id'], $aParams['world']);
            $aResponse['alliance_name'] = $oAlliance->name;
          } catch (ModelNotFoundException $e) {}//Ignore optional alliance name fail
        }
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GhostTownsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      // Get player data
      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      $oPlayer = \Grepodata\Library\Controller\Player::firstOrFail($aParams['id'], $aParams['world']);

      $World = DB::connection()->getPdo()->quote($aParams['world']);
      $Id = DB::connection()->getPdo()->quote($aParams['id']);

      $ConquestTimeLimit = Carbon::now();
      $bHasGhostDetails = false;
      $GhostServerTime = null;
      if (!is_null($oPlayer->ghost_time)) {
        // Conquest times are in UTC
        // Ghost time = UTC timestamp when grepodata detected account reset (this can be delayed for a few hours)
        $bHasGhostDetails = true;
        $ConquestTimeLimit = Carbon::createFromFormat('Y-m-d H:i:s', $oPlayer->ghost_time); // ghost_time = UTC
        $GhostServerTime = $ConquestTimeLimit->copy()->setTimezone($oWorld->php_timezone)->format('Y-m-d H:i:s');
        $ConquestTimeLimit = $ConquestTimeLimit->subHours(12); // correct for delay in data processing
      }

      // Pull ghost towns & conquests
      $aSelect = DB::select( DB::raw("
        select Town_ghost.grep_id as town_id, 
               Town_ghost.name as town_name, 
               Town_ghost.points as points, 
               Town_ghost.island_x as island_x, 
               Town_ghost.island_y as island_y, 
               Conquest.n_p_id, 
               Player.name as n_p_name, 
               Conquest.n_a_id, 
               Alliance.name as n_a_name,
               Conquest.time as conquest_time
        from Town_ghost
        LEFT JOIN Conquest ON Conquest.town_id = Town_ghost.grep_id AND Conquest.world = ".$World." AND Conquest.time > '".$ConquestTimeLimit."' AND Conquest.n_p_id != ".$Id."
        LEFT JOIN Player ON Player.grep_id = Conquest.n_p_id AND Player.world = ".$World."
        LEFT JOIN Alliance ON Alliance.grep_id = Conquest.n_a_id AND Alliance.world = ".$World."
        WHERE Town_ghost.player_id = ".$Id." 
        AND Town_ghost.world = ".$World."
        AND (
          Conquest.id IS NULL 
          OR Conquest.id IN (
            SELECT MIN(id) 
            FROM Conquest 
            WHERE Conquest.town_id = Town_ghost.grep_id 
              AND Conquest.world = ".$World." 
              AND Conquest.time > '".$ConquestTimeLimit."'
            )
          )
        "
      ));

      $aResponse = array(
        'has_ghost_details' => $bHasGhostDetails,
        'ghost_time' => $GhostServerTime,
        'ghost_alliance' => $oPlayer->ghost_alliance ?? 0,
        'size' => 0,
        'items' => array(),
      );
      foreach ($aSelect as $aGhostConquest) {
        $aResponse['items'][] = (array) $aGhostConquest;
      }
      $aResponse['size'] = count($aResponse['items']);

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceChangesGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      $From = 0;
      $Size = 10;
      if (isset($aParams['size']) && $aParams['size'] < 50) {
        $Size = $aParams['size'];
      }
      if (isset($aParams['from']) && $aParams['from'] < 5000) {
        $From = $aParams['from'];
      }

      // Find model
      $aAllianceChanges = \Grepodata\Library\Controller\AllianceChanges::getChangesByPlayerId($aParams['id'], $aParams['world'], $From, $Size);

      $aResponse = array(
        'count' => \Grepodata\Library\Model\AllianceChanges::where('player_grep_id', '=', $aParams['id'], 'and')
          ->where('world', '=', $aParams['world'])->count(),
        'items' => array()
      );
      foreach ($aAllianceChanges as $oAllianceChange) {
        $aChange = $oAllianceChange->getPublicFields();
        $aChange['date'] = array(
          'date' => $aChange['date']->format('Y-m-d H:i:s')
        );
        $aResponse['items'][] = $aChange;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No changes found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function PlayerHistoryGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      // Find model
      $aPlayerHistories = \Grepodata\Library\Controller\PlayerHistory::getPlayerHistory($aParams['id'], $aParams['world'], 0);
      $aResponse = array();
      /** @var PlayerHistory $oPlayerHistory */
      foreach ($aPlayerHistories as $oPlayerHistory) {
        $aResponse[] = $oPlayerHistory->getPublicFields();
      }
//      $aResponse = array_reverse($aResponse);
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player history found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function PlayerHistoryRangeGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id','from','to'));

      if ($aParams['from'] > 90) $aParams['from'] = 0;
      if ($aParams['to'] > 90) $aParams['to'] = 90;
      
      // Find model
      $aPlayerHistories = \Grepodata\Library\Controller\PlayerHistory::getPlayerHistory($aParams['id'], $aParams['world'], $aParams['to']+1);
      $aResponse = array();
      $count = 0;
      $last_date = '';
      foreach ($aPlayerHistories as $oPlayerHistory) {
        $count++;
        if ($count > $aParams['from'] && $count < $aParams['to']) {
          $aPlayer = $oPlayerHistory->getPublicFields();
          $aResponse[] = $aPlayer;
          $last_date = $aPlayer['date'];
        }
      }

      // Add filler records for full range
      try {
        $LastDate = Carbon::createFromFormat("Y-m-d", $last_date);
        while ($count < $aParams['to']) {
          $count++;
          $LastDate->subDay();
          $aResponse[] = array(
            'date' => $LastDate->toDateString(),
            'alliance_id' => 0,
            'alliance_name' => '',
            'points' => 0,
            'rank' => 0,
            'att' => 0,
            'def' => 0,
            'towns' => 0
          );
        }
      } catch (Exception $e) {}

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player history found for these parameters.',
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

      if (isset($aParams['index'])) {
        Logger::v2Migration("Received player search request with index param: ". json_encode($aParams));
        $oIndex = IndexInfo::firstOrFail($aParams['index']);
        $aParams['world'] = $oIndex->world;
      }

      if (isset($aParams['query']) && strlen($aParams['query']) > 30) {
        throw new Exception("Search input exceeds limit: " . substr($aParams['query'], 0, 200));
      }

      // Optional guild preference
      $bGuildHasServer = false;
      if (isset($aParams['guild']) && $aParams['guild'] !== '') {
        $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);
        if ($oDiscord->server !== null) {
          $bGuildHasServer = true;
          $aParams['server'] = substr($oDiscord->server, 0, 2);
        }
      }

      // Option filter by user worlds
      if (isset($aParams['access_token'])) {
        try {
          $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

          // Get worlds user is active in
          $aWorlds = User::GetActiveWorldsByUser($oUser);

          $aFilters = array();
          foreach (((array) $aWorlds) as $aWorld) {
            $aFilters[] = ((array) $aWorld)['world'];
          }
          if (!empty($aFilters)) {
            $aParams['user_worlds'] = $aFilters;
          }
        } catch (\Exception $e) {}
      }

      $bBuildForm = true;
      $bForceSql = false;
      if (isset($aParams['sql']) && $aParams['sql']==='true') {
        $bBuildForm = false;
        $bForceSql = true;
      }

      if (isset($aParams['query'])) {
        $aParams['query'] = strtolower($aParams['query']);
      }

      $aParams['active'] = 'true';
      if ($bForceSql && isset($aParams['size'])) {
        $OriginalSize = $aParams['size'];
        $aParams['size'] += 10;
      }
      try {
        $aElasticsearchResults = Search::FindPlayers($aParams, $bBuildForm);
      } catch (Exception $e) {
        $msg = "ES Player search failed with message: " . $e->getMessage() . ". params: " . urlencode(json_encode($aParams['query']));
        error_log($msg);
        Logger::warning($msg);
      }

      if (isset($aElasticsearchResults) && $aElasticsearchResults != false) {
        $aResponse = $aElasticsearchResults;
        if ($bForceSql === true) {
          $aBestMatch = null;
          $BestIndex = null;
          // if forceSql: search with ES but render results using SQL data
          foreach ($aResponse['results'] as $i => $aResult) {
            $oPlayer = \Grepodata\Library\Controller\Player::first($aResult['id'], $aResult['world']);
            if ($oPlayer!=null&&$oPlayer!=false) {
              $aData = $oPlayer->getPublicFields();
              foreach ($aData as $Field => $Value) {
                //if (!array_key_exists($Field, $aResponse['results'][$i])) {
                $aResponse['results'][$i][$Field] = $Value;
                //}
              }

              if ($aBestMatch == null
                && isset($aParams['query'])
                && strtolower($oPlayer->name) === strtolower($aParams['query'])) {
                $aBestMatch = $aResponse['results'][$i];
                $BestIndex = $i;
              }

            }
          }

          // Push best match to front of results
          if ($aBestMatch!=null) {
            unset($aResponse['results'][$BestIndex]);
            array_unshift($aResponse['results'], $aBestMatch);
          }

          if (isset($aParams['size']) && isset($OriginalSize)) {
            $aResponse['results'] = array_slice($aResponse['results'], 0, $OriginalSize);
          }
        }
      } else {
        // SQL fallback: Find model
        $aPlayers = \Grepodata\Library\Controller\Player::search($aParams);
        if ($aPlayers == null || sizeof($aPlayers) <= 0) throw new ModelNotFoundException();

        // Format sql results
        $aResponse = array(
          'success' => true,
          'count'   => sizeof($aPlayers),
          'status'  => 'sql_fallback',
          'results' => array(),
        );
        foreach ($aPlayers as $oPlayer) {
          $aData = $oPlayer->getPublicFields();
          $aData['id'] = $aData['grep_id']; unset($aData['grep_id']);
          $aData['server'] = substr($aData['world'], 0, 2);

          $aData['alliance_name'] = '';
          if ($aData['alliance_id'] != '') {
            try {
              $oAlliance = \Grepodata\Library\Controller\Alliance::firstOrFail($aData['alliance_id'], $aData['world']);
              if ($oAlliance !== null) $aData['alliance_name'] = $oAlliance->name;
            } catch (ModelNotFoundException $e) {}//Ignore optional alliance name fail
          }
          
          $aResponse['results'][] = $aData;
        }
      }

      $aResponse['discord'] = array(
        'guild_has_world' => $bGuildHasServer
      );
      return self::OutputJson($aResponse);

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No players found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }
}

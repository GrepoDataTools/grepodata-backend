<?php

namespace Grepodata\Application\API\Route;

use Elasticsearch\Common\Exceptions\AlreadyExpiredException;
use Grepodata\Library\Elasticsearch\Search;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Ranking extends \Grepodata\Library\Router\BaseRoute
{
  public static function PlayerRankingGET()
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

      $aRanking = Search::FindPlayers($aParams);
      $aRanking['world'] = $aParams['world'];
      $aResponse = $aRanking;

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player ranking found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceRankingGET()
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

      try {
        $aRanking = Search::FindAlliances($aParams);
        $aRanking['world'] = $aParams['world'];
        $aResponse = $aRanking;

        return self::OutputJson($aResponse);
      } catch (\Exception $e) {
        Logger::warning("Exception while retrieving alliance ranking: " . $e->getMessage());

        return self::OutputJson(array('failed'=>true));
      }

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance ranking found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }
}
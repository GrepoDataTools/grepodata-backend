<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Search extends \Grepodata\Library\Router\BaseRoute
{
  public static function SearchAllGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('query', 'preferred_server'));

      if (isset($aParams['query']) && strlen($aParams['query']) > 30) {
        throw new \Exception("Search input exceeds limit: " . substr($aParams['query'], 0, 200));
      }

      $aParams['query'] = strtolower($aParams['query']);
      $aParams['active'] = 'true';

      try {
        $aElasticsearchResults = \Grepodata\Library\Elasticsearch\Search::FindPlayersAndAlliances($aParams);
      } catch (\Exception $e) {
        $msg = "ES Player+Alliance search failed with message: " . $e->getMessage() . ". params: " . urlencode(json_encode($aParams['query']));
        Logger::warning($msg);
      }

      if (isset($aElasticsearchResults) && $aElasticsearchResults != false) {
        $aResponse = $aElasticsearchResults;
      } else {
        // SQL fallback (only if ES is down): Find model
        // TODO: Add alliances to fallback?
        $aPlayers = \Grepodata\Library\Controller\Player::search($aParams);
        if ($aPlayers == null || sizeof($aPlayers) <= 0) throw new ModelNotFoundException();

        // Format sql results
        $aResponse = array(
          'success' => true,
          'count'   => sizeof($aPlayers),
          'status'  => 'sql_fallback',
          'results' => array(),
          'form'    => array(),
        );
        foreach ($aPlayers as $oPlayer) {
          $aData = array(
            'id' => $oPlayer->grep_id,
            'world' => $oPlayer->world,
            'server' => substr($oPlayer->world, 0, 2),
            'name' => $oPlayer->name,
            'points' => $oPlayer->points,
          );
          $aResponse['results'][] = $aData;
        }
      }

      return self::OutputJson($aResponse);

    } catch (\Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No players or alliances found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

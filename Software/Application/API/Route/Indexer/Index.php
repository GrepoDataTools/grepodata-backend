<?php

namespace Grepodata\Application\API\Route\Indexer;

use Exception;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\Stats;
use Grepodata\Library\Router\BaseRoute;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Index extends BaseRoute
{

  public static function StatsGET()
  {
    $oStats = Stats::orderBy('created_at', 'desc')
      ->first();

    if ($oStats == null) {
      die(self::OutputJson(array(
        'message'     => 'No stats found.',
      ), 404));
    }

    return self::OutputJson($oStats);
  }

  public static function GetWorldsGET()
  {
    /**
     * Return a list of all servers and worlds that an index can be created for
     */
    $aServers = \Grepodata\Library\Controller\World::getServers();
    $aWorlds = \Grepodata\Library\Controller\World::getAllActiveWorlds();

    $aResponse = array();
    foreach ($aServers as $Server) {
      $aServer = array(
        'server'  => $Server
      );
      foreach ($aWorlds as $oWorld) {
        if (strpos($oWorld->grep_id, $Server) !== false) {
          $aServer['timezone'] = $oWorld->php_timezone;
          $aServer['worlds'][] = array(
            'id'    => $oWorld->grep_id,
            'val'   => substr($oWorld->grep_id, 2),
            'name'  => $oWorld->name,
          );
        }
      }
      $aServer['worlds'] = self::SortWorlds($aServer['worlds']);
      $aResponse[] = $aServer;
    }

    return self::OutputJson($aResponse);
  }

  public static function IsValidGET()
  {
    // TODO: implement v1 backwards compatibility
    die(self::OutputJson(array(
      'valid' => true,
      'deprecated' => true
    ), 200));
  }

  public static function GetIndexV1GET()
  {
    // TODO: implement v1 backwards compatibility
    die(self::OutputJson(array('deprecated' => false), 200));
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key'));

      // Validate index key
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'moved'       => true,
          'message'     => 'Index has moved!'
        ), 200));
      }

      $oIndexOverview = IndexOverview::firstOrFail($aParams['key']);
      if ($oIndexOverview == null) throw new ModelNotFoundException();

      $aRecentConquests = array();
      try {
        $oWorld = World::getWorldById($oIndex->world);
        $aConquests = Conquest::allByIndex($oIndex, 0, 30);
        $SearchLimit = 1;
        if (count($aConquests) > 3) $SearchLimit = 2;
        if (count($aConquests) > 6) $SearchLimit = 3;
        if (count($aConquests) >= 10) $SearchLimit = 4;
        if (count($aConquests) >= 20) $SearchLimit = 5;
        foreach ($aConquests as $oConquest) {
          if ($oConquest->num_attacks_counted>=$SearchLimit) {
            $aRecentConquests[] = $oConquest->getPublicFields($oWorld);
          }
          if (count($aRecentConquests) > 10) {
            // only return top 10
            break;
          }
        }
      } catch (Exception $e) {
        Logger::warning("Error loading recent conquests: " . $e->getMessage());
      }

      $aResponse = array(
        'is_admin'          => false,
        'world'             => $oIndexOverview['world'],
        'total_reports'     => $oIndexOverview['total_reports'],
        'spy_reports'       => $oIndexOverview['spy_reports'],
        'enemy_attacks'     => $oIndexOverview['enemy_attacks'],
        'friendly_attacks'  => $oIndexOverview['friendly_attacks'],
        'latest_report'     => $oIndexOverview['latest_report'],
        'max_version'       => $oIndexOverview['max_version'],
        'recent_conquests'  => $aRecentConquests,
        'latest_version'    => $oIndex->script_version,
        'update_message'    => USERSCRIPT_UPDATE_INFO,
        'owners'            => json_decode(urldecode($oIndexOverview['owners'])),
        'contributors'      => json_decode(urldecode($oIndexOverview['contributors'])),
        'alliances_indexed' => json_decode(urldecode($oIndexOverview['alliances_indexed'])),
        'players_indexed'   => json_decode(urldecode($oIndexOverview['players_indexed'])),
        'latest_intel'      => json_decode(urldecode($oIndexOverview['latest_intel'])),
      );

      if (isset($aParams['access_token'])) {
        $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], false);
        if ($oUser!=false && $oUser->is_confirmed==true && $oIndex->created_by_user == $oUser->id) {
          $aResponse['is_admin'] = true;
        }
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No index overview found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  private static function SortWorlds($aWorlds)
  {
    usort($aWorlds, function ($item1, $item2) {
      if ($item1['val'] == $item2['val']) return 0;
      return $item1['val'] < $item2['val'] ? 1 : -1;
    });
    return $aWorlds;
  }

}

<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\Indexer\IndexOwners;
use Grepodata\Library\Controller\Indexer\Notes;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Model\Indexer\Auth;
use Grepodata\Library\Model\Indexer\Stats;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;
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

  private static function SortWorlds($aWorlds)
  {
    usort($aWorlds, function ($item1, $item2) {
      if ($item1['val'] == $item2['val']) return 0;
      return $item1['val'] < $item2['val'] ? 1 : -1;
    });
    return $aWorlds;
  }

  public static function IsValidGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key'));

      // Validate index key
      if (!Validator::IsValidIndex($aParams['key'])) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
        ), 401));
      }

      return self::OutputJson(array(
        'valid' => true
      ));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No index overview found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetIndexGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $oIndexRole = IndexManagement::verifyUserCanRead($oUser, $aParams['key']);
      $bUserIsAdmin = in_array($oIndexRole->role, array(Roles::ROLE_ADMIN, Roles::ROLE_OWNER));

      // Validate index key
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
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
        'is_admin'          => $bUserIsAdmin,
        'world'             => $oIndexOverview['world'],
        'total_reports'     => $oIndexOverview['total_reports'],
        'spy_reports'       => $oIndexOverview['spy_reports'],
        'enemy_attacks'     => $oIndexOverview['enemy_attacks'],
        'friendly_attacks'  => $oIndexOverview['friendly_attacks'],
        'latest_report'     => $oIndexOverview['latest_report'],
        'max_version'       => $oIndexOverview['max_version'],
        'recent_conquests'  => $aRecentConquests,
        'latest_version'    => $oIndex->script_version,
        'index_name'        => $oIndex->index_name,
        'share_link'        => $bUserIsAdmin ? $oIndex->share_link : 'Unauthorized',
        'update_message'    => USERSCRIPT_UPDATE_INFO,
        'owners'            => json_decode(urldecode($oIndexOverview['owners'])),
        'contributors'      => json_decode(urldecode($oIndexOverview['contributors'])),
        'alliances_indexed' => json_decode(urldecode($oIndexOverview['alliances_indexed'])),
        'players_indexed'   => json_decode(urldecode($oIndexOverview['players_indexed'])),
        'latest_intel'      => json_decode(urldecode($oIndexOverview['latest_intel'])),
      );

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No index overview found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function NewIndexGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'index_name', 'access_token', 'captcha'));

      // Validate captcha
      if (!bDevelopmentMode) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Verify token
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // New index
      $oIndex = IndexBuilderV2::buildNewIndex($aParams['world'], $aParams['index_name'], $oUser->id);
      if ($oIndex !== false && $oIndex !== null) {

        Roles::SetUserIndexRole($oUser, $oIndex, Roles::ROLE_OWNER);

        try {
          IndexOverview::buildIndexOverview($oIndex);
        } catch (\Exception $e) {
          Logger::error("Error building index overview for new index " . $oIndex->key_code . " (".$e->getMessage().")");
        }

        return self::OutputJson(array('status' => 'ok', 'key' => $oIndex->key_code));
      }
      else throw new \Exception();

    } catch (\Exception $e) {
      Logger::warning("Error building new index: " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Error building new index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function NewShareLinkGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $oIndex = IndexInfo::firstOrFail($aParams['index_key']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      $oIndex->share_link = IndexBuilderV2::generateIndexKey(10);
      $oIndex->save();

      $aResponse = array(
        'share_link' => $oIndex->share_link
      );
      ResponseCode::success($aResponse, 1200);

    } catch (\Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'Error building new index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
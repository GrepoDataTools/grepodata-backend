<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\IndexV2\IndexOverview;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;

class Profile extends BaseRoute
{

  /**
   * API route: /profile/indexes
   * Method: GET
   */
  public static function IndexesGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Email needs to be confirmed to continue
    if ($oUser->is_confirmed==false) {
      ResponseCode::errorCode(3010, array(), 403);
    }

    $Start = round(microtime(true) * 1000);
    $aIndexes = \Grepodata\Library\Controller\Indexer\IndexInfo::allByUser($oUser);

    $aIndexItems = array();
    foreach ($aIndexes as $oIndex) {

      // Optional filter by world
      if (isset($aParams['world'])) {
        if ($oIndex->world !== $aParams['world']) continue;
      }

      // Check world
      $oWorld = World::getWorldById($oIndex->world);

      $aOverview = array();
      if (isset($aParams['expand_overview']) || isset($aParams['sort_by'])) {
        try {
          $oOverview = IndexOverview::firstOrFail($oIndex->key_code);
          $aOverview = $oOverview->getMinimalFields();
        } catch (\Exception $e) {
          // no overview for this index
          $aOverview = array(
            'total_reports' => '?'
          );
        }
      }
      $bUserIsAdmin = in_array($oIndex->role, Roles::admin_roles);
      $aIndexItems[] = array(
        'key'         => $oIndex->key_code,
        'name'        => $oIndex->index_name,
        'role'        => $oIndex->role,
        'contribute'  => $oIndex->contribute,
        'share_link'  => $bUserIsAdmin ? $oIndex->share_link : 'Unauthorized',
        'num_days'    => $bUserIsAdmin ? $oIndex->delete_old_intel_days : 0,
        'allow_join_v1_key' => $bUserIsAdmin ? $oIndex->allow_join_v1_key : 0,
        'world'       => $oIndex->world,
        'created_at'  => $oIndex->created_at,
        'updated_at'  => $oIndex->updated_at,
        'stats'       => $aOverview,
        'status'      => $oIndex->status,
        'index_version' => $oIndex->index_version,
        'world_stopped' => $oWorld->stopped == 1,
      );
    }

    $ElapsedMs = round(microtime(true) * 1000) - $Start;
    if ($ElapsedMs > 1000) {
      Logger::warning("Slow request warning: IndexesGET > ".$ElapsedMs."ms > ".$oUser->id);
    }

    // optional sort by number of reports descending
    if (isset($aParams['sort_by'])) {
      if ($aParams['sort_by'] == 'reports') {
        usort($aIndexItems, function ($item1, $item2) {
          $ValueA = $item1['stats']['total_reports'] ?? 0;
          $ValueA = $ValueA=='?'?0:$ValueA;
          $ValueB = $item2['stats']['total_reports'] ?? 0;
          $ValueB = $ValueB=='?'?0:$ValueB;
          if ($ValueA == $ValueB) {
            return 0;
          } else {
            return ($ValueA < $ValueB) ? 1 : -1;
          }
        });
      }
    }

    // Push stopped worlds to the back
    $aStopped = array();
    $aActive = array();
    foreach ($aIndexItems as $oIndex) {
      if ($oIndex['world_stopped'] === false) {
        $aActive[] = $oIndex;
      } else {
        $aStopped[] = $oIndex;
      }
    }
    foreach ($aStopped as $oIndex) {
      $aActive[] = $oIndex;
    }
    $aIndexItems = $aActive;

    // Optional limit
    if (isset($aParams['limit'])) {
      $aIndexItems = array_slice($aIndexItems, 0, $aParams['limit']);
    }

    $aResponse = array(
      'query_ms' => $ElapsedMs,
      'rows' => sizeof($aIndexItems),
      'items' => $aIndexItems,
    );

    ResponseCode::success($aResponse);
  }

  public static function LinkedAccountsGET()
  {
    die(self::OutputJson(array('disabled' => true), 200));
//    // Validate params
//    $aParams = self::validateParams(array('access_token'));
//    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
//
//    // Email needs to be confirmed to continue
//    if ($oUser->is_confirmed==false) {
//      ResponseCode::errorCode(3010, array(), 403);
//    }
//
//    $aResponse = array(
//      'rows' => 0,
//      'items' => array(),
//    );
//    $aLinkedAccounts = Linked::getAllByUser($oUser);
//    foreach ($aLinkedAccounts as $oLinked) {
//      $aResponse['items'][] = $oLinked->getPublicFields();
//    }
//    $aResponse['rows'] = sizeof($aResponse['items']);
//
//    ResponseCode::success($aResponse);
  }

  public static function AddLinkedAccountPOST()
  {
    die(self::OutputJson(array('disabled' => true), 200));
//    // Validate params
//    $aParams = self::validateParams(array('access_token', 'player_id', 'player_name', 'server'), array('access_token'));
//    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
//
//    // Email needs to be confirmed to continue
//    if ($oUser->is_confirmed==false) {
//      ResponseCode::errorCode(3010, array(), 403);
//    }
//
//    try {
//      // Check if link was already requested
//      $oLinked = Linked::getByPlayerIdAndServer($oUser, $aParams['player_id'], $aParams['server']);
//    } catch (ModelNotFoundException $e) {
//      // Create new link
//      $oLinked = Linked::newLinkedAccount($oUser, $aParams['player_id'], $aParams['player_name'], $aParams['server']);
//    }
//
//
//    $aResponse = array(
//      'linked_account' => $oLinked->getPublicFields()
//    );
//    ResponseCode::success($aResponse);
  }

  public static function RemoveLinkedAccountPOST()
  {
    die(self::OutputJson(array('disabled' => true), 200));
//    // Validate params
//    $aParams = self::validateParams(array('access_token', 'player_id', 'server'));
//    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
//
//    // Email needs to be confirmed to continue
//    if ($oUser->is_confirmed==false) {
//      ResponseCode::errorCode(3010, array(), 403);
//    }
//
//    try {
//      $oLinked = Linked::getByPlayerIdAndServer($oUser, $aParams['player_id'], $aParams['server']);
//    } catch (ModelNotFoundException $e) {
//      Logger::error("Unable to find account link for user ".$oUser->id);
//      ResponseCode::errorCode(4100, array(), 400);
//    }
//
//    try {
//      Linked::unlink($oLinked);
//    } catch (\Exception $e) {
//      Logger::error("Unable to delete account link ".$oLinked->id. "; ".$e->getMessage());
//      ResponseCode::errorCode(4200, array(), 400);
//    }
//
//    $aResponse = array();
//    ResponseCode::success($aResponse, 4200);
  }

}

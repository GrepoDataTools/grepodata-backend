<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\IndexV2\Linked;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Profile extends BaseRoute
{
  public static function IndexesGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Email needs to be confirmed to continue
    if ($oUser->is_confirmed==false) {
      ResponseCode::errorCode(3010, array(), 403);
    }

    $aIndexes = \Grepodata\Library\Controller\Indexer\IndexInfo::allByUser($oUser);
    $aIndexItems = array();
    foreach ($aIndexes as $oIndex) {
      $aOverview = [];
      if (isset($aParams['expand_overview']) || isset($aParams['sort_by'])) {
        try {
          $oOverview = IndexOverview::firstOrFail($oIndex->key_code);
          $aOverview = $oOverview->getMinimalFields();
        } catch (\Exception $e) {
          continue;
        }
      }
      $bUserIsAdmin = in_array($oIndex->role, array(Roles::ROLE_ADMIN, Roles::ROLE_OWNER));
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
        'index_version' => $oIndex->index_version
      );
    }

    // optional sort by number of reports descending
    if (isset($aParams['sort_by'])) {
      if ($aParams['sort_by'] == 'reports') {
        usort($aIndexItems, function ($item1, $item2) {
          return $item1['stats']['total_reports'] < $item2['stats']['total_reports'] ? 1 : -1;
        });
      }
    }

    // push inactive indexes to the back
    usort($aIndexItems, function ($item1, $item2) {
      return $item1['status'] === 'active' ? -1 : 1;
    });

    // Optional limit
    if (isset($aParams['limit'])) {
      $aIndexItems = array_slice($aIndexItems, 0, $aParams['limit']);
    }

    $aResponse = array(
      'rows' => sizeof($aIndexItems),
      'items' => $aIndexItems,
    );

    ResponseCode::success($aResponse);
  }

  public static function LinkedAccountsGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Email needs to be confirmed to continue
    if ($oUser->is_confirmed==false) {
      ResponseCode::errorCode(3010, array(), 403);
    }

    $aResponse = array(
      'rows' => 0,
      'items' => array(),
    );
    $aLinkedAccounts = Linked::getAllByUser($oUser);
    foreach ($aLinkedAccounts as $oLinked) {
      $aResponse['items'][] = $oLinked->getPublicFields();
    }
    $aResponse['rows'] = sizeof($aResponse['items']);

    ResponseCode::success($aResponse);
  }

  public static function AddLinkedAccountPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token', 'player_id', 'player_name', 'server'), array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Email needs to be confirmed to continue
    if ($oUser->is_confirmed==false) {
      ResponseCode::errorCode(3010, array(), 403);
    }

    try {
      // Check if link was already requested
      $oLinked = Linked::getByPlayerIdAndServer($oUser, $aParams['player_id'], $aParams['server']);
    } catch (ModelNotFoundException $e) {
      // Create new link
      $oLinked = Linked::newLinkedAccount($oUser, $aParams['player_id'], $aParams['player_name'], $aParams['server']);
    }


    $aResponse = array(
      'linked_account' => $oLinked->getPublicFields()
    );
    ResponseCode::success($aResponse);
  }

  public static function RemoveLinkedAccountPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token', 'player_id', 'server'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Email needs to be confirmed to continue
    if ($oUser->is_confirmed==false) {
      ResponseCode::errorCode(3010, array(), 403);
    }

    try {
      $oLinked = Linked::getByPlayerIdAndServer($oUser, $aParams['player_id'], $aParams['server']);
    } catch (ModelNotFoundException $e) {
      Logger::error("Unable to find account link for user ".$oUser->id);
      ResponseCode::errorCode(4100, array(), 400);
    }

    try {
      Linked::unlink($oLinked);
    } catch (\Exception $e) {
      Logger::error("Unable to delete account link ".$oLinked->id. "; ".$e->getMessage());
      ResponseCode::errorCode(4200, array(), 400);
    }

    $aResponse = array();
    ResponseCode::success($aResponse, 4200);
  }

}

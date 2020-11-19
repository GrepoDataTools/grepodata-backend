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

    $aResponse = array(
      'rows' => 0,
      'items' => array(),
    );
    $aIndexes = \Grepodata\Library\Controller\Indexer\IndexInfo::allByUser($oUser);
    foreach ($aIndexes as $oIndex) {
      $aOverview = [];
      if (isset($aParams['expand_overview'])) {
        try {
          $oOverview = IndexOverview::firstOrFail($oIndex->key_code);
          $aOverview = $oOverview->getMinimalFields();
        } catch (\Exception $e) {
          continue;
        }
      }
      $aResponse['items'][] = array(
        'key' => $oIndex->key_code,
        'name' => $oIndex->index_name,
        'role' => $oIndex->role,
        'contribute' => $oIndex->contribute,
        'world' => $oIndex->world,
        'created_at' => $oIndex->created_at,
        'updated_at' => $oIndex->updated_at,
        'stats' => $aOverview
      );
    }
    $aResponse['rows'] = sizeof($aResponse['items']);

    ResponseCode::success($aResponse);
  }

  public static function LinkedAccountsGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'), array('access_token'));
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

    $oLinked = Linked::newLinkedAccount($oUser, $aParams['player_id'], $aParams['player_name'], $aParams['server']);

    $aResponse = array(
      'linked_account' => $oLinked->getPublicFields()
    );
    ResponseCode::success($aResponse);
  }

  public static function RemoveLinkedAccountPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token', 'player_id', 'server'), array('access_token'));
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
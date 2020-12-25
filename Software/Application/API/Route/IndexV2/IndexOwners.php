<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Model\User;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexOwners extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns a list of all index owners for the given index
   */
  public static function IndexOwnersGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      $aResult = IndexOverview::firstOrFail($aParams['index_key']);
      $aItems = json_decode($aResult->owners);

      $aResponse = array(
        'size'    => sizeof($aItems),
        'data'   => $aItems
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Index owner not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
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

      try {
        $oUserRole = Roles::getUserIndexRole($oUser, $aParams['index_key']);
        if (!in_array($oUserRole->role, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN))) {
          ResponseCode::errorCode(7502);
        }
      } catch (\Exception $e) {
        ResponseCode::errorCode(7500);
      }

      $aResult = IndexOverview::firstOrFail($aParams['index_key']);
      $aItems = json_decode($aResult->owners);

      $aResponse = array(
        'size'    => sizeof($aItems),
        'items'   => $aItems
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
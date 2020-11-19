<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Model\User;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexUsers extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns a list of all index owners for the given index
   */
  public static function IndexUsersGET()
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

      $aResult = Roles::getUsersByIndex($aParams['index_key']);
      $aItems = array();
      /** @var User $oUser */
      foreach ($aResult as $oUser) {
        $aItems[] = array(
          'user_id' => $oUser->id,
          'role' => $oUser->role,
          'contribute' => $oUser->contribute,
          'username' => $oUser->username,
          'player_name' => 'TODO',
        );
      }

      $aResponse = array(
        'size'    => sizeof($aItems),
        'data'   => $aItems
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Change a users role on an index
   */
  public static function IndexUsersPOST()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'user_id', 'role'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      $aResult = Roles::getUsersByIndex($aParams['index_key']);
      $aItems = array();
      /** @var User $oUser */
      foreach ($aResult as $oUser) {
        $aItems[] = array(
          'user_id' => $oUser->id,
          'role' => $oUser->role,
          'contribute' => $oUser->contribute,
          'username' => $oUser->username,
          'player_name' => 'TODO',
        );
      }

      $aResponse = array(
        'size'    => sizeof($aItems),
        'data'   => $aItems
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
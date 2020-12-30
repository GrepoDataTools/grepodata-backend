<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\IndexInfo;
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

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      $aResult = Roles::getUsersByIndex($aParams['index_key']);
      $aItems = array();
      /** @var \Grepodata\Library\Model\IndexV2\Roles $oUser */
      foreach ($aResult as $oUser) {
        $aItems[] = array(
          'user_id' => $oUser->user_id,
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
        'message'     => 'User role not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Change a users role on an index
   * @throws \Exception
   */
  public static function IndexUsersPUT()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'user_id', 'role'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      if ($oUser->id == $aParams['user_id']) {
        ResponseCode::errorCode(7520);
      }

      // User has to be at least admin to manage users
      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      try {
        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2020);
      }

      if (!in_array($aParams['role'], array(Roles::ROLE_READ, Roles::ROLE_WRITE, Roles::ROLE_ADMIN, Roles::ROLE_OWNER))) {
        ResponseCode::errorCode(7530);
      }

      // Get user
      try {
        $oManagedUser = \Grepodata\Library\Controller\User::GetUserById($aParams['user_id']);
        $oManagedUserRole = Roles::getUserIndexRole($oManagedUser, $aParams['index_key']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2010);
      }

      // Check update type
      $OldRole = $oManagedUserRole->role;
      $OldRoleNumber = array_search($OldRole, Roles::numbered_roles);
      $NewRole = $aParams['role'];
      $NewRoleNumber = array_search($NewRole, Roles::numbered_roles);
      if ($OldRoleNumber >= $NewRoleNumber) {
        // User is being demoted
        $NewRoleNumber = max(0, $NewRoleNumber-1);
        $NewRole = Roles::numbered_roles[$NewRoleNumber];
      } else {
        // User is being promoted
      }

      // Check role rules
      if ($NewRole == Roles::ROLE_OWNER || $OldRole == Roles::ROLE_OWNER) {
        // Only an owner can manage other owners
        $oEditorRole = IndexManagement::verifyUserIsOwner($oUser, $aParams['index_key']);
      }

      if (($OldRole == Roles::ROLE_ADMIN || $NewRole == Roles::ROLE_ADMIN) && $oEditorRole->role != Roles::ROLE_OWNER) {
        // Only an owner can manage other admins
        ResponseCode::errorCode(7540);
      }

      $oUserRole = Roles::SetUserIndexRole($oManagedUser, $oIndex, $NewRole);
      $aUpdatedUser = $oUserRole->getPublicFields();
      $aUpdatedUser['username'] = $oManagedUser->username;

      ResponseCode::success(array(
        'size' => 1,
        'data' => $aUpdatedUser
      ));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'User role not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Add a new user to the index
   * @throws \Exception
   */
  public static function IndexUsersPOST()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'user_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // User has to be at least admin to manage users
      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      try {
        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2020);
      }

      // Get user
      try {
        $oManagedUser = \Grepodata\Library\Controller\User::GetUserById($aParams['user_id']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2010);
      }

      // Get existing role user
      try {
        $oManagedUserRole = Roles::getUserIndexRole($oManagedUser, $aParams['index_key']);
        if (!empty($oManagedUserRole)) {
          // user already exists on this index
          ResponseCode::errorCode(7570);
        }
      } catch (ModelNotFoundException $e) {}

      // Create new role for user
      $oUserRole = Roles::SetUserIndexRole($oManagedUser, $oIndex, Roles::ROLE_WRITE);
      $aUpdatedUser = $oUserRole->getPublicFields();
      $aUpdatedUser['username'] = $oManagedUser->username;

      ResponseCode::success(array(
        'size' => 1,
        'data' => $aUpdatedUser
      ));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'User not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Delete a users access from an index
   * @throws \Exception
   */
  public static function IndexUsersDELETE()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'user_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      if ($oUser->id == $aParams['user_id']) {
        ResponseCode::errorCode(7520);
      }

      $oEditorRole = IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      try {
        $oManagedUser = \Grepodata\Library\Controller\User::GetUserById($aParams['user_id']);
        $oManagedUserRole = Roles::getUserIndexRole($oManagedUser, $aParams['index_key']);
        if (($oManagedUserRole->role == Roles::ROLE_ADMIN || $oManagedUserRole->role == Roles::ROLE_OWNER) && $oEditorRole->role != Roles::ROLE_OWNER) {
          // Only an owner can manage other admins/owners
          ResponseCode::errorCode(7540);
        }
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(2010);
      }

      $bSuccess = $oManagedUserRole->delete();
      if ($bSuccess == false) {
        ResponseCode::errorCode(7000);
      }

      ResponseCode::success(array(
        'deleted' => true
      ), 1300);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'User role not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
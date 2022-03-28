<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\IndexV2\Event;
use Grepodata\Library\Controller\IndexV2\IndexOverview;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\User;
use Grepodata\Library\Router\Authentication;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexUsers extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns a list of all users that have access to the given index
   */
  public static function IndexUsersGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = Authentication::verifyJWT($aParams['access_token']);

      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

      $aResult = Roles::getUsersByIndex($aParams['index_key']);
      $aUsers = array();
      /** @var \Grepodata\Library\Model\IndexV2\Roles $oUser */
      foreach ($aResult as $oUser) {
          $aUsers[$oUser->user_id] = array(
          'user_id' => $oUser->user_id,
          'role' => $oUser->role,
          'contribute' => $oUser->contribute,
          'username' => $oUser->username,
          'player_name' => null,
        );
      }

      try {
          $oIndexOverview = IndexOverview::firstOrFail($aParams['index_key']);
          $aContributors = json_decode(urldecode($oIndexOverview['contributors_actual']), true);
          if (is_array($aContributors) && count($aContributors) > 0) {
              foreach ($aContributors as $aContributor) {
                  if (key_exists($aContributor['user_id'], $aUsers)) {
                      $aUsers[$aContributor['user_id']]['player_id'] = $aContributor['player_id'];
                      $aUsers[$aContributor['user_id']]['player_name'] = $aContributor['player_name'];
                      $aUsers[$aContributor['user_id']]['contributions'] = $aContributor['contributions'];
                      $aUsers[$aContributor['user_id']]['last_contribution'] = $aContributor['last_contribution'];
                      $aUsers[$aContributor['user_id']]['first_contribution'] = $aContributor['first_contribution'];
                  }
              }
          }
      } catch (\Exception $e) {
          Logger::warning("Unable to expand index users: " . $e->getMessage());
      }

      $aResponse = array(
        'size'    => sizeof($aUsers),
        'data'   => array_values($aUsers)
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
      $oUser = Authentication::verifyJWT($aParams['access_token']);

      if ($oUser->id == $aParams['user_id']) {
        ResponseCode::errorCode(7520);
      }

      // User has to be at least admin to manage users
      $oEditorRole = IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);

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
      Event::addRoleChangeEvent($oIndex, $oUser, $oManagedUser, $NewRole);
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
    ResponseCode::errorCode();
//    try {
//      $aParams = self::validateParams(array('access_token', 'index_key', 'user_id'));
//      $oUser = Authentication::verifyJWT($aParams['access_token']);
//
//      // User has to be at least admin to manage users
//      IndexManagement::verifyUserIsAdmin($oUser, $aParams['index_key']);
//
//      try {
//        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
//      } catch (ModelNotFoundException $e) {
//        ResponseCode::errorCode(2020);
//      }
//
//      // Get user
//      try {
//        $oManagedUser = \Grepodata\Library\Controller\User::GetUserById($aParams['user_id']);
//      } catch (ModelNotFoundException $e) {
//        ResponseCode::errorCode(2010);
//      }
//
//      // Get existing role user
//      try {
//        $oManagedUserRole = Roles::getUserIndexRole($oManagedUser, $aParams['index_key']);
//        if (!empty($oManagedUserRole)) {
//          // user already exists on this index
//          ResponseCode::errorCode(7570);
//        }
//      } catch (ModelNotFoundException $e) {}
//
//      // Create new role for user
//      $oUserRole = Roles::SetUserIndexRole($oManagedUser, $oIndex, Roles::ROLE_WRITE);
//      $aUpdatedUser = $oUserRole->getPublicFields();
//      $aUpdatedUser['username'] = $oManagedUser->username;
//
//      ResponseCode::success(array(
//        'size' => 1,
//        'data' => $aUpdatedUser
//      ));
//
//    } catch (ModelNotFoundException $e) {
//      die(self::OutputJson(array(
//        'message'     => 'User not found.',
//        'parameters'  => $aParams
//      ), 404));
//    }
  }

  /**
   * Delete a users access from an index
   * @throws \Exception
   */
  public static function IndexUsersDELETE()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key', 'user_id'));
      $oUser = Authentication::verifyJWT($aParams['access_token']);

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

      try {
        $oIndex = IndexInfo::firstOrFail($aParams['index_key']);
        Event::addIndexJoinEvent($oIndex, $oManagedUser, 'removed', $oUser);
      } catch (\Exception $e) {}

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

  /**
   * Import one or more v1 index keys for the authenticated user
   * @throws \Exception
   */
  public static function ImportV1KeysPOST()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_keys'));
      $oUser = Authentication::verifyJWT($aParams['access_token']);

      // Validate captcha
      if (isset($aParams['captcha']) && $aParams['captcha']!='') {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      Logger::v2Migration("ImportV1Keys ".json_encode($aParams));

      // Parse array of index keys
      $aIndexKeys = $aParams['index_keys'];
      if (!is_array($aIndexKeys)) {
        if (strlen($aIndexKeys)==8) {
          $aIndexKeys = array($aIndexKeys);
        } else {
          $aIndexKeys = explode(',', $aIndexKeys);
        }
      }

      $bFailVerbose = isset($aParams['verbose']);

      // Import each key
      foreach ($aIndexKeys as $IndexKey) {
        try {
          // Check if the index exists
          $oIndex = IndexInfo::firstOrFail($IndexKey);

          // Check if user already has a role on the index (if role already exists, skip import for this index)
          $oExistingRole = Roles::getUserIndexRoleNoFail($oUser, $oIndex->key_code);
          if ($oExistingRole != null) {
            continue;
          }

          // Check if v1 importing is enabled
          if ($oIndex->allow_join_v1_key == false) {
            // V1 import is disabled, skip to next index
            Logger::v2Migration("Failed attempt to join via v1 redirect (v1 joining disabled) ".json_encode($aParams));
            if ($bFailVerbose) {
              ResponseCode::errorCode(7601);
            }
            continue;
          }

          // Check if the key is an actual V1 key (V2 keys can not be imported in this way)
          if ($oIndex->index_version !== '1') {
            // This is not a V1 index, skip to next index
            Logger::v2Migration("Failed attempt to join via v1 redirect (not a v1 index) ".json_encode($aParams));
            if ($bFailVerbose) {
              ResponseCode::errorCode(7602);
            }
            continue;
          }

          if ($oExistingRole == null) {
            // Add write role on the index
            Roles::SetUserIndexRole($oUser, $oIndex, Roles::ROLE_WRITE);
            Event::addIndexJoinEvent($oIndex, $oUser, 'v1_redirect');
            Logger::v2Migration("Successful import of a v1 key ".$oIndex->key_code." ".$oUser->id);
          }

        } catch (ModelNotFoundException $e) {
          if ($bFailVerbose) {
            ResponseCode::errorCode(7101);
          }
        } catch (\Exception $e) {
          Logger::v2Migration("WARNING: Exception while importing V1 index key '" . $IndexKey . "' for user " . $oUser->id . ". " . $e->getMessage());
        }
      }

      ResponseCode::success(array(
        'imported' => true
      ), 1400);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'User not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Verify and process the given invite link
   */
  public static function VerifyInviteLinkPOST()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'invite_link'));
      $oUser = Authentication::verifyJWT($aParams['access_token']);

      $InviteLink = $aParams['invite_link'];
      if (!is_string($InviteLink) || !in_array(strlen($InviteLink), array(8, 18))) {
        ResponseCode::errorCode(3008);
      }

      // Parse invite link
      $IndexKey = substr($InviteLink, 0, 8);
      $InviteCode = substr($InviteLink, 8);

      try {
        $oIndex = IndexInfo::firstOrFail($IndexKey);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(7101);
      }

      $oActiveRole = null;

      $oUserRole = Roles::getUserIndexRoleNoFail($oUser, $oIndex->key_code);
      $bIsNewAccessGranted = true;
      if ($oUserRole != null) {
        // User already has a role on the index
        $oActiveRole = $oUserRole;
        $bIsNewAccessGranted = false;
      } else if (strlen($InviteCode)===10) {
        if ($oIndex->share_link === $InviteCode) {
          // Verified invite link
          $oActiveRole = Roles::SetUserIndexRole($oUser, $oIndex, Roles::ROLE_WRITE);
          Event::addIndexJoinEvent($oIndex, $oUser, 'invite_link');
        } else {
          // Expired (no longer valid)
          ResponseCode::errorCode(3009);
        }
      } else if (strlen($InviteCode)===0) {
        if ($oIndex->index_version === '1' && $oIndex->allow_join_v1_key === 1) {
          // Allow for v1 redirects
          $oActiveRole = Roles::SetUserIndexRole($oUser, $oIndex, Roles::ROLE_WRITE);
          Event::addIndexJoinEvent($oIndex, $oUser, 'v1_redirect');
          Logger::v2Migration("Successful join via v1 redirect ".$oIndex->key_code." ".$oUser->id);
        } else {
          // Not a V1 index or v1 joining is disabled
          Logger::v2Migration("Failed attempt to join via v1 redirect (not a v1 index or v1 joining disabled) ".json_encode($aParams));
          ResponseCode::errorCode(7601);
        }
      }

      // Check for uncommitted intel
      try {
        $UncommittedCount = \Grepodata\Library\Controller\IndexV2\IntelShared::countUncommitted($oUser->id, $oIndex->world, $oIndex->key_code);
        $oActiveRole->uncommitted_reports = $UncommittedCount;
        $oActiveRole->uncommitted_status = 'Unread';
        $oActiveRole->save();
      } catch (\Exception $e) {
        Logger::error("Error parsing index commitment: ".$e->getMessage());
      }

      // catch all: invalid invite link
      if ($oActiveRole==null) {
        ResponseCode::errorCode(3008);
      }

      $aResponse = array(
        'verified' => true,
        'is_new_access' => $bIsNewAccessGranted,
        'index_name' => $oIndex->index_name,
        'user_role' => $oActiveRole->getPublicFields()
      );
      ResponseCode::success($aResponse, 1201);
    } catch (\Exception $e) {
      Logger::warning("Error handling invite link: " . $e->getMessage());
      ResponseCode::errorCode(1200);
    }
  }


}

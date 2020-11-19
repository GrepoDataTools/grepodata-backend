<?php

namespace Grepodata\Library\IndexV2;


use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Model\User;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexManagement extends \Smarty
{

  /**
   * Returns true if the user has at least read privileges on the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return bool
   */
  public static function verifyUserCanRead(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN, Roles::ROLE_WRITE, Roles::ROLE_READ));
  }

  /**
   * Returns true if the user is allowed to write to the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return bool
   */
  public static function verifyUserCanWrite(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN, Roles::ROLE_WRITE));
  }

  /**
   * Returns true if the user has at least admin privileges on the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return bool
   */
  public static function verifyUserIsAdmin(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN));
  }

  /**
   * Returns true if the user has owner privileges on the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return bool
   */
  public static function verifyUserIsOwner(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER));
  }

  private static function userHasRole($oUser, $IndexKey, $aRolesRequired)
  {
    try {
      $oUserRole = Roles::getUserIndexRole($oUser, $IndexKey);
      if (!in_array($oUserRole->role, $aRolesRequired)) {
        ResponseCode::errorCode(7502);
      }
      return true;
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(7500);
    }
  }
}
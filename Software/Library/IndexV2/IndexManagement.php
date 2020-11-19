<?php

namespace Grepodata\Library\IndexV2;


use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Model\User;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexManagement extends \Smarty
{

  /**
   * Returns Roles if the user has at least read privileges on the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function verifyUserCanRead(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN, Roles::ROLE_WRITE, Roles::ROLE_READ), 7504);
  }

  /**
   * Returns Roles if the user is allowed to write to the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function verifyUserCanWrite(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN, Roles::ROLE_WRITE), 7503);
  }

  /**
   * Returns Roles if the user has at least admin privileges on the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function verifyUserIsAdmin(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER, Roles::ROLE_ADMIN), 7502);
  }

  /**
   * Returns Roles if the user has owner privileges on the given index key
   * Returns an unauthorized error if user has no access.
   * @param User $oUser
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function verifyUserIsOwner(User $oUser, $IndexKey)
  {
    return self::userHasRole($oUser, $IndexKey, array(Roles::ROLE_OWNER), 7501);
  }

  private static function userHasRole($oUser, $IndexKey, $aRolesRequired, $ErrorCode=7500)
  {
    try {
      $oUserRole = Roles::getUserIndexRole($oUser, $IndexKey);
      if (!in_array($oUserRole->role, $aRolesRequired)) {
        ResponseCode::errorCode($ErrorCode);
      }
      return $oUserRole;
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(7500);
    }
  }
}
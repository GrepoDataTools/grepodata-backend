<?php

namespace Grepodata\Library\Controller\IndexV2;

use Exception;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;

class Roles
{
  const ROLE_OWNER = 'owner';
  const ROLE_ADMIN = 'admin';
  const ROLE_WRITE = 'write';
  const ROLE_READ = 'read';
  const admin_roles = array(
    self::ROLE_ADMIN, self::ROLE_OWNER
  );
  const numbered_roles = array(
    0 => self::ROLE_READ,
    1 => self::ROLE_WRITE,
    2 => self::ROLE_ADMIN,
    3 => self::ROLE_OWNER,
  );
  const named_roles = array(
    'read' => 'read-only',
    'write' => 'member',
    'admin' => 'admin',
    'owner' => 'owner',
  );

  /**
   * Sets the given user role on the given index
   * @param User $oUser
   * @param IndexInfo $oIndex
   * @param $Role
   * @return \Grepodata\Library\Model\IndexV2\Roles
   * @throws Exception
   */
  public static function SetUserIndexRole(User $oUser, IndexInfo $oIndex, $Role)
  {
    if (!in_array($Role, array(Roles::ROLE_READ, Roles::ROLE_WRITE, Roles::ROLE_ADMIN, Roles::ROLE_OWNER))) {
      throw new Exception("Invalid user role.");
    }
    /** @var \Grepodata\Library\Model\IndexV2\Roles $oRole */
    $oRole = \Grepodata\Library\Model\IndexV2\Roles::firstOrNew(array(
      'user_id' => $oUser->id,
      'index_key' => $oIndex->key_code,
    ));
    $oRole->role = $Role;
    $oRole->save();
    return $oRole;
  }

  /**
   * Delete the user from the given index
   * @param User $oUser
   * @return bool
   */
  public static function DeleteUserIndexRole(User $oUser, $IndexKey)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::where('user_id', '=', $oUser->id)
      ->where('index_key', '=', $IndexKey)
      ->delete();
  }

  /**
   * Delete the user from the given index
   * @param User $oUser
   * @return bool
   */
  public static function DeleteAllUserRoles(User $oUser)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::where('user_id', '=', $oUser->id)
      ->delete();
  }

  /**
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles[]
   */
  public static function getUsersByIndex($IndexKey)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::select(['Indexer_roles.*', 'User.username'])
      ->join('User', 'User.id', '=', 'Indexer_roles.user_id')
      ->where('Indexer_roles.index_key', '=', $IndexKey)
      ->orderBy('Indexer_roles.created_at', 'asc')
      ->get();
  }

  /**
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles[]
   */
  public static function getOwnersByIndex($IndexKey)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::select(['Indexer_roles.*', 'User.username'])
      ->join('User', 'User.id', '=', 'Indexer_roles.user_id')
      ->where('Indexer_roles.index_key', '=', $IndexKey)
      ->where('Indexer_roles.role', '=', self::ROLE_OWNER)
      ->orderBy('Indexer_roles.created_at', 'asc')
      ->get();
  }

  /**
   * @param User $oUser
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function getUserIndexRole(User $oUser, $IndexKey)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::where('user_id', '=', $oUser->id)
      ->where('index_key', '=', $IndexKey)
      ->firstOrFail();
  }

  /**
   * @param User $oUser
   * @param $IndexKey
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function getUserIndexRoleNoFail(User $oUser, $IndexKey)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::where('user_id', '=', $oUser->id)
      ->where('index_key', '=', $IndexKey)
      ->first();
  }

  /**
   * Return all roles for the given user
   * @param User $oUser
   * @return \Grepodata\Library\Model\IndexV2\Roles
   */
  public static function allByUser(User $oUser)
  {
    return \Grepodata\Library\Model\IndexV2\Roles::where('user_id', '=', $oUser->id)->get();
  }

}

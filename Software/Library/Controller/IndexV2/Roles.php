<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class Roles
{
  const ROLE_OWNER = 'owner';
  const ROLE_ADMIN = 'admin';
  const ROLE_WRITE = 'write';
  const ROLE_READ = 'read';
  const numbered_roles = array(
    0 => self::ROLE_READ,
    1 => self::ROLE_WRITE,
    2 => self::ROLE_ADMIN,
    3 => self::ROLE_OWNER,
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


}
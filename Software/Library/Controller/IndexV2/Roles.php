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

  /**
   * Sets the given user role on the given index
   * @param User $oUser
   * @param IndexInfo $oIndex
   * @param $Role
   */
  public static function SetUserIndexRole(User $oUser, IndexInfo $oIndex, $Role)
  {
    /** @var \Grepodata\Library\Model\IndexV2\Roles $oRole */
    $oRole = \Grepodata\Library\Model\IndexV2\Roles::firstOrNew(array(
      'user_id' => $oUser->id,
      'index_key' => $oIndex->key_code,
    ));
    $oRole->role = $Role;
    $oRole->save();
  }


}
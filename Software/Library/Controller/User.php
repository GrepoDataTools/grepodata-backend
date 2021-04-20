<?php

namespace Grepodata\Library\Controller;

use Illuminate\Database\Capsule\Manager as DB;

class User
{

  /**
   * @param $Mail
   * @return \Grepodata\Library\Model\User
   */
  public static function GetUserByMail($Mail)
  {
    return \Grepodata\Library\Model\User::where('email', '=', $Mail)
      ->firstOrFail();
  }

  /**
   * @param $Username
   * @return \Grepodata\Library\Model\User
   */
  public static function GetUserByUsername($Username)
  {
    return \Grepodata\Library\Model\User::where('username', '=', $Username)
      ->firstOrFail();
  }

  /**
   * @param $Token
   * @return \Grepodata\Library\Model\User
   */
  public static function GetUserByToken($Token)
  {
    if (empty($Token)) {
      return false;
    }
    return \Grepodata\Library\Model\User::where('token', '=', $Token)
      ->firstOrFail();
  }

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\User
   */
  public static function GetUserById($Id)
  {
    return \Grepodata\Library\Model\User::where('id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param $Username
   * @param $Mail
   * @param $Passphrase
   * @param string $Role
   * @return \Grepodata\Library\Model\User
   */
  public static function AddUser($Username, $Mail, $Passphrase, $Role='USER')
  {
    $oUser = new \Grepodata\Library\Model\User();
    $oUser->username   = $Username;
    $oUser->email      = $Mail;
    $oUser->passphrase = $Passphrase;
    $oUser->role       = $Role;
    $oUser->save();
    return $oUser;
  }

  /**
   * Search users
   * @param $query
   * @param int $From
   * @param int $Size
   * @return \Grepodata\Library\Model\User[]
   */
  public static function SearchUser($query, $From=0, $Size=10)
  {
    return \Grepodata\Library\Model\User::where('username', 'LIKE', '%'.$query.'%')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * Return a list of worlds where the user is active in
   * @param \Grepodata\Library\Model\User $oUser
   */
  public static function GetActiveWorldsByUser(\Grepodata\Library\Model\User $oUser)
  {
    return DB::select( DB::raw("
        Select DISTINCT World.grep_id as world FROM (
            (SELECT Indexer_info.world
                FROM Indexer_roles
                LEFT JOIN Indexer_info ON Indexer_info.key_code = Indexer_roles.index_key
                WHERE Indexer_roles.user_id = ".$oUser->id.")
            UNION
            (SELECT world
              FROM Indexer_intel_shared
              WHERE user_id = ".$oUser->id.")
        ) as worlds
        LEFT JOIN World on World.grep_id = worlds.world
        WHERE World.stopped = 0
      "));
  }

}

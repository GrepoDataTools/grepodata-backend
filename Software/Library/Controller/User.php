<?php

namespace Grepodata\Library\Controller;

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

}
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
   * @param $Id
   * @return \Grepodata\Library\Model\User
   */
  public static function GetUserById($Id)
  {
    return \Grepodata\Library\Model\User::where('id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param $Mail
   * @param $Passphrase
   * @param string $Role
   * @return \Grepodata\Library\Model\User
   */
  public static function AddUser($Mail, $Passphrase, $Role='USER')
  {
    $oUser = new \Grepodata\Library\Model\User();
    $oUser->email      = $Mail;
    $oUser->passphrase = $Passphrase;
    $oUser->role       = $Role;
    $oUser->save();
    return $oUser;
  }

//  public static function NewSession(\Grepodata\Library\Model\User $oUser)
//  {
//    $oUser = new \Grepodata\Library\Model\User();
//    $oUser->email      = $Mail;
//    $oUser->passphrase = $Passphrase;
//    $oUser->role       = $Role;
//    $oUser->save();
//    return $oUser;
//  }
}
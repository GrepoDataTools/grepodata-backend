<?php

namespace Grepodata\Library\Router;

use Grepodata\Library\Model\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ReallySimpleJWT\Exception\ValidateException;
use ReallySimpleJWT\Token;

class Authentication
{

  /**
   * @param User $oUser
   * @return bool|string
   */
  public static function generateJWT(User $oUser)
  {
    // Payload
    $aPayload = array(
      'iss' => 'https://grepodata.com',
      'uid' => $oUser->id,
      'iat' => time(),
      'exp' => time() + (24 * 60 * 60),
    );

    // Encode
    try {
      $jwt = Token::customPayload($aPayload, JWT_SECRET);
    } catch (ValidateException $e) {
      return false;
    }

    return $jwt;
  }

  /**
   * @param $JWT
   * @param bool $bDieIfInvalid
   * @return bool|User
   */
  public static function verifyJWT($JWT, $bDieIfInvalid = true)
  {
    $bValid = Token::validate($JWT, JWT_SECRET);
    if ($bValid === false) {
      if ($bDieIfInvalid) {
        self::invalidJWT();
      } else {
        return false;
      }
    }

    $aPayload = Token::getPayload($JWT, JWT_SECRET);

    try {
      $oUser = \Grepodata\Library\Controller\User::GetUserById($aPayload['uid']);
      return $oUser;
    } catch (ModelNotFoundException $e) {
      if ($bDieIfInvalid) {
        self::invalidJWT();
      } else {
        return false;
      }
    }
  }

  /**
   * Return the user for this token if it exists
   * @param $Token
   * @return User
   */
  public static function verifyAccountToken($Token)
  {
    try {
      $oUser = \Grepodata\Library\Controller\User::GetUserByToken($Token);
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(3006, array(), 401);
    }
    return $oUser;
  }

  private static function invalidJWT()
  {
    ResponseCode::errorCode(3003, array(), 401);
  }
}
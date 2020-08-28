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
   * @return string
   */
  public static function generateJWT(User $oUser, $bIsRefreshToken = false)
  {
    // access_token has en expiration of 24 hours
    $expirationWindow = (24 * 60 * 60);

    if ($bIsRefreshToken) {
      // refresh_token has an expiration of 90 days
      $expirationWindow = (90 * 24 * 60 * 60);
    }

    // Payload
    $aPayload = array(
      'iss' => 'https://grepodata.com',
      'uid' => $oUser->id,
      'mail' => $oUser->email,
      'username' => $oUser->username,
      'mail_is_confirmed' => ($oUser->is_confirmed==true?true:false),
      'ref' => $bIsRefreshToken,
      'iat' => time(),
      'exp' => time() + $expirationWindow
    );

    // Encode
    try {
      $jwt = Token::customPayload($aPayload, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);
    } catch (ValidateException $e) {
      return false;
    }

    return $jwt;
  }

  /**
   * @param $JWT
   * @param bool $bIsRefreshToken
   * @return bool|User
   */
  public static function expiresIn($JWT, $bIsRefreshToken = false)
  {
    $aPayload = Token::getPayload($JWT, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);
    return $aPayload['exp'] - time();
  }

  /**
   * @param $JWT
   * @param bool $bDieIfInvalid
   * @param bool $bIsRefreshToken
   * @return bool|User
   */
  public static function verifyJWT($JWT, $bDieIfInvalid = true, $bIsRefreshToken = false)
  {
    $bValid = Token::validate($JWT, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);
    if ($bValid === false) {
      if ($bDieIfInvalid) {
        self::invalidJWT();
      } else {
        return false;
      }
    }

    $aPayload = Token::getPayload($JWT, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);

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
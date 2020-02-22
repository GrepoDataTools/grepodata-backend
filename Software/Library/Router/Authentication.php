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
   * @return User
   */
  public static function verifyJWT($JWT)
  {
    $bValid = Token::validate($JWT, JWT_SECRET);
    if ($bValid === false) {
      self::invalidJWT();
    }

    $aPayload = Token::getPayload($JWT, JWT_SECRET);

    try {
      $oUser = \Grepodata\Library\Controller\User::GetUserById($aPayload['uid']);
    } catch (ModelNotFoundException $e) {
      self::invalidJWT();
    }
    return $oUser;
  }

  private static function invalidJWT()
  {
    die(BaseRoute::OutputJson(array(
      'message'     => 'Invalid access token.'
    ), 401));
  }
}
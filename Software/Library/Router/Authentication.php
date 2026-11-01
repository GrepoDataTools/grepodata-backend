<?php

namespace Grepodata\Library\Router;

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use ReallySimpleJWT\Exception\ValidateException;
use ReallySimpleJWT\Token;

class Authentication
{
  const REASON_EXPIRED = 'expired';

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
      'mail' => self::maskEmail($oUser->email),
      'username' => $oUser->username,
      'mail_is_confirmed' => ($oUser->is_confirmed==true?true:false),
      'account_is_linked' => ($oUser->is_linked==true?true:false),
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
   * @param bool $bUpdateActivity
   * @return bool|User
   */
  public static function verifyJWT($JWT, $bDieIfInvalid = true, $bIsRefreshToken = false, $bUpdateActivity=false)
  {
    $bValid = Token::validate($JWT, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);
    if ($bValid === false) {
      if ($bDieIfInvalid) {
        self::invalidJWT(self::describeInvalidToken($JWT, $bIsRefreshToken), $JWT, $bIsRefreshToken);
      } else {
        return false;
      }
    }

    $aPayload = Token::getPayload($JWT, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);

    try {
      $oUser = \Grepodata\Library\Controller\User::GetUserById($aPayload['uid']);

      if ($oUser->is_deleted === 1) {
        self::invalidJWT('user is deleted', $JWT, $bIsRefreshToken);
      }

      if ($bUpdateActivity) {
        try {
          if ($oUser->last_activity < Carbon::now()->subHour()) {
            // Only update if last activity is older then 1 hour
            $oUser->last_activity = Carbon::now();
            $oUser->save();
          }
        } catch (\Exception $e) {
          Logger::warning("Unable to update user activity: ".$e->getMessage());
        }
      }

      return $oUser;
    } catch (ModelNotFoundException $e) {
      if ($bDieIfInvalid) {
        self::invalidJWT('user not found', $JWT, $bIsRefreshToken);
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
  public static function verifyAccountToken($Token, $bDieOnFailure = true)
  {
    if (empty($Token)) {
      if ($bDieOnFailure===true) {
        ResponseCode::errorCode(3006, array(), 401);
      } else {
        throw new ModelNotFoundException();
      }
    }
    try {
      $oUser = \Grepodata\Library\Controller\User::GetUserByToken($Token);
    } catch (ModelNotFoundException $e) {
      if ($bDieOnFailure===true) {
        ResponseCode::errorCode(3006, array(), 401);
      } else {
        throw new ModelNotFoundException();
      }
    }
    return $oUser;
  }

  /**
   * Reason why the signature/structure/expiration check failed.
   */
  private static function describeInvalidToken($JWT, $bIsRefreshToken)
  {
    try {
      $oParser = Token::parser((string) $JWT, $bIsRefreshToken ? REFRESH_SECRET : JWT_SECRET);
      $oParser->validate()->validateExpiration();
      $oParser->validateNotBefore();
      return 'unknown validation failure';
    } catch (ValidateException $e) {
      // Code 4 is only thrown after the signature check passed
      return $e->getCode() === 4 ? self::REASON_EXPIRED : 'token rejected: ' . $e->getMessage();
    } catch (\Exception $e) {
      return 'token rejected: ' . $e->getMessage();
    }
  }

  private static function invalidJWT($sReason = 'unknown', $JWT = null, $bIsRefreshToken = false)
  {
    if ($sReason === self::REASON_EXPIRED) {
      ResponseCode::errorCode(3003, array(), 401);
    }

    try {
      // Claims are read without signature verification; for diagnostics only. The token itself is never logged.
      $aClaims = array();
      $aParts = explode('.', (string) $JWT);
      if (count($aParts) === 3) {
        $aDecoded = json_decode(base64_decode(strtr($aParts[1], '-_', '+/')), true);
        if (is_array($aDecoded)) {
          $aClaims = $aDecoded;
        }
      }
      $sUid = isset($aClaims['uid']) ? $aClaims['uid'] : 'n/a';
      $sExp = isset($aClaims['exp']) ? date('Y-m-d H:i:s', (int) $aClaims['exp']) : 'n/a';
      $sIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'n/a';

      $sCaller = 'n/a';
      foreach (debug_backtrace() as $aCall) {
        if ($aCall['function'] == 'verifyJWT') {
          $sCaller = (isset($aCall['file']) ? $aCall['file'] : '?') . ':' . (isset($aCall['line']) ? $aCall['line'] : '?');
          break;
        }
      }

      Logger::warning("InvalidJWT called: reason='" . $sReason . "' uid=" . $sUid . ' exp=' . $sExp
        . ' refresh_token=' . ($bIsRefreshToken ? 'true' : 'false') . ' ip=' . $sIp . ' caller=' . $sCaller);
    } catch (\Exception $e) {
      Logger::warning("Error tracing JWT failure: ".$e->getMessage());
    }
    ResponseCode::errorCode(3003, array(), 401);
  }

  public static function maskEmail($email) {
    $mail_parts = explode("@", $email);
    $length = strlen($mail_parts[0]);

    if($length <= 4 & $length > 1)
    {
      $show = 1;
    }else{
      $show = floor($length/2);
    }

    $hide = $length - $show;
    $replace = str_repeat("*", $hide);

    return substr_replace ( $mail_parts[0] , $replace , $show, $hide ) . "@" . substr_replace($mail_parts[1], "**", 0, 2);

  }
}

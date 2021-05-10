<?php

namespace Grepodata\Application\API\Route;

use Carbon\Carbon;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\IndexV2\Event;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\IndexV2\ScriptToken;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Exception\InvalidEmailAddressError;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Authentication extends \Grepodata\Library\Router\BaseRoute
{
  public static function RegisterPOST()
  {
    try {
      // Validate params
      $aParams = self::validateParams(array('mail', 'password', 'username'));

      // Validate captcha
      if (!bDevelopmentMode && isset($aParams['captcha'])) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Validate email
      try {
        if (User::GetUserByMail($aParams['mail'])) {
          ResponseCode::errorCode(3030, array(), 409);
        }
      } catch (ModelNotFoundException $e) {}

      // Validate username
      if (strlen($aParams['username']) < 4) {
        ResponseCode::errorCode(3033, array(), 422);
      } else if (strlen($aParams['username']) > 32) {
        ResponseCode::errorCode(3034, array(), 422);
      }
      try {
        if (User::GetUserByUsername($aParams['username'])) {
          ResponseCode::errorCode(3032, array(), 409);
        }
      } catch (ModelNotFoundException $e) {}

      // Validate password
      if (strlen($aParams['password']) < 8) {
        ResponseCode::errorCode(3031, array(), 422);
      }

      // Hash password
      $hash = password_hash($aParams['password'], PASSWORD_BCRYPT);

      // Save to db
      $oUser = \Grepodata\Library\Controller\User::AddUser($aParams['username'], $aParams['mail'], $hash);

      // Send confirmation email
      if (!bDevelopmentMode) {
        try {
          $Result = self::sendRegistrationMail($oUser);
        } catch (InvalidEmailAddressError $e) {
          // Invalid email used to register, we can not send messages to this address. Cancel registration
          $oUser->delete();
          ResponseCode::errorCode(3035, array(
            'invalid_address' => $aParams['mail']
          ), 422);
        }
      }

      // Login token
      $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);
      $refresh_token = \Grepodata\Library\Router\Authentication::generateJWT($oUser, true);

      // Response
      $aResponseData = array(
        'access_token'  => $jwt,
        'refresh_token' => $refresh_token,
        'expires_in'    => \Grepodata\Library\Router\Authentication::expiresIn($jwt),
        'email_sent'    => isset($Result) && $Result>=1
      );
      ResponseCode::success($aResponseData, 1120);

    } catch (\Exception $e) {
      ResponseCode::errorCode(1000, array(), 500);
    }
  }

  public static function RequestNewConfirmMailGET()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    if (!bDevelopmentMode) {
      try {
        $Result = self::sendRegistrationMail($oUser);
      } catch (InvalidEmailAddressError $e) {
        ResponseCode::errorCode(3035, array(
          'invalid_address' => $oUser->email
        ), 422);
      }
    }

    $Masked = \Grepodata\Library\Router\Authentication::maskEmail($oUser->email);

    // Response
    $aResponseData = array(
      'email_sent' => (isset($Result)&&$Result>=1)||bDevelopmentMode,
      'masked' => $Masked
    );
    ResponseCode::success($aResponseData, 1140);
  }

  public static function ConfirmMailGET()
  {
    try {
      // Validate params
      $aParams = self::validateParams(array('token'));

      try {
        $oUser = \Grepodata\Library\Router\Authentication::verifyAccountToken($aParams['token'], false);
      } catch (ModelNotFoundException $e) {
        Logger::warning("User not found for email confirmation token: ".$aParams['token']);
        header("Location: ".FRONTEND_URL."/profile?token=invalid");
        die();
      }

      if ($oUser->is_confirmed == true) {
        // Already confirmed
        Logger::warning("User is already confirmed: ".$oUser->id);
        header("Location: ".FRONTEND_URL."/profile?token=confirmed");
        die();
      }

      //Logger::warning("confirmed user with token. ".$oUser->id." ".$aParams['token']);
      $oUser->is_confirmed = true;
      $oUser->save();

      // Detect V1 indexes and assign user to index as owner
      try {
        $oIndexes = IndexInfo::allByMail($oUser->email);
        foreach ($oIndexes as $oIndex) {
          try {
            $oIndex->created_by_user = $oUser->id;
            $oIndex->save();
            Roles::SetUserIndexRole($oUser, $oIndex, Roles::ROLE_OWNER);
            Event::addIndexJoinEvent($oIndex, $oUser, 'imported_team_as_owner');
          } catch (\Exception $e) {
            Logger::error("Error transferring old index (".$oIndex->key_code.") ownership to new user: ". $e->getMessage());
          }
        }
      } catch (\Exception $e) {
        Logger::error("Error transferring old indexes to new user (uid ".$oUser->id."): ". $e->getMessage());
      }

      // Redirect to profile
      header("Location: ".FRONTEND_URL."/profile?token=confirmed");
      die();
    } catch (\Exception $e) {
      header("Location: ".FRONTEND_URL."/profile?token=failed");
      die();
    }
  }

  public static function LoginPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('mail', 'password'));

    // Validate captcha
    if (!bDevelopmentMode && isset($aParams['captcha'])) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    // Get user
    try {
      $oUser = User::GetUserByMail($aParams['mail']);
    } catch (ModelNotFoundException $e) {
      try {
        // try as username
        $oUser = User::GetUserByUsername($aParams['mail']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(3004, array(), 401);
      }
    }

    // verify password
    $bValid = password_verify($aParams['password'], $oUser->passphrase);
    if ($bValid === false) {
      ResponseCode::errorCode(3005, array(), 401);
    }

    // Login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);
    $refresh_token = \Grepodata\Library\Router\Authentication::generateJWT($oUser, true);

    // Response
    $aResponse = array(
      'access_token'  => $jwt,
      'refresh_token' => $refresh_token,
      'expires_in'    => \Grepodata\Library\Router\Authentication::expiresIn($jwt)
    );
    ResponseCode::success($aResponse, 1110);
  }

  public static function NewScriptLinkGET()
  {
    /**
     * Generate a unique script identifier with expiration
     */

    $oToken = ScriptToken::NewScriptToken($_SERVER['REMOTE_ADDR']);

    // Response
    $aResponse = array(
      'script_token'  => $oToken->token->toString(),
    );

    try {
      Logger::indexDebug("NewScriptLink - ".$_SERVER['REMOTE_ADDR']." - ". $_SERVER['HTTP_USER_AGENT'] . " - " . $_SERVER['HTTP_REFERER']);
    } catch (\Exception $e) {}

    ResponseCode::success($aResponse, 1150);
  }

  public static function VerifyScriptLinkPOST()
  {
    /**
     * Check if the script link token has been verified and return an access_token for the connected user if true
     */

    // Validate params
    $aParams = self::validateParams(array('script_token'));

    // Check token
    try {
      $oToken = ScriptToken::GetScriptToken($aParams['script_token']);
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(3041, array(), 401);
    }

    // Check expiration
    $Limit = Carbon::now()->subDays(7);
    if ($oToken->created_at < $Limit) {
      // token expired
      ResponseCode::errorCode(3042, array(), 401);
    }

    // Check client
    if ($oToken->client !== $_SERVER['REMOTE_ADDR']) {
      // Invalid client
      ResponseCode::errorCode(3043, array(), 401);
    }

    // Check if a user is linked
    try {
      $oUser = User::GetUserById($oToken->user_id);
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(3040, array(), 401);
    }

    // Get login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);
    $refresh_token = \Grepodata\Library\Router\Authentication::generateJWT($oUser, true);

    // Response
    $aResponse = array(
      'access_token'  => $jwt,
      'refresh_token' => $refresh_token,
      'expires_in'    => \Grepodata\Library\Router\Authentication::expiresIn($jwt)
    );

    ResponseCode::success($aResponse, 1111);
  }

  public static function AuthenticateScriptLinkPOST()
  {
    /**
     * This method will Link the given script uuid to a user account
     * Called from Angular frontend with uuid
     */

    // Validate params
    $aParams = self::validateParams(array('access_token', 'script_token'));

    // Verify token
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Get token
    try {
      $oToken = ScriptToken::GetScriptToken($aParams['script_token']);
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(3041, array(), 401);
    }

    // Already verified
    if ($oToken->user_id === $oUser->id) {
      ResponseCode::success(array(), 1151);
    }

    // Check expiration
    $Limit = Carbon::now()->subDays(100);
    if ($oToken->created_at < $Limit) {
      // token expired
      ResponseCode::errorCode(3042, array(), 401);
    }

    // Check client
    if ($oToken->client !== $_SERVER['REMOTE_ADDR']) {
      // Invalid client
      ResponseCode::errorCode(3043, array(), 401);
    }

    // Add script_token to user
    $oToken->user_id = $oUser->id;
    $oToken->save();

    try {
      Logger::indexDebug("VerifiedScriptLink - ".$_SERVER['REMOTE_ADDR']." - ".$aParams['script_token'] . " - " . $_SERVER['HTTP_USER_AGENT'] . " - " . $_SERVER['HTTP_REFERER']);
    } catch (\Exception $e) {}

    // Response
    ResponseCode::success(array(), 1151);
  }

  /**
   * Verify the access_token
   * Returns 401 status code if token is invalid
   */
  public static function VerifyPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));

    // Verify token
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Response
    $aResponseData = array(
      'access_token'  => $aParams['access_token'],
      'expires_in'    => \Grepodata\Library\Router\Authentication::expiresIn($aParams['access_token']),
      'mail_is_confirmed' => ($oUser->is_confirmed==true?true:false)
    );
    ResponseCode::success($aResponseData, 1102);
  }

  /**
   * Get a new access_token using a refresh_token
   * Returns 401 status code if token is invalid
   */
  public static function RefreshPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('refresh_token'));

    // Verify with refresh token
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['refresh_token'], true, true);

    // Renew login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);
    $refresh_token = \Grepodata\Library\Router\Authentication::generateJWT($oUser, true);

    // Response
    $aResponseData = array(
      'access_token'  => $jwt,
      'refresh_token' => $refresh_token,
      'expires_in'    => \Grepodata\Library\Router\Authentication::expiresIn($jwt)
    );
    ResponseCode::success($aResponseData, 1101);
  }

  /**
   * Send a password reset link to the user
   */
  public static function ForgotPOST()
  {
    try {
      // Validate params
      $aParams = self::validateParams(array('mail'));

      // Validate captcha
      if (!bDevelopmentMode && isset($aParams['captcha'])) {
        BaseRoute::verifyCaptcha($aParams['captcha']);
      }

      // Get user
      try {
        $oUser = User::GetUserByMail($aParams['mail']);
      } catch (ModelNotFoundException $e) {
        ResponseCode::errorCode(3004, array(), 401);
      }

      // Create new user token
      $Token = bin2hex(random_bytes(16));
      $oUser->token = $Token;
      $oUser->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oUser->email,
        'Grepodata password recovery',
        'Hi,<br/>
<br/>
You are receiving this message because a request was made to recover your account on grepodata.com.<br/>
<br/>
Please click on the following link to reset your password:<br/>
<br/>
<a href="https://grepodata.com/reset/'.$Token.'">https://grepodata.com/reset/'.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our password recovery form.<br/>
You can ignore this email if you no longer wish to reset your password.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
        null,
        true,
        false);

      // Response
      $aResponse = array(
        'status' => 'Email sent',
        'result' => $Result >= 1
      );

      return self::OutputJson($aResponse);
    } catch (InvalidEmailAddressError $e) {
      ResponseCode::errorCode(3035, array(), 422);
    } catch (\Exception $e) {
      ResponseCode::errorCode(1000, array(), 500);
    }
  }

  /**
   * Change the user password
   */
  public static function ChangePasswordPOST()
  {
    // Validate params
    $aParams = self::validateParams();

    // Validate captcha
    if (!bDevelopmentMode && isset($aParams['captcha'])) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    if (isset($aParams['token'])) {
      // Change using token
      $oUser = \Grepodata\Library\Router\Authentication::verifyAccountToken($aParams['token']);

    } else if (isset($aParams['access_token'])) {
      // Change using access_token and old password
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Check old password
      if (!isset($aParams['old_password'])) {
        ResponseCode::errorCode(1010, array('fields' => array('old_password')), 400);
      }
      $bValidOldPassword = password_verify($aParams['old_password'], $oUser->passphrase);
      if ($bValidOldPassword == false) {
        ResponseCode::errorCode(3005, array(), 401);
      }

    } else {
      ResponseCode::errorCode(1010, array('fields' => array('token OR access_token')), 400);
    }

    // Check new password
    if (!isset($aParams['new_password'])) {
      ResponseCode::errorCode(1010, array('fields' => array('new_password')), 400);
    }
    $oUser->passphrase = password_hash($aParams['new_password'], PASSWORD_BCRYPT);
    $oUser->token = '';
    $oUser->save();

    // Login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);
    $refresh_token = \Grepodata\Library\Router\Authentication::generateJWT($oUser, true);

    // Response
    $aResponse = array(
      'access_token'  => $jwt,
      'refresh_token' => $refresh_token,
      'expires_in'    => \Grepodata\Library\Router\Authentication::expiresIn($jwt)
    );
    ResponseCode::success($aResponse, 1130);
  }

  /**
   * Delete user account data
   */
  public static function DeleteAccountConfirmedPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('captcha', 'token', 'access_token'));

    // Verify account login and token
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
    $oUserToken = \Grepodata\Library\Router\Authentication::verifyAccountToken($aParams['token']);

    // Validate captcha
    if (!bDevelopmentMode) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    // Delete all personal data by overriding it with random values (linked data will be removed by cleanup script)
    $oUser->username = "DELETED_" . IndexBuilderV2::generateIndexKey(8) . time();
    $oUser->email = "DELETED_" . IndexBuilderV2::generateIndexKey(8) . time();
    $oUser->passphrase = password_hash(IndexBuilderV2::generateIndexKey(12), PASSWORD_BCRYPT);
    $oUser->token = bin2hex(random_bytes(16));
    $oUser->save();

    try {
      // Delete roles
      $aRoles = Roles::DeleteAllUserRoles($oUser);
    } catch (\Exception $e){
      Logger::error("Unable to remove user roles: " . $e->getMessage() . " [".$e->getTraceAsString()."]");
    }

    Logger::error("Account removal completed: " . $oUser->id);

    // Response
    $aResponse = array(
      'success' => true,
      'status' => 'account removed'
    );
    ResponseCode::success($aResponse, 1160);
  }

  /**
   * Change the user password
   */
  public static function DeleteAccountPOST()
  {
    try {
      // Validate params
      $aParams = self::validateParams(array('access_token', 'password'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      Logger::error("Account removal requested: " . $oUser->id);

      // verify password
      $bValid = password_verify($aParams['password'], $oUser->passphrase);
      if ($bValid === false) {
        ResponseCode::errorCode(3005, array(), 401);
      }

      // Create new user token
      $Token = bin2hex(random_bytes(16));
      $oUser->token = $Token;
      $oUser->save();

      // Send confirmation email
      $Result = Client::SendMail(
        'admin@grepodata.com',
        $oUser->email,
        'Grepodata account removal',
        'Hi,<br/>
<br/>
You are receiving this message because a request was made to delete your account on grepodata.com.<br/>
<br/>
Please confirm your request by clicking on the following link:<br/>
<br/>
<a href="https://grepodata.com/delete/'.$Token.'">https://grepodata.com/delete/'.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have access to your GrepoData account.<br/>
You can ignore this email if you no longer wish to remove your account.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
        null,
        true,
        false);

      // Response
      $aResponse = array(
        'status' => 'Email sent',
        'result' => ($Result >= 1 ? true : false)
      );

      return self::OutputJson($aResponse);
    } catch (InvalidEmailAddressError $e) {
      ResponseCode::errorCode(3035, array(), 422);
    } catch (\Exception $e) {
      ResponseCode::errorCode(1000, array(), 500);
    }
  }

  /**
   * @param \Grepodata\Library\Model\User $oUser
   * @return bool|int
   * @throws InvalidEmailAddressError
   */
  private static function sendRegistrationMail(\Grepodata\Library\Model\User $oUser) {
    // Create confirmation link
    if ($oUser->token != null) {
      $Token = $oUser->token;
    } else {
      $Token = bin2hex(random_bytes(16));
      $oUser->token = $Token;
      $oUser->save();
    }

    $Result = false;
    $Result = Client::SendMail(
      'admin@grepodata.com',
      $oUser->email,
      'GrepoData Account Confirmation',
      'Hi,<br/>
<br/>
You are receiving this message because an account was created on grepodata.com using this email address.<br/>
<br/>
Please click on the following link to confirm your account:<br/>
<br/>
<a href="https://api.grepodata.com/confirm?token='.$Token.'">https://api.grepodata.com/confirm?token='.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our account registration form.<br/>
You can ignore this email if you no longer wish to create an account for our website.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
      null,
      true,
      false);

    return $Result;
  }

}

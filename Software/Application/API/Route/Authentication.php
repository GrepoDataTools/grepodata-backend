<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\User;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Router\BaseRoute;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Authentication extends \Grepodata\Library\Router\BaseRoute
{
  public static function RegisterPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('mail', 'password', 'captcha'));

    // Validate captcha
    if (!bDevelopmentMode) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    try {
      if (User::GetUserByMail($aParams['mail'])) {
        ResponseCode::errorCode(3030, array(), 409);
      }
    } catch (ModelNotFoundException $e) {}

    if (strlen($aParams['password']) < 8) {
      ResponseCode::errorCode(3031, array(), 422);
    }

    // Encrypt pass
    $hash = password_hash($aParams['password'], PASSWORD_BCRYPT);

    // Save to db
    $oUser = \Grepodata\Library\Controller\User::AddUser($aParams['mail'], $hash);

    // Login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);

    // Create confirmation link
    $Token = bin2hex(random_bytes(16));
    $oUser->token = $Token;
    $oUser->save();

    // Send confirmation email
    if (!bDevelopmentMode) {
      try {
        $Result = Client::SendMail(
          'admin@grepodata.com',
          $oUser->email,
          'Grepodata account confirmation',
          'Hi,<br/>
<br/>
You are receiving this message because an account was created for your email address on grepodata.com.<br/>
<br/>
Please click on the following link to confirm your account:<br/>
<br/>
<a href="https://api.grepodata.com/auth/confirm?token='.$Token.'">https://api.grepodata.com/auth/confirm?token='.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our account registration form.<br/>
You can ignore this email if you no longer wish to create an account for our website.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
          null,
          true);
      } catch (\Exception $e) {
        Logger::error("Error sending confirmation link for new user (uid".$oUser->id.")");
      }
    }

    // Response
    $aResponseData = array(
      'status'        => 'User created',
      'access_token'  => $jwt,
      'email_sent'    => (isset($Result)&&$Result>=1 ? true : false)
    );
    ResponseCode::success($aResponseData);
  }

  public static function RequestNewConfirmMailPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Create confirmation link
    if ($oUser->token != null) {
      $Token = $oUser->token;
    } else {
      $Token = bin2hex(random_bytes(16));
      $oUser->token = $Token;
      $oUser->save();
    }

    // Send confirmation email
    if (!bDevelopmentMode) {
      try {
        $Result = Client::SendMail(
          'admin@grepodata.com',
          $oUser->email,
          'Grepodata account confirmation',
          'Hi,<br/>
<br/>
You are receiving this message because an account was created for your email address on grepodata.com.<br/>
<br/>
Please click on the following link to confirm your account:<br/>
<br/>
<a href="https://api.grepodata.com/auth/confirm?token='.$Token.'">https://api.grepodata.com/auth/confirm?token='.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our account registration form.<br/>
You can ignore this email if you no longer wish to create an account for our website.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
          null,
          true);
      } catch (\Exception $e) {
        Logger::error("Error sending confirmation link for new user (uid".$oUser->id.")");
      }
    }

    // Response
    $aResponseData = array(
      'status'        => 'Confirmation requested',
      'access_token'  => $aParams['access_token'],
      'email_sent'    => (isset($Result)&&$Result>=1 ? true : false)
    );
    ResponseCode::success($aResponseData);
  }

  public static function ConfirmMailGET()
  {
    try {
      // Validate params
      $aParams = self::validateParams(array('token'));

      try {
        $oUser = User::GetUserByToken($aParams['token']);
      } catch (ModelNotFoundException $e) {
        header("Location: ".FRONTEND_URL."/auth/profile");
        die();
      }

      if ($oUser->is_confirmed == true) {
        // Already confirmed
        header("Location: ".FRONTEND_URL."/auth/profile");
        die();
      }

      $oUser->is_confirmed = true;
      $oUser->token = null;
      $oUser->save();

      // transfer old indexes
      try {
        $oIndexes = IndexInfo::allByMail($oUser->email);
        foreach ($oIndexes as $oIndex) {
          try {
            $oIndex->created_by_user = $oUser->id;
            $oIndex->save();
          } catch (\Exception $e) {
            Logger::error("Error transfering old index (".$oIndex->key_code.") ownership to new user: ". $e->getMessage());
          }
        }
      } catch (\Exception $e) {
        Logger::error("Error transfering old indexes to new user (uid ".$oUser->id."): ". $e->getMessage());
      }

      // Redirect to profile
      header("Location: ".FRONTEND_URL."/auth/profile");
      die();
    } catch (\Exception $e) {
      header("Location: ".FRONTEND_URL."/auth/profile");
      die();
    }
  }

  public static function LoginPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('mail', 'password', 'captcha'));

    // Validate captcha
    if (!bDevelopmentMode) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    // Get user
    try {
      $oUser = User::GetUserByMail($aParams['mail']);
    } catch (ModelNotFoundException $e) {
      ResponseCode::errorCode(3004, array(), 401);
    }

    // verify password
    $bValid = password_verify($aParams['password'], $oUser->passphrase);
    if ($bValid === false) {
      ResponseCode::errorCode(3005, array(), 401);
    }

    // Login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);

    // Response
    $aResponse = array(
      'status'        => 'User login',
      'access_token'  => $jwt
    );
    ResponseCode::success($aResponse);
  }

  public static function ScriptLinkPOST()
  {
    /**
     * This method will Link the given script uuid to a user account
     * Called from Angular frontend with uuid!!
     */

    // Validate params
    $aParams = self::validateParams(array('access_token', 'uuid'));

    die(self::OutputJson(array(
      'message'     => 'Unauthorized.',
    ), 401));

    // TODO userscript: API ScriptAuth has returned a 401: show login popups / flow
    // TODO userscript: IF login flow initiated:
    // TODO userscript:   generate userscript UID => uuid
    // TODO userscript:   save uuid to cookie and local cache
    // TODO userscript:   redirect user to grepodata.com/indexer/auth/{{uuid}}

    // TODO frontend: show login screen if user is not logged in
    // TODO frontend: when logged in: send a request to API ScriptLink with access_token and the uuid

    // TODO API: validate access_token. if invalid => 401
    // TODO API: if valid => link uuid to account => return 200

    // TODO frontend: if 401 => back to login
    // TODO frontend: if 200 => show: 'success! script now linked. go back to game window'

    // TODO userscript: set a 2000ms timeout on the userscript that calls ScriptAuth for next few minutes
    // TODO userscript: ScriptAuth will return 200 when the linking is complete
  }

  public static function ScriptAuthPOST()
  {
    /**
     * This method is called by the userscript every time the script is loaded.
     * Returns an access token if auth is successful based on supplied uuid OR userclient+ip.
     * Otherwise returns a 401
     */

    // Validate params
    $aParams = self::validateParams(array('uuid'));

    die(self::OutputJson(array(
      'message'     => 'Unauthorized.',
    ), 401));

    // TODO userscript: call ScriptAuth every time when script loads with the saved uuid. If uuid is not saved, try auth anyway with empty uuid.

    // TODO API: if uuid is not empty: check auth for uuid and return access token
    // TODO API: if uuid is empty: check auth for userclient+ip and try to find an older uuid anyway
    // TODO API: if uuid is empty and an older uuid was found using userclient+ip, return the uuid AND an access token AND let userscript know the old uuid
    // TODO API: if uuid is invalid or empty and userclient+ip is unknown: return 401

    // TODO userscript: if a 401 is returned by API: Script is not yet linked!! show login popup
    // TODO userscript: if a 401 is returned by API: Index buttons replaced by login popup buttons
    // TODO userscript: if a 200 is returned by API: Script goes to active-linked mode => happy indexing!
  }

  /**
   * Verify the access_token and renew if valid
   * Returns 401 status code if token is invalid
   */
  public static function VerifyPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('access_token'));

    // Verify token
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Renew login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);

    // Response
    $aResponseData = array(
      'status'        => 'Renew token',
      'access_token'  => $jwt,
      'is_confirmed'  => ($oUser->is_confirmed==true?true:false)
    );
    ResponseCode::success($aResponseData);
  }

  /**
   * Send a password reset link to the user
   */
  public static function ForgotPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('mail', 'captcha'));

    // Validate captcha
    if (!bDevelopmentMode) {
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
<a href="https://grepodata.com/auth/reset/'.$Token.'">'.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our password recovery form.<br/>
You can ignore this email if you no longer wish to reset your password.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
      null,
      true);

    // Response
    $aResponse = array(
      'status' => 'Email sent',
      'result' => ($Result >= 1 ? true : false)
    );

    return self::OutputJson($aResponse);
  }

  /**
   * Change the user password
   */
  public static function ChangePasswordPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('captcha'));

    // Validate captcha
    if (!bDevelopmentMode) {
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
    $oUser->save();

    // Response
    $aResponse = array(
      'status' => 'Password changed'
    );
    ResponseCode::success($aResponse);
  }
}
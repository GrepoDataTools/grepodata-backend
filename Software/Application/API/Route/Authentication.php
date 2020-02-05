<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\User;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Mail\Client;
use Grepodata\Library\Router\BaseRoute;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Authentication extends \Grepodata\Library\Router\BaseRoute
{
  public static function RegisterPOST()
  {
    //TODO: TEMP
    if (!bDevelopmentMode) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

    // Validate params
    $aParams = self::validateParams(array('mail', 'password', 'captcha'));

    // Validate captcha
    if (!bDevelopmentMode) {
      BaseRoute::verifyCaptcha($aParams['captcha']);
    }

    try {
      if (User::GetUserByMail($aParams['mail'])) {
        die(self::OutputJson(array(
          'message'     => 'Email address is already in use.'
        ), 401));
      }
    } catch (ModelNotFoundException $e) {}

    if (strlen($aParams['password']) < 8) {
      die(self::OutputJson(array(
        'message'     => 'Password is not strong enough. Your password should be at least 8 characters.'
      ), 401));
    }

    // Encrypt pass
    $hash = password_hash($aParams['password'], PASSWORD_BCRYPT);

    // Save to db
    $oUser = \Grepodata\Library\Controller\User::AddUser($aParams['mail'], $hash);

    // Login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);

    // Create confirmation link
    $Token = md5(IndexBuilder::generateIndexKey(32) . time());
    $oUser->token = $Token;
    $oUser->save();

    // Send confirmation email
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
<a href="https://grepodata.com/confirm/'.$Token.'">'.$Token.'</a><br/>
<br/>
If you did not request this email, someone else may have entered your email address into our account registration form.<br/>
You can ignore this email if you no longer wish to create an account for our website.<br/>
<br/>
Sincerely,<br/>
admin@grepodata.com',
      null,
      true);

    // Response
    $aResponse = array(
      'status'        => 'User created',
      'access_token'  => $jwt,
      'result'        => ($Result >= 1 ? true : false)
    );

    return self::OutputJson($aResponse);
  }

  public static function LoginPOST()
  {
    //TODO: TEMP
    if (!bDevelopmentMode) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

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
      die(self::OutputJson(array(
        'message'     => 'Unknown email address.'
      ), 401));
    }

    // verify password
    $bValid = password_verify($aParams['password'], $oUser->passphrase);
    if ($bValid === false) {
      die(self::OutputJson(array(
        'message'     => 'Invalid password.'
      ), 401));
    }

    // Login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);

    // Response
    $aResponse = array(
      'status'        => 'User login',
      'access_token'  => $jwt
    );

    return self::OutputJson($aResponse);
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

  public static function VerifyPOST()
  {
    //TODO: TEMP
    if (!bDevelopmentMode) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

    // Validate params
    $aParams = self::validateParams(array('access_token'));

    // Verify token
    $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

    // Renew login token
    $jwt = \Grepodata\Library\Router\Authentication::generateJWT($oUser);

    // Response
    $aResponse = array(
      'status'        => 'Renew token',
      'access_token'  => $jwt
    );

    return self::OutputJson($aResponse);
  }

  public static function ForgotPOST()
  {
    //TODO: TEMP
    if (!bDevelopmentMode) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

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
      die(self::OutputJson(array(
        'message'     => 'Unknown email address.'
      ), 401));
    }

    // TODO: TEMP
    if (!in_array($oUser->email, ['admin@grepodata.com'])) {
      die(self::OutputJson(array(
        'message'     => 'Unauthorized.',
      ), 401));
    }

    // Create confirmation link
    $Token = md5(IndexBuilder::generateIndexKey(32) . time());
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
<a href="https://grepodata.com/forgot/'.$Token.'">'.$Token.'</a><br/>
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
}
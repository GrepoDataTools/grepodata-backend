<?php

namespace Grepodata\Library\Router;

// Generic error
define('GD_ERROR_0000', 'Undefined error code');
define('GD_ERROR_1000', 'Unable to handle request');
define('GD_ERROR_1010', 'Bad request. Invalid or missing parameters');

// Authentication
define('GD_ERROR_3000', 'Unable to handle authentication request');
define('GD_ERROR_3001', 'Invalid credentials');
define('GD_ERROR_3002', 'Invalid captcha key');
define('GD_ERROR_3003', 'Invalid access token');
define('GD_ERROR_3004', 'Unknown email address');
define('GD_ERROR_3005', 'Invalid password');
define('GD_ERROR_3006', 'Invalid account token');
define('GD_ERROR_3007', 'Unknown username address');
define('GD_ERROR_3010', 'Email address is still unconfirmed. Confirm your email address to continue');
define('GD_ERROR_3011', 'Invalid email confirmation token.');
define('GD_ERROR_3030', 'This email address is already in use');
define('GD_ERROR_3031', 'Password is not strong enough. Your password should be at least 8 characters');
define('GD_ERROR_3032', 'This username is already in use');

// Discord
define('GD_ERROR_5000', 'Unable to handle discord request');
define('GD_ERROR_5001', 'This guild has not set a default index key required for this request');
define('GD_ERROR_5002', 'This guild has not set a default server required for this request');
define('GD_ERROR_5003', 'Report not found for these parameters');
define('GD_ERROR_5004', 'Server was not able to build this image (internal error)');

class ResponseCode
{
  /**
   * return a default successful response
   * @param array $aExtraResponseData
   * @param int $HttpCode
   */
  public static function success($aExtraResponseData = array(), $HttpCode = 200)
  {
    $aResponseData = array(
      'success' => true,
    );
    if (is_array($aExtraResponseData) && sizeof($aExtraResponseData)) {
      $aResponseData = array_merge($aResponseData, $aExtraResponseData);
    }
    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode($aResponseData, JSON_PRETTY_PRINT));
  }

  /**
   * die with a predefined error code
   * @param int $Code
   * @param array $aExtraResponseData
   * @param int $HttpCode
   */
  public static function errorCode($Code = 1000, $aExtraResponseData = array(), $HttpCode = 200)
  {
    $Message = constant('GD_ERROR_'.$Code);
    if (is_null($Message)) {
      $Message = GD_ERROR_0000;
    }

    $aResponseData = array(
      'success' => false,
      'error_code' => $Code,
      'message' => $Message,
    );
    if (is_array($aExtraResponseData) && sizeof($aExtraResponseData)) {
      $aResponseData = array_merge($aResponseData, $aExtraResponseData);
    }
    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode($aResponseData, JSON_PRETTY_PRINT));
  }

  /**
   * Die with a custom message and error code of 1000
   * @param $Message string custom message
   * @param int $ErrorCode
   * @param int $HttpCode
   */
  public static function custom($Message, $ErrorCode = 1000, $HttpCode = 200)
  {
    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode(array(
      'success' => false,
      'error_code' => $ErrorCode,
      'message' => $Message,
    ), JSON_PRETTY_PRINT));
  }
}
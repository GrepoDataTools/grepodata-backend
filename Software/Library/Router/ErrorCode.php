<?php

namespace Grepodata\Library\Router;

// Generic error
define('GD_ERROR_0000', 'Undefined error code');
define('GD_ERROR_1000', 'Unable to handle request');

// Discord
define('GD_ERROR_5000', 'Unable to handle discord request');
define('GD_ERROR_5001', 'This guild has not set a default index key required for this request');
define('GD_ERROR_5002', 'This guild has not set a default server required for this request');
define('GD_ERROR_5003', 'Report not found for these parameters');
define('GD_ERROR_5004', 'Server was not able to build this image (internal error)');

class ErrorCode
{
  /**
   * die with a predefined error code
   * @param int $Code
   * @param int $HttpCode
   */
  public static function code($Code = 1000, $HttpCode = 200)
  {
    $Message = constant('GD_ERROR_'.$Code);
    if (is_null($Message)) {
      $Message = GD_ERROR_0000;
    }

    header('Content-Type: application/json', true, $HttpCode);
    die(json_encode(array(
      'success' => false,
      'error_code' => $Code,
      'message' => $Message,
    ), JSON_PRETTY_PRINT));
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
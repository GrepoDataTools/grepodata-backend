<?php

namespace Grepodata\Library\Router;

class BaseRoute
{
  private static $oRequestContext;
  private static $oRequest;

  public function __construct(\Symfony\Component\Routing\RequestContext $oRequestContext, \Symfony\Component\HttpFoundation\Request $oRequest)
  {
    self::$oRequestContext  = $oRequestContext;
    self::$oRequest         = $oRequest;
  }

  public static function OutputJson($aData, $HttpCode = 200)
  {
    header('Content-Type: application/json', true, $HttpCode);
    return json_encode($aData, JSON_PRETTY_PRINT);
  }

  /**
   * Return array of request params. Returns error if missing param names
   * @param array $aParamNames
   * @param array $aCheckHeaders
   * @return array|mixed
   * @throws \Exception
   */
  public static function validateParams($aParamNames = array(), $aCheckHeaders = array())
  {
    if (!is_array($aParamNames)) {
      throw new \Exception("Param names must be array");
    }

    // Get params
    $aParams = array();
    if (self::$oRequestContext->getMethod() == 'GET') $aParams = self::getGetVars();
    else if (self::$oRequestContext->getMethod() == 'POST') $aParams = self::getPostVars();
    else if (self::$oRequestContext->getMethod() == 'PUT') $aParams = self::getPutVars();
    else if (self::$oRequestContext->getMethod() == 'DELETE') $aParams = self::getDeleteVars();
    else {
      throw new \Exception("Unhandled HTTP method: " . self::$oRequestContext->getMethod());
    }

    // Check headers
    if (!empty($aCheckHeaders) || in_array('access_token', $aParamNames)) {
      $aHeaders = apache_request_headers();
      foreach ($aCheckHeaders as $HeaderParamName) {
        if (key_exists($HeaderParamName, $aHeaders) && !key_exists($HeaderParamName, $aParams)) {
          $aParams[$HeaderParamName] = $aHeaders[$HeaderParamName];
        }
      }
      if (key_exists('access_token', $aHeaders) && !key_exists('access_token', $aCheckHeaders)) {
        $aParams['access_token'] = $aHeaders['access_token'];
      }
    }

    // Validate
    $aInvalidParams = array();
    foreach ($aParamNames as $Param) {
      if (!isset($aParams[$Param]) || $aParams[$Param] == '') $aInvalidParams[] = $Param;
    }

    if (!empty($aInvalidParams)) {
      ResponseCode::errorCode(1010, array('fields'  => $aInvalidParams), 400);
    }
    return $aParams;
  }

  public static function verifyCaptcha($CaptchaResponse)
  {
    $bValidCaptcha = Captcha::verifyResponse($CaptchaResponse);
    if (!$bValidCaptcha) {
      ResponseCode::errorCode(3002, array(), 401);
    }
    return $bValidCaptcha;
  }

  private static function getGetVars()
  {
    $Query = self::$oRequestContext->getQueryString();
    parse_str($Query, $aVars);
    return $aVars;
  }

  private static function getPostVars()
  {
    return $_POST;
  }

  private static function getPutVars()
  {
    parse_str(file_get_contents('php://input'), $_PUT);
    return $_PUT;
  }

  private static function getDeleteVars()
  {
    if (is_array($_GET) && !empty($_GET)) {
      return $_GET;
    }
    parse_str(file_get_contents('php://input'), $_PUT);
    return $_PUT;
  }
}
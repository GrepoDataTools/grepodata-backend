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

  public static function validateParams($aParamNames = array())
  {
    // Get params
    $aParams = array();
    if (self::$oRequestContext->getMethod() == 'GET') $aParams = self::getGetVars();
    else if (self::$oRequestContext->getMethod() == 'POST') $aParams = self::getPostVars($aParamNames);

    // Validate
    $aInvalidParams = array();
    foreach ($aParamNames as $Param) {
      if (!isset($aParams[$Param]) || $aParams[$Param] == '') $aInvalidParams[] = $Param;
    }

    if (!empty($aInvalidParams)) {
      //error_log(json_encode($aInvalidParams));
      die(self::OutputJson(array(
        'message' => 'Bad request! Invalid or missing fields.',
        'fields'  => $aInvalidParams
      ), 400));
    }
    return $aParams;
  }

  public static function verifyCaptcha($CaptchaResponse)
  {
    $bValidCaptcha = Captcha::verifyResponse($CaptchaResponse);
    if (!$bValidCaptcha) {
      die(self::OutputJson(array(
        'message'     => 'Invalid captcha key.'
      ), 401));
    }
    return $bValidCaptcha;
  }

  private static function getGetVars()
  {
    $Query = self::$oRequestContext->getQueryString();
    parse_str($Query, $aVars);
    return $aVars;
  }

  private static function getPostVars($aParamNames)
  {
    $aVars = array();
    foreach ($aParamNames as $ParamName) {
      if (isset($_POST[$ParamName])) $aVars[$ParamName] = $_POST[$ParamName];
    }

    // Check textual post params not contained in $_POST
//    if (sizeof($aVars) == 0) {
//      $RequestData = self::$oRequest->getContent();
//      parse_str($RequestData, $aVars);
//    }

    return $aVars;

  }
}
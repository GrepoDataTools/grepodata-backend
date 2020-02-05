<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Logger\Pushbullet;
use Grepodata\Library\Router\BaseRoute;

class Captcha extends BaseRoute
{
  public static function VerifyPOST()
  {
    // Validate params
    $aParams = self::validateParams(array('response'));

    // Verify
    $bValid = true;
    if (!bDevelopmentMode) {
      $bValid = BaseRoute::verifyCaptcha($aParams['response']);
    }

    // Response
    $aResponse = array(
      'success' => $bValid
    );
    return self::OutputJson($aResponse);
  }
}
<?php

namespace Grepodata\Library\Router;

class Captcha
{

  public static function verifyResponse($Response)
  {
    // Verify
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $Secret = CAPTCHA_SECRET;

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
      'secret' => $Secret,
      'response' => $Response
    )));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $Validation = curl_exec($ch);
    curl_close($ch);

    //check for success
    if ($Validation != null && (strpos($Validation, 'success": true,')!==FALSE)) {
      return true;
    }
    return false;
  }

}
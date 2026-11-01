<?php

namespace Grepodata\Library\BuyMeACoffee;

class Client
{
  const BASE_URL = 'https://developers.buymeacoffee.com/api/v1';

  /**
   * List one-time supporters (donations/coffees), newest first.
   * @param int $Page
   * @return array
   * @throws \Exception
   */
  public static function getSupporters($Page = 1)
  {
    $Url = self::BASE_URL . '/supporters?page=' . intval($Page);

    $ch = curl_init($Url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer ' . PRIVATE_BUYMEACOFFEE_TOKEN,
      'Accept: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    $Response = curl_exec($ch);
    if ($Response === false) {
      $Error = curl_error($ch);
      curl_close($ch);
      throw new \Exception('BuyMeACoffee request failed: ' . $Error);
    }

    $HttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $Decoded = json_decode($Response, true);
    if ($HttpCode != 200 || !is_array($Decoded)) {
      throw new \Exception('BuyMeACoffee request returned HTTP ' . $HttpCode . ': ' . $Response);
    }

    return isset($Decoded['data']) ? $Decoded['data'] : array();
  }

}

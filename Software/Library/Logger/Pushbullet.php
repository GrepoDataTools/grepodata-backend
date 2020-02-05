<?php

namespace Grepodata\Library\Logger;

class Pushbullet
{
  protected static $token;
  protected static $url;

  public static function GetInstance()
  {
    static $inst = null;
    if ($inst === null) {
      $inst = new Pushbullet();
    }
    return $inst;
  }

  public static function SetConfiguration($aConfiguration)
  {
    if (isset($aConfiguration['token']) && isset($aConfiguration['url'])) {
      static::$token = $aConfiguration['token'];
      static::$url = $aConfiguration['url'];
      return true;
    }
    return false;
  }

  public static function SendPushMessage($message, $title = 'gd notification')
  {
    if (!isset($message) || $message == '') {
      return false;
    }

    // Build request
    $aParams = array(
      'body' => $message,
      'title' => $title,
      'type' => 'note',
    );
    $data = json_encode($aParams);

    // Do curl
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, static::$url);
    curl_setopt($curl, CURLOPT_USERPWD, static::$token);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data)
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);

    // Execute
    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
  }

  private function __construct()
  {
  }

}
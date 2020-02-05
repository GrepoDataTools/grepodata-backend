<?php

namespace Grepodata\Library\Elasticsearch;

use Elasticsearch\ClientBuilder;

class Client
{
  protected static $hosts;

  /**
   * @return \Elasticsearch\Client
   */
  public static function GetInstance($NumRetries=5)
  {
    static $inst = null;
    if ($inst === null) {
      $inst = ClientBuilder::create()
        ->setRetries($NumRetries)
        ->setHosts(self::$hosts)
        ->build();
      $t=2;
    }
    return $inst;
  }

  public static function SetConfiguration($aConfiguration)
  {
    if (isset($aConfiguration['hosts'])) {
      static::$hosts = $aConfiguration['hosts'];
      return true;
    }
    return false;
  }
}
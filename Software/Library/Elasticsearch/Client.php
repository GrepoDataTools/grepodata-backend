<?php

namespace Grepodata\Library\Elasticsearch;

use Elasticsearch\ClientBuilder;

class Client
{
  protected static $hosts;

  /**
   * @param int $NumRetries
   * @param bool $bForceNew
   * @return \Elasticsearch\Client
   */
  public static function GetInstance($NumRetries = 5, $bForceNew = false)
  {
    static $inst = null;
    if ($inst === null || $bForceNew) {
      $inst = ClientBuilder::create()
        ->setRetries($NumRetries)
        ->setHosts(self::$hosts)
        ->build();
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
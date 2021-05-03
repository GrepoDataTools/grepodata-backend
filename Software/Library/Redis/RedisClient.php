<?php

namespace Grepodata\Library\Redis;

use Grepodata\Library\Logger\Logger;
use Redis;

class RedisClient
{
  const ALLIANCE_WARS_PREFIX = 'gd_alliance_wars_'; // followed by: {alliance_id}{world}
  const INDEXER_PLAYER_PREFIX = 'gd_indexer_player_intel_'; // followed by: {uid}{player_id}{world}
  const INDEXER_ALLIANCE_PREFIX = 'gd_indexer_alliance_intel_'; // followed by: {uid}{alliance_id{world}

  /**
   * @return Redis
   */
  private static function GetInstance()
  {
    static $inst = null;
    if ($inst === null) {
      $oRedis = new Redis();
      $oRedis->connect(REDIS_HOST, REDIS_PORT);
      $inst = $oRedis;
    }
    return $inst;
  }

  /**
   * Store a value
   * @param $Key
   * @param $Data
   * @param int $TimeoutSeconds Time in seconds
   * @return bool
   */
  public static function SetKey($Key, $Data, $TimeoutSeconds=60): bool
  {
    if (bDevelopmentMode) return false;
    try {
      $oRedis = self::GetInstance();
      return $oRedis->set($Key, $Data, ['nx', 'ex'=>$TimeoutSeconds]);
    } catch (\Exception $e) {
      Logger::warning("Redis SetKey Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
    }
    return false;
  }

  /**
   * Get a value. Returns FALSE if the key does not exist
   * @param $Key
   * @return false|mixed|string
   */
  public static function GetKey($Key)
  {
    if (bDevelopmentMode) return false;
    try {
      $oRedis = self::GetInstance();
      return $oRedis->get($Key);
    } catch (\Exception $e) {
      Logger::warning("Redis GetKey Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
    }
    return false;
  }

  /**
   * Return the TTL or false of a given key
   * @param $Key
   * @return false|mixed|string
   */
  public static function GetTTL($Key)
  {
    if (bDevelopmentMode) return false;
    try {
      $oRedis = self::GetInstance();
      return $oRedis->ttl($Key);
    } catch (\Exception $e) {
      Logger::warning("Redis GetTTL Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
    }
    return false;
  }

  /**
   * Delete the key
   * @param $Key
   * @return false|mixed|string
   */
  public static function Delete($Key)
  {
    if (bDevelopmentMode) return false;
    try {
      $oRedis = self::GetInstance();
      return $oRedis->del($Key) > 0;
    } catch (\Exception $e) {
      Logger::warning("Redis Delete Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
    }
    return false;
  }
}

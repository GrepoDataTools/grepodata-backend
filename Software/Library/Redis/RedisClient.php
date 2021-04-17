<?php

namespace Grepodata\Library\Redis;

use Grepodata\Library\Logger\Logger;
use Redis;

class RedisClient
{
  const INDEXER_PLAYER_PREFIX = 'gd_indexer_player_intel_'; // followed by: {uid}_{player_id}_{world}
  const INDEXER_ALLIANCE_PREFIX = 'gd_indexer_alliance_intel_'; // followed by: {uid}_{alliance_id}_{world}
  const INDEXER_TOWN_PREFIX = 'gd_indexer_town_intel_'; // followed by: {uid}_{town_id}_{world}

  /**
   * @return Redis
   */
  public static function GetInstance()
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
    try {
      $oRedis = self::GetInstance();
      return $oRedis->get($Key);
    } catch (\Exception $e) {
      Logger::warning("Redis GetKey Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
    }
    return false;
  }
}

<?php

namespace Grepodata\Library\Redis;

use Grepodata\Library\Logger\Logger;
use Redis;

class RedisClient
{
  const ALLIANCE_WARS_PREFIX = 'gd_alliance_wars_'; // followed by: {alliance_id}{world}
  const INDEXER_PLAYER_PREFIX = 'gd_indexer_player_intel_'; // followed by: {uid}{player_id}{world}
  const INDEXER_ALLIANCE_PREFIX = 'gd_indexer_alliance_intel_'; // followed by: {uid}{alliance_id{world}
  const COMMAND_STATE_PREFIX = 'cmd_state_'; // followed by: {team}
  const COMMAND_DATA_PREFIX = 'cmd_data_'; // followed by: {team}
  const WEBSOCKET_TOKEN_PREFIX = 'wst-'; // followed by: {websocket_token}

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
   * Store a value (do not overwrite)
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
   * Store a value and overwrite the old value if it already exists
   * @param $Key
   * @param $Data
   * @param int $TimeoutSeconds Time in seconds
   * @return bool
   */
  public static function UpsertKey($Key, $Data, $TimeoutSeconds=60): bool
  {
    if (bDevelopmentMode) return false;
    try {
      $oRedis = self::GetInstance();
      return $oRedis->set($Key, $Data, ['ex'=>$TimeoutSeconds]);
    } catch (\Exception $e) {
      Logger::warning("Redis UpsertKey Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
    }
    return false;
  }

  /**
   * Publish a message to a channel
   * @param $Channel
   * @param $Message
   * @return int Number of clients that received the message
   */
  public static function Publish($Channel, $Message): int
  {
    if (bDevelopmentMode) return false;
    try {
      $oRedis = self::GetInstance();
      return $oRedis->publish($Channel, $Message);
    } catch (\Exception $e) {
      Logger::error("Redis Publish Exception: ".$e->getMessage()." [".$e->getTraceAsString()."]");
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

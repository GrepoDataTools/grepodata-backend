<?php

namespace Grepodata\Library\Redis;

use Grepodata\Library\Logger\Logger;

class RedisHelper
{

  /**
   * Send a heartbeat message over the backbone channel
   * @return int Number of receivers
   */
  public static function SendBackboneHeartbeat(): int
  {
    return RedisClient::Publish(REDIS_BACKBONE_CHANNEL, "{\"type\":\"redis_heartbeat\"}");
  }

  /**
   * Send a custom message over the backbone channel
   * @return int Number of receivers
   */
  public static function SendBackboneMessage($Message): int
  {
    if (is_array($Message)) {
      $Message = json_encode($Message);
    }
    return RedisClient::Publish(REDIS_BACKBONE_CHANNEL, $Message);
  }

}

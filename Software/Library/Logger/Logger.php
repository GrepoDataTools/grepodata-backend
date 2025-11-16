<?php

namespace Grepodata\Library\Logger;

use \Datetime;
use Grepodata\Library\Model\Operation_log;

class Logger
{
  const level_error   = 1;
  const level_warning = 2;
  const level_debug   = 3;
  const level_silly   = 4;
  const level_index   = 10;
  const level_v2_migration = 100;

  /**
   * Enable local output (for testing purposes)
   */
  private static $local;
  public static function enableDebug()
  {
    static::$local = true;
  }

  public static function silly($Message)
  {
    self::writeLogMessage(array(
      'message' => $Message,
      'level'   => self::level_silly,
      'pid'     => self::getPid()
    ));
  }

  public static function debugInfo($Message)
  {
    self::writeLogMessage(array(
      'message' => $Message,
      'level'   => self::level_debug,
      'pid'     => self::getPid()
    ));
  }

  public static function warning($Message)
  {
    error_log('Logger.warning: '.$Message);
    self::writeLogMessage(array(
      'message' => $Message,
      'level'   => self::level_warning,
      'pid'     => self::getPid()
    ));
  }

  public static function indexDebug($Message)
  {
    self::writeLogMessage(array(
      'message' => $Message,
      'level'   => self::level_index,
      'pid'     => self::getPid()
    ));
  }

  public static function v2Migration($Message)
  {
    self::writeLogMessage(array(
      'message' => $Message,
      'level'   => self::level_v2_migration,
      'pid'     => self::getPid()
    ));
  }

  public static function error($Message)
  {
    error_log('Logger.error: '.$Message);
    self::writeLogMessage(array(
      'message' => $Message,
      'level'   => self::level_error,
      'pid'     => self::getPid()
    ));
  }

  private static function writeLogMessage($aParams)
  {
    try {
      list($usec, $sec) = explode(" ", microtime());
      $aParams['microtime'] = substr($usec, 2);
      Operation_log::create($aParams);

      // Optional local debugging
      if (isset(static::$local) && static::$local && PHP_SAPI == "cli") {
        self::logLocal($aParams);
      }

    } catch (\Exception $e) {
      Pushbullet::SendPushMessage("Error while logging. (".$e->getMessage().")", 'critical GD error');
    }
  }

  private static function logLocal($aParams)
  {
    // Build micro date
    $localMessagePrefix = "";
    $t = microtime(true);
    $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
    $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
    $currentTime = $d->format("Y-m-d H:i:s.u");

    // Level colors
    switch ($aParams['level']) {
      case self::level_error:
        $localMessagePrefix .= "\033[31m[".$currentTime."] ERROR -\033[0m ";
        break;
      case self::level_warning:
        $localMessagePrefix .= "\033[33m[".$currentTime."] WARNING -\033[0m ";
        break;
      case self::level_debug:
        $localMessagePrefix .= "\033[34m[".$currentTime."] INFO -\033[0m ";
        break;
      case self::level_silly:
        $localMessagePrefix .= "\033[37m[".$currentTime."] SILLY -\033[0m ";
        break;
      default:
        $localMessagePrefix .= "\033[32m[".$currentTime."] -\033[0m ";
        break;
    }

    echo $localMessagePrefix . $aParams['message'] . PHP_EOL;
  }

  private static function getPid()
  {
    static $pid = null;
    if ($pid === null) {
      $pid = getmypid();
    }
    return $pid;
  }

}

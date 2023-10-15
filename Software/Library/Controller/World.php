<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;
use DateTimeZone;
use Grepodata\Library\Logger\Logger;

class World
{
  private static $aServers = array('ar', 'br', 'cz', 'de', 'dk', 'en', 'es', 'fr', 'gr', 'hu', 'it',
    'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'se', 'sk', 'tr', 'us', 'zz');

  /**
   * @param $WorldId
   * @return \Grepodata\Library\Model\World
   */
  public static function firstOrNew($WorldId)
  {
    return \Grepodata\Library\Model\World::firstOrNew(array(
      'grep_id' => $WorldId
    ));
  }

  /**
   * @param $WorldId string World identifier
   * @return \Grepodata\Library\Model\World World
   */
  public static function getWorldById($WorldId)
  {
    return \Grepodata\Library\Model\World::where('grep_id', '=', $WorldId)
      ->firstOrFail();
  }

  /**
   * Returns the most recent server for the given region
   * @param $Server string Server identifier
   * @return \Grepodata\Library\Model\World World
   */
  public static function getLatestByServer($Server)
  {
    return \Grepodata\Library\Model\World::where('grep_id', 'LIKE', '%'.$Server.'%')
      ->orderBy('created_at', 'desc')
      ->first();
  }

  /**
   * Returns the previous most recent server for the given region
   * @param $Server string Server identifier
   * @return \Grepodata\Library\Model\World World
   */
  public static function getPreviousWorld($Server)
  {
    return \Grepodata\Library\Model\World::where('grep_id', 'LIKE', '%'.$Server.'%')
      ->orderBy('created_at', 'desc')
      ->offset(1)
      ->first();
  }

  /**
   * Returns the current scoreboard date (current server date +2 hours delay)
   * @param \Grepodata\Library\Model\World $oWorld
   * @return string
   */
  public static function getScoreboardDate(\Grepodata\Library\Model\World $oWorld)
  {
    $ScoreboardDate = $oWorld->getServerTime();

    // Implement a ~2 hour delay on all scoreboards (to catch up with inno API)
    $ScoreboardDate->subMinutes(120);
    return $ScoreboardDate->format('Y-m-d');
  }

  /**
   * Returns the current history date (server date of new history record; yesterday)
   * @param \Grepodata\Library\Model\World $oWorld
   * @return string
   */
  public static function getHistoryDate(\Grepodata\Library\Model\World $oWorld)
  {
    $ScoreboardDate = $oWorld->getServerTime();
    $ScoreboardDate->subDays(1);
    return $ScoreboardDate->format('Y-m-d');
  }

  /**
   * Returns the current scoreboard time (data last modified to world timezone)
   * @param \Grepodata\Library\Model\World $oWorld
   * @param $GrepServerTime string UTC string of downloaded data last modified time
   * @return string
   */
  public static function getScoreboardTime(\Grepodata\Library\Model\World $oWorld, $GrepServerTime)
  {
    $ScoreboardTime = Carbon::createFromFormat('H:i:s', date('H:i:s', strtotime($GrepServerTime)), 'UTC');
    $ScoreboardTime->setTimezone($oWorld->php_timezone);
    return $ScoreboardTime->format('H:i:s');
  }

  /**
   * Returns the minutes untill the next expected scoreboard update
   * This includes a round up to the nearest $UpdateIntervalMinutes
   * @param \Grepodata\Library\Model\World $oWorld
   * @return string
   */
  public static function getNextUpdateDiff(\Grepodata\Library\Model\World $oWorld, $UpdateIntervalMinutes = 10)
  {
    // Find next update in server time
    $ScoreboardLastUpdate = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($oWorld->grep_server_time)), 'UTC');
    $ScoreboardLastUpdate->setTimezone($oWorld->php_timezone);
    $ScoreboardLastUpdate->addHour();

    // wait for import to execute: 10 minute ceil
    $SecondsUntillNextImport = $UpdateIntervalMinutes * 60;
    $UpdateTimestamp = ($SecondsUntillNextImport * ceil($ScoreboardLastUpdate->getTimestamp() / $SecondsUntillNextImport));
    $ScoreboardLastUpdate->setTimestamp($UpdateTimestamp);

    // Add 2 minute update margin
    $ScoreboardLastUpdate->addMinutes(2);

    // Find diff with current server time
    $ServerTime = $oWorld->getServerTime();
    return $ServerTime->diffForHumans($ScoreboardLastUpdate);
  }

  /**
   * @param \Grepodata\Library\Model\World $oWorld
   * @param $UTCTimestamp
   * @return Carbon
   */
  public static function utcTimestampToServerTime(\Grepodata\Library\Model\World $oWorld, $UTCTimestamp) {
    $dt = Carbon::createFromTimestampUTC($UTCTimestamp);
    $dt->setTimezone($oWorld->php_timezone);
    return $dt;
  }

  /**
   * @return bool|\Illuminate\Database\Eloquent\Collection Active worlds
   */
  public static function getAllActiveWorlds()
  {
    $worlds = \Grepodata\Library\Model\World::where('stopped', '=', 0)
      ->orderBy('grep_id', 'desc')
      ->get();

    if (!isset($worlds) || $worlds == null || $worlds == '' || sizeof($worlds) <= 0) {
      Logger::error("Found 0 active worlds in database.");
      return false;
    }

    return $worlds;
  }

  public static function getServers()
  {
    return self::$aServers;
  }
}

<?php

namespace Grepodata\Library\Model;

use Carbon\Carbon;
use DateTimeZone;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed grep_id
 * @property mixed uid
 * @property mixed php_timezone
 * @property mixed name
 * @property mixed stopped
 * @property mixed feature_level
 * @property mixed colormap
 * @property mixed cleaned
 * @property mixed last_reset_time
 * @property mixed grep_server_time
 * @property mixed etag
 */
class World extends Model
{
  protected $table = 'World';
  protected $fillable = array('grep_id', 'uid', 'php_timezone', 'name', 'stopped', 'last_reset_time', 'grep_server_time', 'etag');

  /**
   * Returns the current server time
   * @return Carbon
   */
  public function getServerTime()
  {
    $ServerTime = Carbon::now($this->php_timezone);
    return $ServerTime;
  }

  /**
   * Returns UNIX offset in seconds
   * @return integer
   */
  public function getUnixOffset()
  {
    $ServerTimezone = new DateTimeZone($this->php_timezone);
    $Now = new \DateTime();
    return $ServerTimezone->getOffset($Now);
  }

  /**
   * Returns the UTC date time of the most recent world reset
   * @return string Date string with format: Y-m-d H:i:s
   */
  public function getLastUtcResetTime()
  {
    // Get server time at midnight
    $dt = $this->getServerTime();
    $dt->setTime(0,0);

    // Convert to UTC and return as string
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
  }
}

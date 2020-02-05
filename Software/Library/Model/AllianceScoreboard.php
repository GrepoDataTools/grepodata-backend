<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed date
 * @property mixed server_time
 * @property mixed att
 * @property mixed def
 * @property mixed con
 * @property mixed los
 */
class AllianceScoreboard extends Model
{
  protected $table = 'Alliance_scoreboard';
  protected $fillable = array('world', 'date', 'server_time', 'att', 'def', 'con', 'los');
}

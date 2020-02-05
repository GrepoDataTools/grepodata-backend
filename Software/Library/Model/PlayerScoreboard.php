<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed date
 * @property mixed server_time
 * @property mixed overview
 * @property mixed att
 * @property mixed def
 * @property mixed con
 * @property mixed los
 */
class PlayerScoreboard extends Model
{
  protected $table = 'Player_scoreboard';
  protected $fillable = array('world', 'date', 'server_time', 'overview', 'att', 'def', 'con', 'los');

}

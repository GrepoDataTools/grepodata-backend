<?php

namespace Grepodata\Library\Model;

use Carbon\Carbon;
use DateTimeZone;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed date
 * @property mixed domination_json
 */
class DominationScoreboard extends Model
{
  protected $table = 'Domination_scoreboard';
  protected $fillable = array('world', 'date');

}

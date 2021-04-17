<?php

namespace Grepodata\Library\Model;

use Carbon\Carbon;
use DateTimeZone;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed domination_json
 */
class WorldDomination extends Model
{
  protected $table = 'World_domination';
  protected $fillable = array('world');

}

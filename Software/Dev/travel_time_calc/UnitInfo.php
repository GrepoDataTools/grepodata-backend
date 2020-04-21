<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed units
 * @property mixed world
 * @property mixed source_town
 * @property mixed target_town
 * @property mixed game_speed
 * @property mixed unit_speed
 * @property mixed boosts
 */
class UnitInfo extends Model
{
  protected $table = 'Index_unit_info';
}

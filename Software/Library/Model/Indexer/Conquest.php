<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed index_key
 * @property mixed town_id
 * @property mixed town_name
 * @property mixed player_id
 * @property mixed player_name
 * @property mixed alliance_id
 * @property mixed alliance_name
 * @property mixed date
 * @property mixed victory
 * @property mixed fireships_total
 * @property mixed myths_total
 */
class Conquest extends Model
{
  protected $table = 'Index_conquest';
}

<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed reports
 * @property mixed town_count
 * @property mixed player_count
 * @property mixed alliance_count
 * @property mixed spy_count
 * @property mixed att_count
 * @property mixed def_count
 * @property mixed fire_count
 * @property mixed myth_count
 * @property mixed index_count
 */
class Stats extends Model
{
  protected $table = 'Index_stats';
}

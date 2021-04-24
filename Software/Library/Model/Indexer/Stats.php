<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed reports
 * @property mixed town_count
 * @property mixed user_count
 * @property mixed shared_count
 * @property mixed index_count
 * @property mixed users_today
 * @property mixed teams_today
 * @property mixed reports_today
 */
class Stats extends Model
{
  protected $table = 'Index_stats';
}

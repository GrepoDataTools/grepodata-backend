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
 * @property mixed users_week
 * @property mixed users_month
 * @property mixed teams_today
 * @property mixed teams_week
 * @property mixed teams_month
 * @property mixed reports_today
 * @property mixed commands_count
 * @property mixed commands_today
 * @property mixed commands_users_today
 * @property mixed commands_users_week
 * @property mixed commands_users_month
 * @property mixed commands_teams_today
 * @property mixed commands_teams_week
 * @property mixed commands_teams_month
 */
class Stats extends Model
{
  protected $table = 'Index_stats';
}

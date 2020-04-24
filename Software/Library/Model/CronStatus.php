<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed path
 * @property mixed running
 * @property mixed active
 * @property mixed last_run_started
 * @property mixed last_run_ended
 * @property mixed last_error_check
 */
class CronStatus extends Model
{
  protected $table = 'Cron_status';
  protected $fillable = array('path', 'running', 'active', 'last_run_started', 'last_run_ended', 'last_error_check');
}

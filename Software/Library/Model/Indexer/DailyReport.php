<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed type
 * @property mixed title
 * @property mixed data
 */
class DailyReport extends Model
{
  protected $table = 'Index_daily_report';
  protected $fillable = array('type', 'title', 'data');
}

<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed script
 * @property mixed start
 * @property mixed end
 * @property mixed pid
 */
class Operation_scriptlog extends Model
{
  protected $table = 'Operation_scriptlog';
  protected $fillable = array('script', 'start', 'end', 'pid');
}

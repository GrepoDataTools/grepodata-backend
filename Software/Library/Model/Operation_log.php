<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed message
 * @property mixed level
 * @property mixed pid
 * @property mixed microtime
 * @property mixed created_at
 */
class Operation_log extends Model
{
  protected $table = 'Operation_log';
  protected $fillable = array('message', 'level', 'pid', 'microtime');
}

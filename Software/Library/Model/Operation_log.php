<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

class Operation_log extends Model
{
  protected $table = 'Operation_log';
  protected $fillable = array('message', 'level', 'pid', 'microtime');
}

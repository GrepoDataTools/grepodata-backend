<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

class Message extends Model
{
  protected $table = 'Message';
  protected $fillable = array('name', 'mail', 'message');
}

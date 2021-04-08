<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed name
 * @property mixed mail
 * @property mixed message
 * @property mixed files
 */
class Message extends Model
{
  protected $table = 'Message';
  protected $fillable = array('name', 'mail', 'message');
}

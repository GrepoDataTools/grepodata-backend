<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed guild_id
 * @property mixed server
 * @property mixed index_key
 */
class Discord extends Model
{
  protected $table = 'Discord_guild';
  protected $fillable = array('guild_id');
}

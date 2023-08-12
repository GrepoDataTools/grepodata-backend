<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed token
 * @property mixed client
 * @property mixed user_id
 * @property mixed type
 */
class ScriptToken extends Model
{
  protected $table = 'Indexer_script_token';
  protected $fillable = array('token', 'client', 'user_id', 'type', 'payload');

}

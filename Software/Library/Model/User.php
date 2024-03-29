<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed username
 * @property mixed email
 * @property mixed is_confirmed
 * @property mixed is_linked
 * @property mixed is_deleted
 * @property mixed userscript_active
 * @property mixed passphrase
 * @property mixed token
 * @property mixed role
 * @property mixed last_activity
 */
class User extends Model
{
  protected $table = 'User';
  protected $fillable = array('username', 'email', 'is_confirmed', 'is_linked', 'passphrase', 'role');
}

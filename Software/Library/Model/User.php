<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed email
 * @property mixed is_confirmed
 * @property mixed passphrase
 * @property mixed token
 * @property mixed role
 */
class User extends Model
{
  protected $table = 'User';
  protected $fillable = array('email', 'is_confirmed', 'passphrase', 'role');
}

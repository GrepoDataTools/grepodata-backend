<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed key_code
 * @property string auth_token
 * @property string action
 * @property string data
 */
class Auth extends Model
{
  protected $table = 'Index_auth';
}

<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed user_id
 * @property mixed index_key
 * @property mixed role
 */
class Roles extends Model
{
  protected $table = 'Indexer_roles';
  protected $fillable = array('user_id', 'index_key', 'role');

  public function getPublicFields()
  {
    return array(
      'id'          => $this->id,
      'user_id'     => $this->user_id,
      'index_key'   => $this->index_key,
      'role'        => $this->role,
    );
  }

}

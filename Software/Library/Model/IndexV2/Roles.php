<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed user_id
 * @property mixed index_key
 * @property mixed contribute
 * @property mixed role
 * @property mixed uncommitted_reports How many reports are uncommitted to this index_key by the user_id?
 * @property mixed uncommitted_status Process status: unread, ignore, processing, processed
 */
class Roles extends Model
{
  protected $table = 'Indexer_roles';
  protected $fillable = array('user_id', 'index_key', 'role');

  public function getPublicFields()
  {
    return array(
      'user_id'     => $this->user_id,
      'index_key'   => $this->index_key,
      'contribute'  => $this->contribute ?? true,
      'role'        => $this->role,
      'uncommitted_reports' => $this->uncommitted_reports,
      'uncommitted_status'  => $this->uncommitted_status
    );
  }

}

<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed index_key
 * @property mixed world
 * @property mixed admin_only
 * @property mixed json
 * @property mixed local_time
 */
class Event extends Model
{
  protected $table = 'Indexer_event';

  public function getPublicFields()
  {
    return array(
      'id'          => $this->id,
      'index_key'   => $this->index_key,
      'world'       => $this->world,
      'admin_only'  => $this->admin_only,
      'json'        => json_decode($this->json, true),
      'local_time'  => $this->local_time,
    );
  }

}

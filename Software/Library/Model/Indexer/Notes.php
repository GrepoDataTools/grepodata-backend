<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed index_key
 * @property mixed town_id
 * @property mixed poster_name
 * @property mixed poster_id
 * @property mixed message
 */
class Notes extends Model
{
  protected $table = 'Index_notes';

  public function getPublicFields()
  {
    return array(
      'id'        => $this->id,
      'town_id'   => $this->town_id,
      'index_key' => $this->index_key,
      'poster_name' => $this->poster_name,
      'poster_id'  => $this->poster_id,
      'message'    => $this->message,
      'created_at' => $this->created_at,
    );
  }
}

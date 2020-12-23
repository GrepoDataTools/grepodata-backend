<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed index_key
 * @property mixed world
 * @property mixed town_id
 * @property mixed user_id
 * @property mixed poster_name
 * @property mixed poster_id
 * @property mixed note_id
 * @property mixed message
 */
class Notes extends Model
{
  protected $table = 'Indexer_notes';

  public function getPublicFields()
  {
    return array(
      'id'        => $this->id,
      'town_id'   => $this->town_id,
      'index_key' => $this->index_key,
      'world'     => $this->world,
      'poster_name' => $this->poster_name,
      'poster_id'  => $this->poster_id,
      'note_id'    => $this->note_id,
      'message'    => json_decode($this->message, true),
      'created_at' => $this->created_at,
    );
  }

}

<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed index_key
 * @property mixed alliance_id
 * @property mixed alliance_name
 * @property mixed hide_intel
 * @property mixed share
 */
class OwnersActual extends Model
{
  protected $table = 'Indexer_owners_actual';
  protected $fillable = array('index_key', 'alliance_id');

  public function getPublicFields()
  {
    return array(
      'index_key'     => $this->index_key,
      'alliance_id'   => $this->alliance_id,
      'alliance_name' => $this->alliance_name,
      'share'         => $this->share,
      'hide_intel'    => $this->hide_intel == 1 ? true : false,
    );
  }

}

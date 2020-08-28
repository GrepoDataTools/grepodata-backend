<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed user_id
 * @property mixed player_id
 * @property mixed player_name
 * @property mixed server
 * @property mixed confirmed
 * @property mixed town_token
 */
class Linked extends Model
{
  protected $table = 'Indexer_linked';
  protected $fillable = array('user_id', 'player_id', 'player_name', 'server', 'confirmed', 'town_token');

  public function getPublicFields()
  {
    return array(
      'user_id'     => $this->user_id,
      'player_id'   => $this->player_id,
      'player_name' => $this->player_name,
      'server'      => $this->server,
      'confirmed'   => $this->confirmed==1?true:false,
      'town_token'  => $this->town_token,
    );
  }

}

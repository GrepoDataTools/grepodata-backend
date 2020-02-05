<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed grep_id
 * @property mixed date
 * @property mixed world
 * @property mixed alliance_id
 * @property mixed alliance_name
 * @property mixed points
 * @property mixed rank
 * @property mixed att
 * @property mixed def
 * @property mixed towns
 */
class PlayerHistory extends Model
{
  protected $table = 'Player_history';
  protected $fillable = array('world', 'grep_id', 'date', 'alliance_id', 'alliance_name', 'points', 'rank', 'att', 'def', 'towns');

  public function getPublicFields()
  {
    return array(
      'date'          => $this->date,
      'world'         => $this->world,
      'alliance_id'   => $this->alliance_id,
      'alliance_name' => $this->alliance_name,
      'points'        => $this->points,
      'rank'          => $this->rank,
      'att'           => $this->att,
      'def'           => $this->def,
      'towns'         => $this->towns,
    );
  }
}

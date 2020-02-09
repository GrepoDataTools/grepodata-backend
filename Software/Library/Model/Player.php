<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed alliance_id
 * @property mixed grep_id
 * @property mixed world
 * @property mixed name
 * @property mixed points
 * @property mixed rank
 * @property mixed rank_max
 * @property mixed rank_date
 * @property mixed towns
 * @property mixed towns_max
 * @property mixed towns_date
 * @property mixed att
 * @property mixed def
 * @property mixed att_old
 * @property mixed def_old
 * @property mixed att_rank
 * @property mixed def_rank
 * @property mixed fight_rank
 * @property mixed att_rank_max
 * @property mixed def_rank_max
 * @property mixed fight_rank_max
 * @property mixed att_rank_date
 * @property mixed def_rank_date
 * @property mixed fight_rank_date
 * @property mixed heatmap
 * @property mixed active
 * @property mixed data_update
 */
class Player extends Model
{
  protected $table = 'Player';
  protected $fillable = array('grep_id', 'world', 'name', 'alliance_id', 'points', 'rank', 'towns', 'att', 'def', 'att_old', 'def_old');

  public function getPublicFields()
  {
    return array(
      'grep_id'     => $this->grep_id,
      'world'       => $this->world,
      'name'        => $this->name,
      'alliance_id' => $this->alliance_id,
      'points'      => $this->points,
      'rank'        => $this->rank,
      'towns'       => $this->towns,
      'att'         => $this->att,
      'def'         => $this->def,
      'att_old'     => $this->att_old,
      'def_old'     => $this->def_old,
      'att_rank'    => $this->att_rank,
      'def_rank'    => $this->def_rank,
      'fight_rank'  => $this->fight_rank,
      'att_rank_max'  => $this->att_rank_max,
      'def_rank_max'  => $this->def_rank_max,
      'fight_rank_max'  => $this->fight_rank_max,
      'att_rank_date'   => $this->att_rank_date,
      'def_rank_date'   => $this->def_rank_date,
      'fight_rank_date' => $this->fight_rank_date,
      'heatmap' => (is_null($this->heatmap)? array() : json_decode($this->heatmap, true)),
    );
  }
}

<?php

namespace Grepodata\Library\Model;

use Carbon\Carbon;
use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed grep_id
 * @property mixed name
 * @property mixed points
 * @property mixed player_id
 * @property mixed island_x
 * @property mixed island_y
 */
class TownGhost extends Model
{
  protected $table = 'Town_ghost';
  protected $fillable = array('grep_id', 'world', 'name', 'points', 'island_x', 'island_y', 'player_id');

  public function getPublicFields()
  {
    return array(
      'world'     => $this->world,
      'grep_id'   => $this->grep_id,
      'name'      => $this->name,
      'points'    => $this->points,
      'player_id' => $this->player_id,
      'ix'        => $this->island_x,
      'iy'        => $this->island_y,
      'ocean'     => (int) (floor($this->island_x/100) . floor($this->island_y/100)),
    );
  }
}

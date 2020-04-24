<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed grep_id
 * @property mixed name
 * @property mixed points
 * @property mixed player_id
 * @property mixed island_x
 * @property mixed island_y
 * @property mixed island_i
 */
class Town extends Model
{
  protected $table = 'Town';
  protected $fillable = array('grep_id', 'world', 'name', 'points', 'player_id');

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

  public function getMinimalFields()
  {
    return array(
      'grep_id' => $this->grep_id,
      'name'    => $this->name,
      'points'  => $this->points,
      'ix'      => $this->island_x,
      'iy'      => $this->island_y,
      'ocean'   => (int) (floor($this->island_x/100) . floor($this->island_y/100)),
    );
  }
}

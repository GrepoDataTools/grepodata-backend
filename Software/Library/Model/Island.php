<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed grep_id
 * @property mixed island_x
 * @property mixed island_y
 */
class Island extends Model
{
  protected $table = 'Island';
  protected $fillable = array('world', 'grep_id', 'island_x', 'island_y');

  public function getPublicFields()
  {
    return array(
      'world'       => $this->world,
      'grep_id'     => $this->grep_id,
      'island_x'    => $this->island_x,
      'island_y'    => $this->island_y,
    );
  }

  public function getMinimalFields()
  {
    return array(
      'world'       => $this->world,
      'grep_id'     => $this->grep_id,
      'island_x'    => $this->island_x,
      'island_y'    => $this->island_y,
    );
  }
}

<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed grep_id
 * @property mixed world
 * @property mixed def
 * @property mixed att
 * @property mixed towns
 * @property mixed rank
 * @property mixed points
 * @property mixed members
 * @property mixed name
 * @property mixed updated_at
 */
class Alliance extends Model
{
  protected $table = 'Alliance';
  protected $fillable = array('grep_id', 'world', 'name', 'points', 'rank', 'towns', 'members', 'att', 'def');

  public function getPublicFields()
  {
    return array(
      'grep_id'     => $this->grep_id,
      'world'       => $this->world,
      'name'        => $this->name,
      'members'     => $this->members,
      'points'      => $this->points,
      'rank'        => $this->rank,
      'towns'       => $this->towns,
      'att'         => $this->att,
      'def'         => $this->def,
    );
  }
}

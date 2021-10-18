<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed date
 * @property mixed points
 * @property mixed rank
 * @property mixed att
 * @property mixed def
 * @property mixed towns
 * @property mixed members
 * @property mixed domination_percentage
 */
class AllianceHistory extends Model
{
  protected $table = 'Alliance_history';
  protected $fillable = array('world', 'grep_id', 'date', 'points', 'rank', 'att', 'def', 'towns', 'members');

  public function getPublicFields()
  {
    return array(
      'date'          => $this->date,
      'points'        => $this->points,
      'rank'          => $this->rank,
      'att'           => $this->att,
      'def'           => $this->def,
      'towns'         => $this->towns,
      'members'       => $this->members,
      'domination_percentage' => $this->domination_percentage,
    );
  }
}

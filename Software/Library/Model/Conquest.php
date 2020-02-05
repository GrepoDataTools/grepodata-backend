<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed world
 * @property mixed town_id
 * @property mixed time
 * @property mixed n_p_id
 * @property mixed o_p_id
 * @property mixed n_a_id
 * @property mixed o_a_id
 * @property mixed points
 */
class Conquest extends Model
{
  protected $table = 'Conquest';
  protected $fillable = array('town_id', 'world', 'time', 'n_p_id', 'o_p_id', 'n_a_id', 'o_a_id', 'points');

  public function getPublicFields()
  {
    return array(
      'town_id'       => $this->town_id,
      'time'          => $this->time,
      'n_p_id'        => $this->n_p_id,
      'o_p_id'        => $this->o_p_id,
      'n_a_id'        => $this->n_a_id,
      'o_a_id'        => $this->o_a_id,
      'points'        => $this->points,
    );
  }
}

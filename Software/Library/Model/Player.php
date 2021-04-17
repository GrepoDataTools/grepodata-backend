<?php

namespace Grepodata\Library\Model;

use Carbon\Carbon;
use Exception;
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
 * @property mixed att_point_date
 * @property mixed town_point_date
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
    $Now = Carbon::now()->format('Y-m-d H:i:s');
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
      'rank_max'    => min($this->rank_max, $this->rank),
      'towns_max'   => max($this->towns_max, $this->towns),
      'att_rank_max'  => min($this->att_rank_max, $this->att_rank),
      'def_rank_max'  => min($this->def_rank_max, $this->def_rank),
      'fight_rank_max'  => min($this->fight_rank_max, $this->fight_rank),
      'rank_date'       => $this->rank_max < $this->rank ? $this->rank_date : $Now,
      'towns_date'      => $this->towns_max > $this->towns ? $this->towns_date : $Now,
      'att_rank_date'   => $this->att_rank_max < $this->att_rank ? $this->att_rank_date : $Now,
      'def_rank_date'   => $this->def_rank_max < $this->def_rank ? $this->def_rank_date : $Now,
      'fight_rank_date' => $this->fight_rank_max < $this->fight_rank ? $this->fight_rank_date : $Now,
      'att_point_date' => $this->att_point_date ?? null,
      'town_point_date' => $this->town_point_date ?? null,
      'hours_inactive' => $this->getHoursInactive() ?? null,
      'heatmap' => array(), // deprecated
    );
  }

  public function getHoursInactive()
  {
    // Hours inactive
    $HoursInactive = null;
    try {
      if (!is_null($this->att_point_date) && !is_null($this->town_point_date)) {
        $LastActivity = $this->att_point_date;
        if ($this->town_point_date > $LastActivity) {
          $LastActivity = $this->town_point_date;
        }
        $Now = Carbon::now();
        $HoursInactive = $Now->diffInHours($LastActivity);
      }
    } catch (Exception $e) {}
    return $HoursInactive;
  }

}

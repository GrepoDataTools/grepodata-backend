<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

class AllianceChanges extends Model
{
  protected $table = 'Alliance_changes';
  protected $fillable = array('world', 'player_grep_id', 'player_points', 'player_rank', 'player_name');

  public function getPublicFields()
  {
    return array(
      'date'            => $this->created_at,
      'player_grep_id'  => $this->player_grep_id,
      'player_name'     => $this->player_name,
      'player_rank'     => $this->player_rank,
      'player_points'   => $this->player_points,
      'old_alliance_grep_id' => (isset($this->old_alliance_grep_id) ? $this->old_alliance_grep_id : 0),
      'old_alliance_name'    => (isset($this->old_alliance_name)    ? $this->old_alliance_name    : ''),
      'old_alliance_rank'    => (isset($this->old_alliance_rank)    ? $this->old_alliance_rank    : 0),
      'old_alliance_points'  => (isset($this->old_alliance_points)  ? $this->old_alliance_points  : 0),
      'new_alliance_grep_id' => (isset($this->new_alliance_grep_id) ? $this->new_alliance_grep_id : 0),
      'new_alliance_name'    => (isset($this->new_alliance_name)    ? $this->new_alliance_name    : ''),
      'new_alliance_rank'    => (isset($this->new_alliance_rank)    ? $this->new_alliance_rank    : 0),
      'new_alliance_points'  => (isset($this->new_alliance_points)  ? $this->new_alliance_points  : 0),
    );
  }
}

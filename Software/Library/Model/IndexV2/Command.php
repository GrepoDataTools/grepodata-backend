<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed world
 * @property mixed command_id
 * @property mixed source_town_id
 * @property mixed source_town_name
 * @property mixed source_player_id
 * @property mixed source_player_name
 * @property mixed source_alliance_id
 * @property mixed source_alliance_name
 * @property mixed target_town_id
 * @property mixed target_town_name
 * @property mixed target_player_id
 * @property mixed target_player_name
 * @property mixed target_alliance_id
 * @property mixed target_alliance_name
 * @property mixed is_returning
 * @property mixed attacking_strategy
 * @property mixed units
 * @property mixed arrival_at
 * @property mixed started_at
 */
class Command extends Model
{
  protected $table = 'Indexer_command';

  public function getPublicFields()
  {
    return array(
      'id' => $this->id,
      'world' => $this->world,
      'command_id' => $this->command_id,
      'source_town_id' => $this->source_town_id,
      'source_town_name' => $this->source_town_name,
      'source_player_id' => $this->source_player_id,
      'source_player_name' => $this->source_player_name,
      'source_alliance_id' => $this->source_alliance_id,
      'source_alliance_name' => $this->source_alliance_name,
      'target_town_id' => $this->target_town_id,
      'target_town_name' => $this->target_town_name,
      'target_player_id' => $this->target_player_id,
      'target_player_name' => $this->target_player_name,
      'target_alliance_id' => $this->target_alliance_id,
      'target_alliance_name' => $this->target_alliance_name,
      'is_returning' => $this->is_returning,
      'attacking_strategy' => $this->attacking_strategy,
      'units' => $this->units,
      'arrival_at' => $this->arrival_at,
      'started_at' => $this->started_at
    );
  }

}






















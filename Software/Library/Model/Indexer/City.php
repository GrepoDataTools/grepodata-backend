<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed town_id
 * @property mixed index_key
 * @property mixed town_name
 * @property mixed player_id
 * @property mixed player_name
 * @property mixed alliance_id
 * @property mixed parsed_date
 * @property mixed report_date
 * @property mixed report_type
 * @property mixed hero
 * @property mixed god
 * @property mixed silver
 * @property mixed fireships
 * @property mixed buildings
 * @property mixed land_units
 * @property mixed sea_units
 * @property mixed mythical_units
 * @property mixed poster_player_id
 * @property mixed poster_alliance_id
 * @property mixed type
 * @property mixed conquest_id
 * @property mixed soft_deleted
 */
class City extends Model
{
  protected $table = 'Index_city';

  public function getPublicFields()
  {
    return array(
      'id'          => $this->id,
      'town_id'     => $this->town_id,
      'town_name'   => $this->town_name,
      'player_id'   => $this->player_id,
      'alliance_id' => $this->alliance_id,
      'date'        => $this->report_date,
      'parsed_date' => $this->parsed_date,
      'type'        => $this->report_type,
      'hero'        => $this->hero,
      'god'         => $this->god,
      'silver'      => $this->silver,
      'fireships'   => $this->fireships,
      'buildings'   => json_decode($this->buildings, true),
      'land'        => json_decode($this->land_units, true),
      'sea'         => json_decode($this->sea_units, true),
      'air'         => json_decode($this->mythical_units, true),
      'deleted'     => ($this->soft_deleted!=null?true:false)
    );
  }

  public function getMinimalFields()
  {
    return array(
      'id'          => $this->id,
      'date'        => $this->report_date,
      'type'        => $this->report_type,
      'hero'        => $this->hero,
      'god'         => $this->god,
      'silver'      => $this->silver,
      'fireships'   => $this->fireships,
      'parsed_date' => $this->parsed_date,
      'buildings'   => json_decode($this->buildings, true),
      'land'        => json_decode($this->land_units, true),
      'sea'         => json_decode($this->sea_units, true),
      'air'         => json_decode($this->mythical_units, true),
      'deleted'     => ($this->soft_deleted!=null?true:false)
    );
  }
}

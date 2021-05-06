<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed indexed_by_user_id
 * @property mixed hash
 * @property mixed luck
 * @property mixed v1_index
 * @property mixed world
 * @property mixed source_type
 * @property mixed report_type
 * @property mixed script_version
 * @property mixed town_id
 * @property mixed town_name
 * @property mixed player_id
 * @property mixed player_name
 * @property mixed alliance_id
 * @property mixed poster_player_name
 * @property mixed poster_player_id
 * @property mixed poster_alliance_id
 * @property mixed conquest_id
 * @property mixed conquest_details
 * @property mixed report_date
 * @property mixed parsed_date
 * @property mixed hero
 * @property mixed god
 * @property mixed silver
 * @property mixed buildings
 * @property mixed land_units
 * @property mixed sea_units
 * @property mixed fireships
 * @property mixed mythical_units
 * @property mixed is_previous_owner_intel
 * @property mixed created_at
 * @property mixed updated_at
 * @property mixed soft_deleted
 * @property mixed report_json
 * @property mixed report_info
 * @property mixed parsing_failed
 * @property mixed debug_explain
 */
class Intel extends Model
{
  protected $table = 'Indexer_intel';

  public function getPublicFields()
  {
    return array(
      'id'          => $this->id,
      'hash'        => $this->hash,
      'world'       => $this->world,
      'source_type' => $this->source_type,
      'town_id'     => $this->town_id,
      'town_name'   => $this->town_name,
      'player_id'   => $this->player_id,
      'player_name' => $this->player_name,
      'alliance_id' => $this->alliance_id,
      'conquest_id' => $this->conquest_id,
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
      'deleted'     => $this->soft_deleted!=null,
      'parsed'      => $this->parsing_failed==0,
      'is_previous_owner_intel' => $this->is_previous_owner_intel==1
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
      'deleted'     => $this->soft_deleted!=null,
      'is_previous_owner_intel' => $this->is_previous_owner_intel==1
    );
  }

  /**
   * Returns a list of column names to be selected in a SQL query
   * @param bool $bPrefix
   * @return string[]
   */
  public static function getMinimalSelect($bPrefix = true): array
  {
    $Prefix = '';
    if ($bPrefix) {
      $Prefix = 'Indexer_intel.';
    }

    return array(
      $Prefix.'id',
      $Prefix.'indexed_by_user_id',
      $Prefix.'hash',
      $Prefix.'luck',
      $Prefix.'v1_index',
      $Prefix.'world',
      $Prefix.'source_type',
      $Prefix.'report_type',
      //$Prefix.'script_version',
      $Prefix.'town_id',
      $Prefix.'town_name',
      $Prefix.'player_id',
      $Prefix.'player_name',
      $Prefix.'alliance_id',
      $Prefix.'poster_player_name',
      $Prefix.'poster_player_id',
      $Prefix.'poster_alliance_id',
      $Prefix.'conquest_id',
      $Prefix.'conquest_details',
      $Prefix.'report_date',
      $Prefix.'parsed_date',
      $Prefix.'hero',
      $Prefix.'god',
      $Prefix.'silver',
      $Prefix.'buildings',
      $Prefix.'land_units',
      $Prefix.'sea_units',
      $Prefix.'fireships',
      $Prefix.'mythical_units',
      $Prefix.'is_previous_owner_intel',
      $Prefix.'created_at',
      $Prefix.'updated_at',
      $Prefix.'soft_deleted',
      //$Prefix.'report_json',
      //$Prefix.'report_info',
      $Prefix.'parsing_failed',
      //$Prefix.'debug_explain',
    );
  }

}

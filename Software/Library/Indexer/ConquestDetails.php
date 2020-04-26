<?php

namespace Grepodata\Library\Indexer;


class ConquestDetails
{
  public $siegeTownId = 0;
  public $siegeTownName = '';
  public $siegePlayerId = 0;
  public $siegePlayerName = '';
  public $wall = null;
  public $siegeUnits = array();

  public function jsonSerialize() {
    return array(
      'siege_town_id' => $this->siegeTownId,
      'siege_town_name' => $this->siegeTownName,
      'siege_player_id' => $this->siegePlayerId,
      'siege_player_name' => $this->siegePlayerName,
      'siege_units' => $this->siegeUnits,
      'wall' => $this->wall,
    );
  }
}
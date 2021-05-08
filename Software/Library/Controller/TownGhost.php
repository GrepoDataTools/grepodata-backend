<?php

namespace Grepodata\Library\Controller;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TownGhost
{


  /**
   * @param $World
   * @param $Id
   * @return bool|Collection|\Grepodata\Library\Model\TownGhost[]
   */
  public static function allByPlayer($Id, $World)
  {
    return \Grepodata\Library\Model\TownGhost::where('world', '=', $World, 'and')
      ->where('player_id','=', $Id)
      ->orderBy('name', 'asc')
      ->get();
  }

}

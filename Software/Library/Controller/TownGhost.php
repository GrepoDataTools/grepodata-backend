<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;
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


  /**
   * @param $World
   * @param $MaxDate
   * @return bool|Collection|\Grepodata\Library\Model\TownGhost[]
   */
  public static function allRecentByWorld($World, $MaxDate)
  {
    return \Grepodata\Library\Model\TownGhost::selectRaw('Town_ghost.player_id, Player.name as player_name, min(Town_ghost.created_at) as ghost_time, count(*) as num_towns')
      ->leftJoin('Player', function($query)
      {
        $query->on('Player.grep_id', '=', 'Town_ghost.player_id')
          ->on('Player.world', '=', 'Town_ghost.world');
      })
      ->where('Town_ghost.world', '=', $World)
      ->where('Town_ghost.created_at','<=', $MaxDate)
      ->where('Town_ghost.created_at','>', $MaxDate->copy()->subHours(24))
      ->groupBy('Town_ghost.player_id', 'Player.name')
      ->get();
//    SELECT ghost.player_id, COUNT(*)
//    FROM `Town_ghost` as ghost
//    LEFT JOIN Player ON Player.grep_id = ghost.player_id AND Player.world = ghost.world
//    WHERE ghost.world = 'fr136'
//      AND ghost.created_at > NOW() - INTERVAL 30 hour
//    GROUP BY ghost.player_id
//    ORDER BY COUNT(*) DESC
  }

}

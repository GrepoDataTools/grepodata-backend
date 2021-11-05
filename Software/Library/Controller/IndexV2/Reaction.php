<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Capsule\Manager as DB;

class Reaction
{

  /**
   * Returns all reactions for the given thread id that the user has access to via their team
   * @param User $oUser
   * @param $ThreadId
   * @return Collection|\Grepodata\Library\Model\IndexV2\Reaction[]
   */
  public static function allByThread(User $oUser, $World, $ThreadId)
  {
//    DB::enableQueryLog();
    $oQuery = \Grepodata\Library\Model\IndexV2\Reaction::select([
      'Indexer_reaction.*',
      'Player.name'
    ])
      ->leftJoin('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_reaction.index_key')
      ->leftJoin('Player', function($join)
      {
        $join->on('Player.grep_id', '=', 'Indexer_reaction.player_id')
          ->on('Player.world', '=', 'Indexer_reaction.world');
      });

    $aResult = $oQuery
      ->where('Indexer_reaction.world', '=', $World)
      ->where('Indexer_reaction.thread_id', '=', $ThreadId)
      ->where('Indexer_roles.user_id', '=', $oUser->id)
      ->orderBy('id', 'asc')
      ->get();
//    $test = DB::getQueryLog();
    return $aResult;

//  select `Indexer_reaction`.*, `Player`.`name`
//  from `Indexer_reaction`
//  left join `Indexer_roles` on `Indexer_roles`.`index_key` = `Indexer_reaction`.`index_key`
//  left join `Player` on `Player`.`grep_id` = `Indexer_reaction`.`player_id` and `Player`.`world` = `Indexer_reaction`.`world`
//  where `Indexer_reaction`.`world` = 'nl92' and `Indexer_reaction`.`thread_id` = 3024 and `Indexer_roles`.`user_id` = 1
//  order by `id` asc
  }

  public static function allByPost(User $oUser, $World, $PostId)
  {
    $oQuery = \Grepodata\Library\Model\IndexV2\Reaction::select([
      'Indexer_reaction.*',
      'Player.name'
    ])
      ->leftJoin('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_reaction.index_key')
      ->leftJoin('Player', function($join)
      {
        $join->on('Player.grep_id', '=', 'Indexer_reaction.player_id')
          ->on('Player.world', '=', 'Indexer_reaction.world');
      });

    $aResult = $oQuery
      ->where('Indexer_reaction.world', '=', $World)
      ->where('Indexer_reaction.post_id', '=', $PostId)
      ->where('Indexer_roles.user_id', '=', $oUser->id)
      ->orderBy('id', 'asc')
      ->get();
    return $aResult;
  }

}
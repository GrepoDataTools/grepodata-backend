<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class IntelShared
{

  /**
   * Returns all hashes for the given player_id on this world
   * @param $PlayerId
   * @param $World
   * @param int $Limit
   * @return Collection
   */
  public static function getHashlistForPlayer($PlayerId, $World, $Limit = 300)
  {
    return \Grepodata\Library\Model\IndexV2\IntelShared::where('world', '=', $World, 'and')
      ->where('player_id', '=', $PlayerId)
      ->orderBy('id', 'desc')
      ->limit($Limit)
      ->get();
  }

  /**
   * Returns a specific record if it exists for this hash
   * @param $PlayerId
   * @param $World
   * @param $Hash
   * @return \Grepodata\Library\Model\IndexV2\Intel
   */
  public static function getByHashByPlayer($PlayerId, $World, $Hash)
  {
    return \Grepodata\Library\Model\IndexV2\IntelShared::where('world', '=', $World, 'and')
      ->where('player_id', '=', $PlayerId, 'and')
      ->where('report_hash', '=', $Hash)
      ->first();
  }
}
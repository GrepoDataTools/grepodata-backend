<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\Player;
use Grepodata\Library\Model\User;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class Linked
{

  /**
   * Get all linked accounts for a user
   * @param User $oUser
   * @return \Grepodata\Library\Model\IndexV2\Linked[]
   */
  public static function getAllByUser(User $oUser) {
    return \Grepodata\Library\Model\IndexV2\Linked::where('user_id', '=', $oUser->id)
      ->orderBy('id', 'desc')
      ->get();
  }

  /**
   * Get linked account by player id and server
   * @param User $oUser
   * @param $PlayerId
   * @param $Server
   * @return \Grepodata\Library\Model\IndexV2\Linked
   */
  public static function getByPlayerIdAndServer(User $oUser, $PlayerId, $Server) {
    return \Grepodata\Library\Model\IndexV2\Linked::where('user_id', '=', $oUser->id, 'and')
      ->where('player_id', '=', $PlayerId, 'and')
      ->where('server', '=', $Server)
      ->firstOrFail();
  }

  /**
   * Get all unconfirmed linked accounts
   * @return \Grepodata\Library\Model\IndexV2\Linked[]
   */
  public static function getAllUnconfirmed() {
    return \Grepodata\Library\Model\IndexV2\Linked::where('confirmed', '=', false)
      ->get();
  }

  /**
   * Add a new link request between user and player
   * @param User $oUser
   * @param $PlayerId
   * @param $PlayerName
   * @param $Server
   * @return \Grepodata\Library\Model\IndexV2\Linked
   */
  public static function newLinkedAccount(User $oUser, $PlayerId, $PlayerName, $Server) {
    $oLinked = new \Grepodata\Library\Model\IndexV2\Linked();
    $oLinked->user_id = $oUser->id;
    $oLinked->player_id = $PlayerId;
    $oLinked->player_name = $PlayerName;
    $oLinked->server = $Server;
    $oLinked->confirmed = false;
    $oLinked->town_token = IndexBuilderV2::generateIndexKey(20);
    $oLinked->save();
    return $oLinked;
  }

  /**
   * Confirm the account link
   * @param \Grepodata\Library\Model\IndexV2\Linked $oLinked
   */
  public static function setConfirmed(\Grepodata\Library\Model\IndexV2\Linked $oLinked)
  {
    $oLinked->confirmed = true;
    $oLinked->save();

    try {
      $oUser = \Grepodata\Library\Controller\User::GetUserById($oLinked->user_id);
      $oUser->is_linked = true;
      $oUser->save();
    } catch (Exception $e) {
      Logger::warning("Unable to find user for link request ".$oLinked->user_id);
    }
  }

  /**
   * Unlink the specified account link
   * @param \Grepodata\Library\Model\IndexV2\Linked $oLinked
   * @return bool|null
   * @throws Exception
   */
  public static function unlink(\Grepodata\Library\Model\IndexV2\Linked $oLinked) {
    return $oLinked->delete();
  }


}
<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;
use Grepodata\Library\Redis\RedisHelper;
use Illuminate\Database\Eloquent\Collection;

class Notification
{

  /**
   * @param IndexInfo $oIndex
   * @param int $From
   * @param int $Size
   * @return \Grepodata\Library\Model\IndexV2\Notification[]
   */
  public static function allByTeam(IndexInfo $oIndex, int $From = 0, int $Size = 20)
  {
    return \Grepodata\Library\Model\IndexV2\Notification::where('team', '=', $oIndex->key_code)
      ->orderBy('id', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * @param User $oUser
   * @param int $From
   * @param int $Size
   * @return Collection|\Grepodata\Library\Model\IndexV2\Notification[]
   */
  public static function allByUser(User $oUser, int $From = 0, int $Size = 20)
  {
    return \Grepodata\Library\Model\IndexV2\Notification::select(['Indexer_notification.*'])
      ->leftJoin('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_notification.team')
      ->where('Indexer_roles.user_id', '=', $oUser->id)
      ->orWhere('Indexer_notification.user_id', '=', $oUser->id)
      ->orderBy('Indexer_notification.id', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * Save notification to DB and push msg over backbone to team
   * @param string $World
   * @param string $Team
   * @param string $Message
   */
  public static function notifyTeam(string $World, string $Team, string $Message)
  {
    // Save notification to SQL
    $oNotification = new \Grepodata\Library\Model\IndexV2\Notification();
    $oNotification->world = $World;
    $oNotification->team = $Team;
    $oNotification->message = $Message;
    $oNotification->type = 'notify_team';
    $oNotification->action = 'ops_event';
    $oNotification->save();

    // Push notification over Redis backbone (All current websocket listeners will be notified)
    $aBackboneMessage = array(
      'id'      => $oNotification->id,
      'type'    => $oNotification->type,
      'team'    => $oNotification->team,
      'msg'     => $oNotification->message,
      'world'   => $oNotification->world,
      'action'  => $oNotification->action
    );
    RedisHelper::SendBackboneMessage($aBackboneMessage);
  }

  /**
   * Save notification to DB and push msg over backbone to user
   * @param string $World
   * @param int $UserId
   * @param string $Message
   */
  public static function notifyUser(string $World, int $UserId, string $Message)
  {
    // Save notification to SQL
    $oNotification = new \Grepodata\Library\Model\IndexV2\Notification();
    $oNotification->world = $World;
    $oNotification->user_id = $UserId;
    $oNotification->message = $Message;
    $oNotification->type = 'notify_user';
    $oNotification->action = 'ops_event';
    $oNotification->save();

    // Push notification over Redis backbone (All current websocket listeners will be notified)
    $aBackboneMessage = array(
      'id'      => $oNotification->id,
      'type'    => $oNotification->type,
      'user_id' => $oNotification->user_id,
      'msg'     => $oNotification->message,
      'world'   => $oNotification->world,
      'action'  => $oNotification->action
    );
    RedisHelper::SendBackboneMessage($aBackboneMessage);
  }

}

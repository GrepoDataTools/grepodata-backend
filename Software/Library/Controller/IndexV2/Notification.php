<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;
use Grepodata\Library\Model\World;
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

  public static function notifyCommandsUploaded(World $oWorld, IndexInfo $oTeam, string $PlayerName, int $PlayerId, int $NumCommands, bool $bIsPlanned)
  {
    $postfix = $bIsPlanned ? ' planned commands' : ' live commands';
    $aPayload = array(
      self::notificationPart('player', $PlayerName, array('id'=>$PlayerId)),
      self::notificationPart('text', " shared " . $NumCommands . $postfix),
    );
    $Message = json_encode($aPayload);
    self::notifyTeam($oWorld, $oTeam, $Message);
  }

  /**
   * Save notification to DB and push msg over backbone to team
   * @param World $oWorld
   * @param IndexInfo $oTeam
   * @param string $Message
   */
  private static function notifyTeam(World $oWorld, IndexInfo $oTeam, string $Message)
  {
    // Save notification to SQL
    $oNotification = new \Grepodata\Library\Model\IndexV2\Notification();
    $oNotification->world = $oWorld->grep_id;
    $oNotification->team = $oTeam->key_code;
    $oNotification->team_name = $oTeam->index_name;
    $oNotification->message = $Message;
    $oNotification->type = 'notify_team';
    $oNotification->action = 'ops_event';
    $oNotification->server_time = $oWorld->getServerTime()->format('d M y H:i');
    $oNotification->save();

    // Push notification over Redis backbone (All current websocket listeners will be notified)
    RedisHelper::SendBackboneMessage($oNotification->getPublicFields());
  }

  /**
   * Save notification to DB and push msg over backbone to user
   * @param World $oWorld
   * @param User $oUser
   * @param string $Message
   */
  private static function notifyUser(World $oWorld, User $oUser, string $Message)
  {
    // Save notification to SQL
    $oNotification = new \Grepodata\Library\Model\IndexV2\Notification();
    $oNotification->world = $oWorld->grep_id;
    $oNotification->user_id = $oUser->id;
    $oNotification->message = $Message;
    $oNotification->type = 'notify_user';
    $oNotification->action = 'ops_event';
    $oNotification->server_time = $oWorld->getServerTime()->format('d M y H:i');
    $oNotification->save();

    // Push notification over Redis backbone (All current websocket listeners will be notified)
    RedisHelper::SendBackboneMessage($oNotification->getPublicFields());
  }

  private static function notificationPart($Type = 'text', $Text = '', $Params = array())
  {
    return array(
      'type' => $Type,
      'text' => $Text,
      'params' => $Params,
    );
  }

}

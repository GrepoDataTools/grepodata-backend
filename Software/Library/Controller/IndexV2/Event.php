<?php

namespace Grepodata\Library\Controller\IndexV2;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\User;

class Event
{

//  /**
//   * Get all events for a user
//   * @param User $oUser
//   * @return \Grepodata\Library\Model\IndexV2\Event[]
//   */
//  public static function getAllByUser(User $oUser)
//  {
//    return \Grepodata\Library\Model\IndexV2\Event::select(['*'])
//      ->leftJoin('Indexer_roles', 'Indexer_roles.index_key', '=', 'Indexer_event.index_key')
//      ->where('Indexer_roles.user_id', '=', $oUser->id)
//      ->where(function ($query) {
//        $query->where('Indexer_event.admin_only', '=', 0)
//          ->orWhere('Indexer_roles.role', '=', 'owner')
//          ->orWhere('Indexer_roles.role', '=', 'admin');
//      })
//      ->orderBy('id', 'desc')
//      ->get();
//  }

  /**
   * Get all team events
   * @param $IndexKey
   * @param bool $bUserIsAdmin
   * @param int $From
   * @param int $Size
   * @return \Grepodata\Library\Model\IndexV2\Event[]
   */
  public static function getAllByTeam($IndexKey, $bUserIsAdmin = false, $From = 0, $Size = 20)
  {
    return \Grepodata\Library\Model\IndexV2\Event::where('index_key', '=', $IndexKey)
      ->where('admin_only', '<=', $bUserIsAdmin?1:0)
      ->orderBy('id', 'desc')
      ->offset($From)
      ->limit($Size)
      ->get();
  }

  /**
   * Count the number of events for this team
   * @param $IndexKey
   * @param bool $bUserIsAdmin
   * @return \Grepodata\Library\Model\IndexV2\Event[]
   */
  public static function countAllByTeam($IndexKey, $bUserIsAdmin = false)
  {
    return \Grepodata\Library\Model\IndexV2\Event::where('index_key', '=', $IndexKey)
      ->where('admin_only', '<=', $bUserIsAdmin?1:0)
      ->count();
  }

  /**
   * Add index join event
   * @param IndexInfo $oIndex
   * @param User $JoiningUser
   * @param $JoinType
   * @param User|null $EdittingUser
   */
  public static function addIndexJoinEvent(IndexInfo $oIndex, User $JoiningUser, $JoinType, User $EdittingUser = null)
  {
    try {
      $oWorld = World::getWorldById($oIndex->world);
      $oEvent = new \Grepodata\Library\Model\IndexV2\Event();
      $oEvent->world = $oIndex->world;
      $oEvent->local_time = $oWorld->getServerTime();
      $oEvent->admin_only = false;
      $oEvent->index_key = $oIndex->key_code;
      switch ($JoinType) {
        case 'imported_team_as_owner':
          $aEvent = array(
            self::eventPart('text', 'User '),
            self::eventPart('user', $JoiningUser->username),
            self::eventPart('text', ' was verified as owner of team '),
            self::eventPart('team', $oIndex->index_name, array('key' => $oIndex->key_code)),
          );
          break;
        case 'created_team':
          $aEvent = array(
            self::eventPart('text', 'User '),
            self::eventPart('user', $JoiningUser->username),
            self::eventPart('text', ' created team '),
            self::eventPart('team', $oIndex->index_name, array('key' => $oIndex->key_code)),
          );
          break;
        case 'v1_redirect':
          $aEvent = array(
            self::eventPart('text', 'User '),
            self::eventPart('user', $JoiningUser->username),
            self::eventPart('text', ' joined team '),
            self::eventPart('team', $oIndex->index_name, array('key' => $oIndex->key_code)),
            self::eventPart('text', ' using the old index url (the team admin can disable this option in the team settings)'),
          );
          break;
        case 'left':
          $aEvent = array(
            self::eventPart('text', 'User '),
            self::eventPart('user', $JoiningUser->username),
            self::eventPart('text', ' left team '),
            self::eventPart('team', $oIndex->index_name, array('key' => $oIndex->key_code)),
          );
          break;
        case 'removed':
          $aEvent = array(
            self::eventPart('text', 'User '),
            self::eventPart('user', $JoiningUser->username),
            self::eventPart('text', ' was removed from team '),
            self::eventPart('team', $oIndex->index_name, array('key' => $oIndex->key_code)),
            self::eventPart('text', ' by '),
            self::eventPart('user', $EdittingUser->username),
          );
          break;
        case 'invite_link':
        default:
          $aEvent = array(
            self::eventPart('text', 'User '),
            self::eventPart('user', $JoiningUser->username),
            self::eventPart('text', ' joined team '),
            self::eventPart('team', $oIndex->index_name, array('key' => $oIndex->key_code)),
            self::eventPart('text', ' using the invite link'),
          );
      }
      $oEvent->json = json_encode($aEvent);
      $oEvent->save();
    } catch (\Exception $e) {
      Logger::warning("Error saving index join event: ".$e->getMessage());
    }
  }

  /**
   * Add index owner change event
   * @param IndexInfo $oIndex
   * @param $AllianceName
   * @param $EventType
   * @param User|null $oEdittingUser
   */
  public static function addOwnerAllianceEvent(IndexInfo $oIndex, $AllianceName, $EventType, User $oEdittingUser = null)
  {
    try {
      $oWorld = World::getWorldById($oIndex->world);
      $oEvent = new \Grepodata\Library\Model\IndexV2\Event();
      $oEvent->world = $oIndex->world;
      $oEvent->local_time = $oWorld->getServerTime();
      $oEvent->admin_only = false;
      $oEvent->index_key = $oIndex->key_code;
      switch ($EventType) {
        case 'manual_add':
          $aEvent = array(
            self::eventPart('text', 'Alliance '),
            self::eventPart('alliance', $AllianceName),
            self::eventPart('text', ' was added as a team owner by '),
            self::eventPart('user', $oEdittingUser->username)
          );
          break;
        case 'manual_remove':
          $aEvent = array(
            self::eventPart('text', 'Alliance '),
            self::eventPart('alliance', $AllianceName),
            self::eventPart('text', ' was removed as a team owner by '),
            self::eventPart('user', $oEdittingUser->username)
          );
          break;
        case 'automated':
        default:
          $aEvent = array(
            self::eventPart('text', 'Alliance '),
            self::eventPart('alliance', $AllianceName),
            self::eventPart('text', ' was automatically identified as a team owner due to their contributions to this team (you can change the team owners in the team settings)')
          );
      }
      $oEvent->json = json_encode($aEvent);
      $oEvent->save();
    } catch (\Exception $e) {
      Logger::warning("Error saving owner alliance event: ".$e->getMessage());
    }
  }

  /**
   * Add index role change
   * @param IndexInfo $oIndex
   * @param User $EditingUser
   * @param User $ChangedUser
   * @param $Role
   */
  public static function addRoleChangeEvent(IndexInfo $oIndex, User $EditingUser, User $ChangedUser, $Role)
  {
    try {
      $oWorld = World::getWorldById($oIndex->world);
      $oEvent = new \Grepodata\Library\Model\IndexV2\Event();
      $oEvent->world = $oIndex->world;
      $oEvent->local_time = $oWorld->getServerTime();
      $oEvent->admin_only = false;
      $oEvent->index_key = $oIndex->key_code;
      $aEvent = array(
        self::eventPart('text', 'User '),
        self::eventPart('user', $EditingUser->username),
        self::eventPart('text', ' changed the role of '),
        self::eventPart('user', $ChangedUser->username),
        self::eventPart('text', ' to '),
        self::eventPart('bold', Roles::named_roles[$Role]),
      );
      $oEvent->json = json_encode($aEvent);
      $oEvent->save();
    } catch (\Exception $e) {
      Logger::warning("Error saving role change event: ".$e->getMessage());
    }
  }

  /**
   * Add invite link reset event
   * @param IndexInfo $oIndex
   * @param User $EditingUser
   */
  public static function addNewInviteLinkEvent(IndexInfo $oIndex, User $EditingUser)
  {
    try {
      $oWorld = World::getWorldById($oIndex->world);
      $oEvent = new \Grepodata\Library\Model\IndexV2\Event();
      $oEvent->world = $oIndex->world;
      $oEvent->local_time = $oWorld->getServerTime();
      $oEvent->admin_only = true;
      $oEvent->index_key = $oIndex->key_code;
      $aEvent = array(
        self::eventPart('text', 'User '),
        self::eventPart('user', $EditingUser->username),
        self::eventPart('text', ' has reset the invite link of this team. The previous link will no longer work.'),
      );
      $oEvent->json = json_encode($aEvent);
      $oEvent->save();
    } catch (\Exception $e) {
      Logger::warning("Error saving invite link event: ".$e->getMessage());
    }
  }

  private static function eventPart($Type = 'text', $Text = '', $Params = array())
  {
    return array(
      'type' => $Type,
      'text' => $Text,
      'params' => $Params,
    );
  }


}

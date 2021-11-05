<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;

class Reaction extends \Grepodata\Library\Router\BaseRoute
{

  public static function ThreadReactionsGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'thread_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], true, false, true);

      // get reactions
      $aReactions = \Grepodata\Library\Controller\IndexV2\Reaction::allByThread($oUser, $aParams['world'], $aParams['thread_id']);

      $aResponse = array(
        'posts' => self::renderPostReactions($oUser, $aReactions)
      );
      ResponseCode::success($aResponse);
    } catch (\Exception $e) {
      Logger::warning("Unable to get thread reactions: " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'No reactions found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function NewReactionGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'player_id', 'thread_id', 'post_id', 'reaction'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], true, false, true);

      if (strlen($aParams['reaction']) > 8) {
        ResponseCode::errorCode();
      }

      // Get indexes for player
      $aIndexes = IndexInfo::allByUserAndWorld($oUser, $aParams['world']);

      if (count($aIndexes) <= 0) {
        ResponseCode::errorCode();
      }

      // Save reaction to each team
      foreach ($aIndexes as $oIndex) {
        $oReaction = \Grepodata\Library\Model\IndexV2\Reaction::firstOrNew(array(
          'index_key' => $oIndex->key_code,
          'thread_id' => $aParams['thread_id'],
          'post_id' => $aParams['post_id'],
          'user_id' => $oUser->id,
          'reaction' => $aParams['reaction'],
        ));
        $oReaction->world = $aParams['world'];
        $oReaction->player_id = $aParams['player_id'];
        $oReaction->save();
      }

      // get reactions
      $aReactions = \Grepodata\Library\Controller\IndexV2\Reaction::allByPost($oUser, $aParams['world'], $aParams['post_id']);

      $aResponse = array(
        'posts' => self::renderPostReactions($oUser, $aReactions)
      );
      ResponseCode::success($aResponse);
    } catch (\Exception $e) {
      Logger::warning("Unable to add thread reaction: " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to add reaction.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function DeleteReactionGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'thread_id', 'post_id', 'reaction'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], true, false, true);

      if (strlen($aParams['reaction']) > 8) {
        ResponseCode::errorCode();
      }

      // Get indexes for player
      $aIndexes = IndexInfo::allByUserAndWorld($oUser, $aParams['world']);

      if (count($aIndexes) <= 0) {
        ResponseCode::errorCode();
      }

      // Delete reaction from each team
      foreach ($aIndexes as $oIndex) {
        $oReaction = \Grepodata\Library\Model\IndexV2\Reaction::firstOrNew(array(
          'index_key' => $oIndex->key_code,
          'thread_id' => $aParams['thread_id'],
          'post_id' => $aParams['post_id'],
          'user_id' => $oUser->id,
          'reaction' => $aParams['reaction'],
        ));
        if ($oReaction !== false) {
          $oReaction->delete();
        }
      }

      // get reactions
      $aReactions = \Grepodata\Library\Controller\IndexV2\Reaction::allByPost($oUser, $aParams['world'], $aParams['post_id']);

      $aResponse = array(
        'posts' => self::renderPostReactions($oUser, $aReactions)
      );
      ResponseCode::success($aResponse);
    } catch (\Exception $e) {
      Logger::warning("Unable to delete thread reaction: " . $e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to delete reaction.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  private static function renderPostReactions($oUser, $aReactions)
  {
    $aCombined = array();
    /** @var \Grepodata\Library\Model\IndexV2\Reaction $oReaction */
    foreach ($aReactions as $oReaction) {
      if (!key_exists($oReaction->post_id, $aCombined)) {
        $aCombined[$oReaction->post_id] = array();
      }
      if (key_exists($oReaction->reaction, $aCombined[$oReaction->post_id])) {
        $aCombined[$oReaction->post_id][$oReaction->reaction]['players'][] = $oReaction->name;
      } else {
        $aCombined[$oReaction->post_id][$oReaction->reaction] = array(
          'players' => array($oReaction->name),
          'active' => false
        );
      }

      if ($oReaction->user_id == $oUser->id) {
        $aCombined[$oReaction->post_id][$oReaction->reaction]['active'] = true;
      }
    }
    return $aCombined;
  }

}

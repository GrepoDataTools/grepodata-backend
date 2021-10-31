<?php

namespace Grepodata\Application\API\Route\IndexV2;

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

      $aResponse = array(
        'posts' => $aCombined
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

}

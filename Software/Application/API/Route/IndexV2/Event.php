<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Event extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * API route: /events/user
   * Method: GET
   */
  public static function GetAllByUserGET()
  {
    try {
      $aParams = self::validateParams(array('access_token'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $From = 0;
      $Size = 20;
      if (isset($aParams['size']) && $aParams['size'] < 50) {
        $Size = $aParams['size'];
      }
      if (isset($aParams['from']) && $aParams['from'] < 5000) {
        $From = $aParams['from'];
      }

      // Get events
      $Start = round(microtime(true) * 1000);
      $aEvents = \Grepodata\Library\Controller\IndexV2\Event::getAllByUser($oUser, $From, $Size);

      $aResponse = array(
        'count' => 0,
        'items' => array()
      );
      foreach ($aEvents as $oEvent) {
        $aResponse['items'][] = $oEvent->getPublicFields();
      }
      $aResponse['count'] = count($aResponse['items']);

      if ($From == 0 && count($aResponse['items']) >= $Size) {
        // count total
        $aResponse['total'] = \Grepodata\Library\Controller\IndexV2\Event::countAllByUser($oUser);
      }

      $ElapsedMs = round(microtime(true) * 1000) - $Start;
      if ($ElapsedMs > 1000) {
        Logger::warning("Slow query warning: GetAllByUserGet > ".$ElapsedMs."ms > ".$oUser->id);
      }

      ResponseCode::success($aResponse);
    } catch (\Exception $e) {
      Logger::warning("Error getting user events: ".$e->getMessage());
      ResponseCode::errorCode(1000, array(), 404);
    }
  }


  /**
   * API route: /events/team
   * Method: GET
   */
  public static function GetAllByTeamGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      try {
        if (!bDevelopmentMode) {
          $oUserRole = Roles::getUserIndexRole($oUser, $aParams['index_key']);
          $bUserIsAdmin = in_array($oUserRole->role, Roles::admin_roles);
        } else {
          $bUserIsAdmin = true;
        }
      } catch (ModelNotFoundException $e) {
        // unauthorized
        ResponseCode::errorCode(7500, array(), 401);
      }

      $From = 0;
      $Size = 20;
      if (isset($aParams['size']) && $aParams['size'] < 50) {
        $Size = $aParams['size'];
      }
      if (isset($aParams['from']) && $aParams['from'] < 5000) {
        $From = $aParams['from'];
      }


      // Get events
      $Start = round(microtime(true) * 1000);
      $aEvents = \Grepodata\Library\Controller\IndexV2\Event::getAllByTeam($aParams['index_key'], $bUserIsAdmin, $From, $Size);

      $aResponse = array(
        'count' => 0,
        'items' => array()
      );
      foreach ($aEvents as $oEvent) {
        $aResponse['items'][] = $oEvent->getPublicFields();
      }
      $aResponse['count'] = count($aResponse['items']);

      if ($From == 0 && count($aResponse['items']) >= $Size) {
        // count total
        $aResponse['total'] = \Grepodata\Library\Controller\IndexV2\Event::countAllByTeam($aParams['index_key'], $bUserIsAdmin);
      }

      $ElapsedMs = round(microtime(true) * 1000) - $Start;
      if ($ElapsedMs > 1000) {
        Logger::warning("Slow query warning: GetAllByTeamGET > ".$ElapsedMs."ms > ".$aParams['index_key']);
      }

      ResponseCode::success($aResponse);
    } catch (\Exception $e) {
      Logger::warning("Error getting team events: ".$e->getMessage());
      ResponseCode::errorCode(1000, array(), 404);
    }
  }

}

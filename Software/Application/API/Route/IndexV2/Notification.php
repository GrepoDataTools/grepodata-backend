<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Notification extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * Returns a list of all notifications for the given team
   */
  public static function AllByUserGET()
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

      $aNotifications = \Grepodata\Library\Controller\IndexV2\Notification::allByUser($oUser, $From, $Size);

      $aResponse = array();
      foreach ($aNotifications as $oNotification) {
        $aResponse[] = $oNotification->getPublicFields();
      }

      $aResponse = array(
        'size' => sizeof($aResponse),
        'data' => $aResponse
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'Notifications not found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

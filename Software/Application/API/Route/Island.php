<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\User;
use Grepodata\Library\Elasticsearch\Search;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Island extends \Grepodata\Library\Router\BaseRoute
{
  public static function IslandsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'x_min', 'x_max', 'y_min', 'y_max'));
      if ($aParams['x_max']-$aParams['x_min']>100) {
        ResponseCode::errorCode(9010, array('invalid_range'=>'window width exceeds 100 (x_max - x_min > 100). please specify a smaller window'), 400);
      }
      if ($aParams['y_max']-$aParams['y_min']>100) {
        ResponseCode::errorCode(9020, array('invalid_range'=>'window height exceeds 100 (y_max - y_min > 100). please specify a smaller window'), 400);
      }
      $aIslands = \Grepodata\Library\Controller\Island::allInRange($aParams['world'], $aParams['x_min'], $aParams['x_max'], $aParams['y_min'], $aParams['y_max']);
      $aResponse = array();
      foreach ($aIslands as $oIsland) {
        $aResponse[] = $oIsland->getMinimalFields();
      }
      $aResponse = array(
        'count' => count($aResponse),
        'items' => $aResponse
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No islands found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

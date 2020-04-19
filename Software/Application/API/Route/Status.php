<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\DailyReport;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Status extends \Grepodata\Library\Router\BaseRoute
{
  public static function IndexerUsageGET()
  {
    try {
      $aParams = self::validateParams();

      $aResponse = array();
      $aStats = DailyReport::where('type', 'LIKE', 'indexer%')->get();
      /** @var DailyReport $oStat */
      foreach ($aStats as $oStat) {
        $aResponse[$oStat->type] = array(
          'type' => $oStat->type,
          'title' => $oStat->title,
          'data' => json_decode($oStat->data, true),
        );
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No stats found.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}
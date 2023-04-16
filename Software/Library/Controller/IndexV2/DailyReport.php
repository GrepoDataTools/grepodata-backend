<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Logger\Logger;

class DailyReport
{

  /**
   * Persist count to database
   * @param $type
   * @param $increment
   */
  public static function increment_persisted_property($type, $increment) {
    try {
      $oReport = \Grepodata\Library\Model\Indexer\DailyReport::firstOrNew(array('type' => $type));
      $oReport->title = "Number of records deleted";
      if (empty($oReport->data)) {
        $oReport->data = $increment;
      } else {
        $oReport->data = $oReport->data + $increment;
      }
      Logger::warning("Persisting to database: $type = ".$oReport->data);
      $oReport->save();
    } catch (\Exception $e) {
      Logger::error("Error persisting record count to database: ".$e->getMessage() . " [$type; $increment]");
    }
  }

}

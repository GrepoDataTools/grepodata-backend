<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Grepodata\Library\Logger\Logger;

class Reporting extends \Grepodata\Library\Router\BaseRoute
{

  public static function BugReportPOST()
  {
    try {
      $aParams = self::validateParams(array('error', 'function', 'browser', 'version', 'world', 'index'));

      // Save bug report as logmessage
      Logger::indexDebug(json_encode($aParams));

      die(self::OutputJson(array('success' => true), 200));
    } catch (\Exception $e) {
      Logger::warning("Unable to save bug report: " . $e->getMessage());
      die(self::OutputJson(array('success' => true), 200));
    }
  }

}

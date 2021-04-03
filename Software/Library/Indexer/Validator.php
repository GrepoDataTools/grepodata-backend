<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;

class Validator
{

  /**
   * @param $Key
   * @return bool | IndexInfo
   */
  public static function IsValidIndex($Key)
  {
    if (!is_string($Key) || strlen($Key) != 8) return false;

    try {
      $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($Key);
      if ($oIndex!=false && $oIndex!=null) {
        return $oIndex;
      }
    } catch (\Exception $e) {
      Logger::warning("Unable to validate index for key: " . $Key);
    }

    return false;
  }

}

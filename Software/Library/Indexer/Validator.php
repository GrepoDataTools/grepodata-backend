<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Controller\Indexer\BotDetect;
use Grepodata\Library\Cron\Common;
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
    // TODO return new session id to stop subsequent requests from validating the index key (faster?)

    if (BotDetect::isBanned($_SERVER['REMOTE_ADDR'])) return false;
    if (!is_string($Key) || strlen($Key) != 8) return false;

    try {
      $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($Key);
      if ($oIndex!=false&&$oIndex!=null) {
        return $oIndex;
      }
    } catch (\Exception $e) {
      Logger::warning("Unable to validate index for key: " . $Key);
    }

    // Illegal key attempt
    BotDetect::addInvalidAttempt($_SERVER['REMOTE_ADDR']);
    return false;
  }

}
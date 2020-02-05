<?php

namespace Grepodata\Library\Controller\Indexer;

use DateTime;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\Banned;

class BotDetect
{

  /**
   * Returns true if the user has been previously banned
   * @param $Client
   * @return boolean
   */
  public static function isBanned($Client)
  {
    $aBans = \Grepodata\Library\Model\Indexer\Banned::where('client', '=', $Client)->get();
    if ($aBans == null || sizeof($aBans)<=0) return false;
    else {
      foreach ($aBans as $oBan) {
        if (strtotime($oBan->expires) > time()) return true;
      }
    }
    return false;
  }

  /**
   * Adds a new invalid attempt to the tracker
   * @param $Client
   */
  public static function addInvalidAttempt($Client)
  {
    try {
      // add new record
      $oAttempt = new \Grepodata\Library\Model\Indexer\BotDetect();
      $oAttempt->client = $Client;
      $oAttempt->save();

      // check if total in last 10 minutes exceeds 20
      $date = new DateTime;
      $date->modify('-10 minutes');
      $formatted_date = $date->format('Y-m-d H:i:s');
      $aAttempts = \Grepodata\Library\Model\Indexer\BotDetect::where('client', '=', $Client, 'and')->where('created_at','>=',$formatted_date)->get();

      if (sizeof($aAttempts) > 15) {
        Logger::error('Banning client. Number of attempts exceeds threshold [' . $Client . ']');
        $oExpires = new DateTime();
        $numBans = 1;
        $aBans = \Grepodata\Library\Model\Indexer\Banned::where('client', '=', $Client)->get();
        if ($aBans != null) $numBans = sizeof($aBans);
        $oExpires->modify('+ '. ($numBans*10) . ' minutes');
        $oBan = new Banned();
        $oBan->client = $Client;
        $oBan->expires = $oExpires->format('Y-m-d H:i:s');
        $oBan->save();
      }

    } catch (\Exception $e) {
      Logger::error('Error handling bot tracker: ' . $e->getMessage());
    }

  }

}
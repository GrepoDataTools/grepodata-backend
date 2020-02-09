<?php

namespace Grepodata\Library\Controller\Indexer;

class Report
{

  /**
   * @param $Fingerprint string md5 hash of report contents
   * @param $Key string index identifier
   * @return boolean
   */
  public static function exists($Fingerprint, $Key)
  {
    $oUser = \Grepodata\Library\Model\Indexer\Report::where('index_code', '=', $Key, 'and')
      ->where('fingerprint', '=', $Fingerprint)
      ->first();
    if ($oUser !== null) {
      return true;
    }
    return false;
  }

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\Indexer\Report
   */
  public static function firstById($Id)
  {
    return \Grepodata\Library\Model\Indexer\Report::where('id', '=', $Id)
      ->firstOrFail();
  }

}
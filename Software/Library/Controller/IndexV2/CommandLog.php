<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Logger\Logger;

class CommandLog
{

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\IndexV2\CommandLog
   */
  public static function first($Id)
  {
    return \Grepodata\Library\Model\IndexV2\CommandLog::where('id', '=', $Id)
      ->first();
  }

  /**
   * @param $Id
   * @return \Grepodata\Library\Model\IndexV2\CommandLog
   */
  public static function firstOrFail($Id)
  {
    return \Grepodata\Library\Model\IndexV2\CommandLog::where('id', '=', $Id)
      ->firstOrFail();
  }

  public static function log($Action, $Team, $NumUploaded = null, $NumCreated = null, $NumUpdated = null, $NumDeleted = null, $ProcessingMs = null, $UserId = null)
  {
    try {
      $oCommandLog = new \Grepodata\Library\Model\IndexV2\CommandLog();
      $oCommandLog->uid = $UserId;
      $oCommandLog->action = $Action;
      $oCommandLog->team = $Team;
      $oCommandLog->num_uploaded = $NumUploaded;
      $oCommandLog->num_created = $NumCreated;
      $oCommandLog->num_updated = $NumUpdated;
      $oCommandLog->num_deleted = $NumDeleted;
      $oCommandLog->processing_ms = $ProcessingMs;
      $oCommandLog->save();
    } catch (\Exception $e) {
      Logger::warning("OPS: Error logging command stats ".$e->getMessage());
    }
  }

}

<?php

use Grepodata\Library\Controller\Town;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');


try {
  // Find worlds to process
  $worlds = Common::getAllActiveWorlds();
  if ($worlds !== false) {
    foreach ($worlds as $oWorld) {
      $oWorld = \Grepodata\Library\Controller\World::getWorldById('nl85');
      $aLocalData = LocalData::getLocalTownData($oWorld->grep_id);
      if (!$aLocalData) {
        continue;
      }

      $LocalKeys = array_keys($aLocalData);

      $NumDeleted = 0;
      $aTownsDatabase = Town::allByWorld($oWorld->grep_id);
      foreach ($aTownsDatabase as $oTown) {
        if (!in_array($oTown->grep_id, $LocalKeys)) {
          // Town is no longer present, delete from db and es
          try {
            $NumDeleted++;
            $oTown->delete();
            Import::DeleteTown($oTown);
          } catch (\Exception $e) {
            Logger::debugInfo("Unable to delete town: ". $e->getMessage() . " - " . $e->getTraceAsString());
          }
        }
      }

      Logger::debugInfo("Deleted $NumDeleted destroyed towns from the database.");
    }
  }
} catch (\Exception $e) {
  Logger::error("Exception while deleting destroyed towns: " . $e->getMessage());
}


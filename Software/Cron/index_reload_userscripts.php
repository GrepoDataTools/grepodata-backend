<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started index userscript builder");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 24*60);

// Find worlds to process
$aWorlds = Common::getAllActiveWorlds();
if ($aWorlds === false) {
  Logger::error("Terminating execution of index userscript builder: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

foreach ($aWorlds as $oWorld) {
  $aIndex = Common::getAllIndexesByWorld($oWorld);
  if ($aIndex === false) {
    Logger::error("Terminating execution of index userscript builder: Error retrieving index keys from database.");
    Common::endExecution(__FILE__);
  }

  Logger::silly("Reloading userscripts for world " . $oWorld->grep_id . ". Num indexes: " . count($aIndex));

  /** @var IndexInfo $oIndex */
  foreach ($aIndex as $oIndex) {
    // Check commands 'php SCRIPTNAME[=0] INDEXCODE[=1]'
    if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oIndex->key_code) continue;

    IndexBuilder::createUserscript($oIndex->key_code, $oWorld);
    $oIndex->script_version = USERSCRIPT_VERSION; // update active version
    $oIndex->save();
  }
}

Logger::debugInfo("Finished successful execution of index userscript builder.");
Common::endExecution(__FILE__, $Start);
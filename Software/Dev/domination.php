<?php

namespace Grepodata\Cron;

use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started hourly data import");

//Common::markAsRunning(__FILE__, 2*60);

// Find worlds to process
//$aWorlds = Common::getAllActiveWorlds();
//if ($aWorlds === false) {
//  Logger::error("Terminating execution of hourly import: Error retrieving worlds from database.");
//  Common::endExecution(__FILE__);
//}

$aWorlds = array(
  World::getWorldById("nl63")
);

/** @var \Grepodata\Library\Model\World $oWorld */
foreach ($aWorlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

  //todo: count domination towns and add history

}

Logger::debugInfo("Finished successful execution of domination update.");
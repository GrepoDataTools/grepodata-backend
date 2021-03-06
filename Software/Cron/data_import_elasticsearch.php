<?php

namespace Grepodata\Cron;
use Carbon\Carbon;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Elasticsearch\Import;
use Grepodata\Library\Import\Elasticsearch;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started elasticsearch import");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 20*60);

// Find worlds to process
$aWorlds = Common::getAllActiveWorlds();
if ($aWorlds === false) {
  Logger::error("Terminating execution of elasticsearch import: Error retrieving worlds from database.");
  Common::endExecution(__FILE__);
}

$bForceAllObjects = false;
if (isset($argv[1]) && $argv[1]!=null && $argv[1]==='force-all') {
  Logger::error("ES import: forcing import of all objects");
  $bForceAllObjects = true;
}

foreach ($aWorlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] FORCEALL[=1] WORLD[=1 or 2]'
  if (isset($argv[1]) && !isset($argv[2]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!='force-all' && $argv[1]!=$oWorld->grep_id) continue;
  else if (isset($argv[2]) && $argv[2]!=null && $argv[2]!='' && $argv[2]!=$oWorld->grep_id) continue;

  try {
    Elasticsearch::DataImportElasticsearch($oWorld, $bForceAllObjects);
  } catch (\Exception $e) {
    Logger::error("Error processing elasticsearch import for world " . $oWorld . " (".$e->getMessage().")");
    continue;
  }
}

Logger::debugInfo("Finished execution of elasticsearch import.");
Common::endExecution(__FILE__, $Start);
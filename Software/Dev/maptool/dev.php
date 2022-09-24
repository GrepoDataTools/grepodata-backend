<?php

namespace Grepodata\Cron;

use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

//$world_id = "nl97";
//$oWorld = World::getWorldById($world_id);


$aWorlds = array(
  World::getWorldById("nl97")
);

/** @var \Grepodata\Library\Model\World $oWorld */
foreach ($aWorlds as $oWorld) {
  // Check commands 'php SCRIPTNAME[=0] WORLD[=1]'
  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$oWorld->grep_id) continue;

  //todo: generate map data

}
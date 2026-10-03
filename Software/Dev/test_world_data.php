<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Grepodata\Library\Cron\WorldData;

$aServers = WorldData::loadWorldNames();
print_r($aServers);

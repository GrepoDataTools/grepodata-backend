<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Redis\RedisHelper;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();
Logger::debugInfo("Started websocket heartbeat");

$Start = Carbon::now();
Common::markAsRunning(__FILE__, 1);

// Send a heartbeat message over the Redis backbone to let the WebSocket server know if the connection is still alive
$NumReceivers = RedisHelper::SendBackboneHeartbeat();

if ($NumReceivers <= 0) {
  Logger::error("No receiver for backbone heartbeat");
} else {
  Logger::silly("Number of backbone listeners: ".$NumReceivers);
}

Logger::debugInfo("Finished execution of websocket heartbeat");
Common::endExecution(__FILE__, $Start);

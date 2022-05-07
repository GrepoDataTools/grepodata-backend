<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;

$redis = new Redis();

$redis->connect(REDIS_HOST, REDIS_PORT, 0);

try {
  $response = $redis->info();
  printf('redis ok');
  print_r($response);
} catch (Exception $e) {
  printf('redis exception '.$e->getMessage());
}

$t=2;

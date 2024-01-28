<?php

namespace Grepodata\Dev;

use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');


$aWorlds = Common::getAllActiveWorlds();

$aWorldsList = array();
foreach ($aWorlds as $oWorld) {
  $aWorldsList[] = $oWorld->grep_id;
}

$requestsPerSecond = 100;
$delay = 1000000 / $requestsPerSecond; // microseconds delay for 20 requests per second

$total_requests = 0;
$response_times = array();
while (true) {
  $random_world = $aWorldsList[array_rand($aWorldsList)];
  $start = microtime(true);
  $url = "https://api.grepodata.com/data/".$random_world."/player_idle.json?_=".time();
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
  $output = curl_exec($ch);
  curl_close($ch);
  $elapsed_ms = (microtime(true) - $start) * 1000;
  $response_times[] = $elapsed_ms;


  $total_requests += 1;
  if ($total_requests % 20 == 0) {
    echo "Total requests: " . $total_requests . ", ms/req " . (array_sum(array_slice($response_times, -20)) / 20) . PHP_EOL;
  }

  usleep($delay); // Delay to control the request rate
}

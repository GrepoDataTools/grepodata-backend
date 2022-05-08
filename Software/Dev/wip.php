<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;


$aLocalTowns = LocalData::getLocalTownData('nl95');
$aLocalPlayers = LocalData::getLocalPlayerData('nl95');

$aTestIntel = \Grepodata\Library\Model\IndexV2\Intel::select(
  \Grepodata\Library\Model\IndexV2\Intel::getIdentifierSelect(true)
)->where('id', '=', 5963808)->cursor();

foreach ($aTestIntel as $oCity) {
  $aTestTown = $aLocalTowns[$oCity->town_id];
  $aTestPlayer = $aLocalPlayers[$oCity->player_id];

  $oCity->player_name = $oCity->player_name . 'zz';
  $oCity->save();
  $t=2;
}

$t=2;

//$redis = new Redis();
//
//$redis->connect(REDIS_HOST, REDIS_PORT, 0);
//
//try {
//  $response = $redis->info();
//  printf('redis ok');
//  print_r($response);
//} catch (Exception $e) {
//  printf('redis exception '.$e->getMessage());
//}

$t=2;

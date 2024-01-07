<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Cron\LocalData;
use Grepodata\Library\Logger\Logger;

$MaxDate = Carbon::now('Europe/Amsterdam');

$oWorld = \Grepodata\Library\Controller\World::getWorldById('us121');
$timezone = $oWorld->php_timezone;

$NowServer = $oWorld->getServerTime();
$Now = Carbon::now();

$oConquest = \Grepodata\Library\Controller\IndexV2\Conquest::first(191174);
$LastAttack = Carbon::parse($oConquest->first_attack_date, $oWorld->php_timezone);
$LastAttack2 = Carbon::parse($oConquest->first_attack_date);

$oTownConquest = \Grepodata\Library\Model\Conquest::where('id', '=', 157845250)->first();
$oConquestTime = Carbon::parse($oTownConquest->time)->setTimezone($oWorld->php_timezone);

$timestamp_us122 = 1704594811-(3600*6);
$timestamp_utc = 1704594811;

$Test = Carbon::parse($timestamp_utc);

$DiffToSiege = $LastAttack->diffInMinutes($Test, false);

$nowdiff = $NowServer->diffInHours(Carbon::parse($oConquest->first_attack_date, $oWorld->php_timezone));
$conquestdiff = $LastAttack->diffInHours($oConquestTime, false);

$t=2;
//$aLocalTowns = LocalData::getLocalTownData('nl95');
//$aLocalPlayers = LocalData::getLocalPlayerData('nl95');
//
//$aTestIntel = \Grepodata\Library\Model\IndexV2\Intel::select(
//  \Grepodata\Library\Model\IndexV2\Intel::getIdentifierSelect(true)
//)->where('id', '=', 5963808)->cursor();
//
//foreach ($aTestIntel as $oCity) {
//  $aTestTown = $aLocalTowns[$oCity->town_id];
//  $aTestPlayer = $aLocalPlayers[$oCity->player_id];
//
//  $oCity->player_name = $oCity->player_name . 'zz';
//  $oCity->save();
//  $t=2;
//}
//
//$t=2;

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

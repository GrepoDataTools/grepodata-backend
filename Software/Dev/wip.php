<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\WorldData;
use Grepodata\Library\Indexer\IndexBuilder;
use Grepodata\Library\Model\Operation_log;

$t = Carbon::now()->subDay();
$t2 = Carbon::now();

//$aWorlds2 = World::getAllActiveWorlds();
$aWorlds = \Grepodata\Library\Cron\Common::getAllActiveWorlds();

//$aConquests = \Grepodata\Library\Model\Indexer\Conquest::whereNull('uid')->get();
//foreach ($aConquests as $oConquest) {
//  $oConquest->uid = md5(IndexBuilder::generateIndexKey(32) . time());
//  $oConquest->save();
//}

$aCities = CityInfo::allByTownIdByKeys(array('5be6zv1s'), 10043);

//$LinkDataEncoded = '#eyJpZCI6MTI4MTIsIml4Ijo0NzUsIml5Ijo1MTcsInRwIjoidG93biIsIm5hbWUiOiJGYW5mYXJlIHZhbiBob25nZXIifQ==';
//$LinkDataEncoded = '#eyJuYW1lIjoibWFpb3IiLCJpZCI6NDEyNDkwfQ==';
//$LinkDataEncoded = '#eyJpZCI6MjEzMDAsIml4Ijo0NzAsIml5Ijo1MTYsInRwIjoidG93biIsIm5hbWUiOiI0NS5BLjEgTGFzZXIifQ==';
//$LinkDataEncoded = '#eyJuYW1lIjoiTmVsbHkxOTgwIiwiaWQiOjEzODkzMzZ9';
$LinkDataEncoded = '#eyJpZCI6MTU1ODksIml4Ijo0NzIsIml5Ijo1MzYsInRwIjoidG93biIsIm5hbWUiOiJPQyA0NSBQZW5zcG9ueSJ9';
$aLinkData = json_decode(base64_decode($LinkDataEncoded), true);
$t=2;

//$ScoreboardTime = Carbon::now();
//$ScoreboardTime->setTimezone('Europe/Istanbul');
//$Test = $ScoreboardTime->format('H:i:s');
//
//$String = Carbon::now()->toDateTimeString();
//
//$oWorld = \Grepodata\Library\Controller\World::getWorldById('nl66');
//
//$ServerTime = $oWorld->getServerTime();
//$Timestamp = $ServerTime->timestamp;
//
//$ConquestLowerTimeLimit = strtotime($oWorld->last_reset_time);
//$ConquestLowerTimeLimit2 = strtotime($oWorld->getLastUtcResetTime());

//\Grepodata\Library\Cron\Common::getAllActiveWorlds();
//\Grepodata\Library\Cron\Common::getAllActiveWorlds(false);

//$Days = 14;
//$Limit = Carbon::now()->subDays($Days);
//$aPlayers = \Grepodata\Library\Model\Alliance::where('world', '=', 'nl66', 'and')
//  ->where('updated_at', '>', $Limit)
//  ->get();

//$aHistory = \Grepodata\Library\Model\PlayerHistory::where('created_at', '<', $Limit, 'and')
//  ->whereRaw("DAY(created_at) = 1") // First of month
////  ->whereRaw("MOD(DAY(created_at)-1, 5) = 0") // every 5 days
//  ->get();


//use Illuminate\Database\Capsule\Manager as DB;
//$start = microtime(true);
//$aResult = \Grepodata\Library\Model\Player::where('world', '=', $oWorld->grep_id, 'and')
//  ->where('active', '=', 1, 'and')
////  ->where(function ($query) {
////    $query->where('att_old', '!=', DB::raw("`att`"))
////      ->orWhere('def_old', '!=', DB::raw("`def`"));
////  })
//  ->update([
//    'att_rank_max' => DB::raw("`att_rank`"),
//    'def_rank_max' => DB::raw("`def_rank`"),
//  ]);
//$end = microtime(true);
//$diff = $end - $start;

$t=2;
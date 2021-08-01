<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;

//$t = Carbon::now()->subDays(2);
//$t2 = Carbon::now();
//
//$aDebug = \Grepodata\Library\Model\Operation_log::where('message', 'LIKE', 'VerifiedScriptLink%')->where('created_at', '>', $t)->get();
//$counts = array();
//$countsMultiple = array();
//foreach ($aDebug as $Debug) {
//  $msg = $Debug->created_at . ' - ' . $Debug->message;
//  preg_match_all('/\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b/', $msg, $aMatch);
//  if (isset($counts[$aMatch[0][0]])) {
//    $counts[$aMatch[0][0]][] = $msg;
//    $countsMultiple[$aMatch[0][0]] = $counts[$aMatch[0][0]];
//  } else {
//    $counts[$aMatch[0][0]] = array($msg);
//  }
//}
//
//$t=2;

//$aWorlds2 = World::getAllActiveWorlds();
//$aWorlds = \Grepodata\Library\Cron\Common::getAllActiveWorlds();

//$aConquests = \Grepodata\Library\Model\Indexer\Conquest::whereNull('uid')->get();
//foreach ($aConquests as $oConquest) {
//  $oConquest->uid = md5(IndexBuilder::generateIndexKey(32) . time());
//  $oConquest->save();
//}

//$LinkDataEncoded = '#eyJpZCI6MTI4MTIsIml4Ijo0NzUsIml5Ijo1MTcsInRwIjoidG93biIsIm5hbWUiOiJGYW5mYXJlIHZhbiBob25nZXIifQ==';
//$LinkDataEncoded = '#eyJuYW1lIjoibWFpb3IiLCJpZCI6NDEyNDkwfQ==';
//$LinkDataEncoded = '#eyJpZCI6MjEzMDAsIml4Ijo0NzAsIml5Ijo1MTYsInRwIjoidG93biIsIm5hbWUiOiI0NS5BLjEgTGFzZXIifQ==';
//$LinkDataEncoded = '#eyJuYW1lIjoiTmVsbHkxOTgwIiwiaWQiOjEzODkzMzZ9';
//$LinkDataEncoded = '#eyJpZCI6MjU4LCJpeCI6NDkyLCJpeSI6NDc4LCJ0cCI6ImZhcm1fdG93biIsIm5hbWUiOiJEcmFhZWdpa3kiLCJyZWxhdGlvbl9zdGF0dXMiOjF9';
//$LinkDataEncoded = '#eyJpZCI6MjU4LCJpeCI6NTAyLCJpeSI6NDg4LCJ0cCI6InRvd24iLCJuYW1lIjoiQS4gU2xha2tlbnRlbXBvIn0=';
$LinkDataEncoded = '#eyJpZCI6MTU5MDIsIml4Ijo1MTAsIml5Ijo1MDUsInRwIjoidGVtcGxlIiwibmFtZSI6Ik9seW1wdXMifQ==';
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

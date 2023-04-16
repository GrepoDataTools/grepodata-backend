<?php

use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Capsule\Manager as DB;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

// Find worlds to process
$worlds = Common::getAllActiveWorlds();
//$worlds = array(\Grepodata\Library\Controller\World::getWorldById('nl103'));
$totalworlds = count($worlds);

$k = 0;
foreach ($worlds as $world) {
  $k++;

  // foreach index in world
  $aTeams = \Grepodata\Library\Controller\Indexer\IndexInfo::allByWorld($world->grep_id);
//  $aTeams = array(\Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail('ejw1anpb'));
  $total = count($aTeams);
  Logger::debugInfo("[" . $k . " / " . $totalworlds . "] Processing world " . $world->grep_id . ". Teams: " . $total);
  $i = 0;
  foreach ($aTeams as $oTeam) {
    $i++;
    Logger::silly("[" . $world->grep_id . " " . $i . " / " . $total . "] Processing team " . $oTeam->key_code);

    $Query = "
    UPDATE Indexer_intel_shared as shared
    LEFT JOIN (
        SELECT sub.report_hash, sub.user_id
        FROM (
            SELECT report_hash, MIN(user_id) as user_id,  COUNT(DISTINCT user_id) as num_uploaders
            FROM Indexer_intel_shared
            WHERE report_hash IN (
                SELECT report_hash
                FROM Indexer_intel_shared 
                WHERE index_key = '".$oTeam->key_code."'
            )
            AND user_id IS NOT NULL
            GROUP BY report_hash
        ) as sub 
        WHERE sub.num_uploaders = 1
    ) as user_shared ON shared.report_hash = user_shared.report_hash
    SET shared.upload_uid = user_shared.user_id
    WHERE shared.index_key = '".$oTeam->key_code."' 
    AND shared.upload_uid IS NULL
    AND user_shared.user_id IS NOT NULL
      ";
    $aResult = DB::select(DB::raw($Query));
    $t=2;
  }


}

$t=2;

Logger::debugInfo("Done.");
<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Capsule\Manager as DB;

Logger::enableDebug();

//$aUserRoles = \Grepodata\Library\Model\IndexV2\Roles::where('user_id', '=', 1)->where('uncommitted_reports', '=', null)->get();
$aUserRoles = \Grepodata\Library\Model\IndexV2\Roles::get();

$Total = count($aUserRoles);

\Grepodata\Library\Logger\Logger::silly("Total Role count: ".$Total);

$i = 0;
$total_uncommitted = 0;
foreach ($aUserRoles as $oRole) {
  $i++;
  If ($i%100==0) {
    \Grepodata\Library\Logger\Logger::silly("Progress: ".$i."/".$Total.", total uncommitted: ".$total_uncommitted);
  }

  try {
    $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($oRole->index_key);
    $Count = \Grepodata\Library\Controller\IndexV2\IntelShared::countUncommitted($oRole->user_id, $oIndex->world, $oIndex->key_code);
    if ($Count > 0) {
      $total_uncommitted += $Count;
      $oRole->uncommitted_reports = $Count;
      $oRole->uncommitted_status = 'Unread';
      $oRole->save();
    }
  } catch (Exception $e) {
    \Grepodata\Library\Logger\Logger::warning("Error parsing commitment: ".$e->getMessage());
  }
}

\Grepodata\Library\Logger\Logger::silly("Done. Total uncommitted: ".$total_uncommitted);

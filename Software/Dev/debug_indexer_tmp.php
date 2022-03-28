<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;
use Grepodata\Library\Cron\Common;

// Get all reports
$aReports =  \Grepodata\Library\Model\IndexV2\IntelShared::where('world', '=', 'nl92', 'and')
      ->where('user_id', '=', '8732')
      ->get();


foreach ($aReports as $IntelShared) {
  $Report = \Grepodata\Library\Controller\IndexV2\Intel::getById($IntelShared->intel_id);
  $oCity = Common::debugIndexer($Report);

  $t=2;
}
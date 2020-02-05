<?php

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

use Carbon\Carbon;

use Illuminate\Database\Capsule\Manager as DB;
$Duplicates = DB::select( DB::raw(
  "SELECT a.*
  FROM Index_city a
  JOIN (SELECT index_key, town_id, parsed_date, COUNT(*)
  FROM Index_city 
  GROUP BY index_key, town_id, parsed_date
  HAVING count(*) > 1 ) b
  ON a.index_key = b.index_key
  AND a.town_id = b.town_id
  AND a.parsed_date = b.parsed_date
  WHERE a.type = 'forum'
  ORDER BY a.parsed_date DESC"
));

foreach ($Duplicates as $duplicate) {
  \Grepodata\Library\Model\Indexer\City::where('id', '=', $duplicate->id)->delete();
}
$t=2;


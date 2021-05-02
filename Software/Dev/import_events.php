<?php

use Carbon\Carbon;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

$aIndexes = \Grepodata\Library\Model\Indexer\IndexInfo::where('created_by_user', '>', 0)->get();
foreach ($aIndexes as $oIndex) {
  try {
    $oUser = \Grepodata\Library\Controller\User::GetUserById($oIndex->created_by_user);
    $oWorld = World::getWorldById($oIndex->world);
    $Time =  Carbon::createFromFormat('Y-m-d H:i:s', $oIndex->created_at)->setTimezone($oWorld->php_timezone);
    addEvent($oIndex, $oUser->username, $oIndex->index_name, $Time);
  } catch (Exception $e) {
    \Grepodata\Library\Logger\Logger::warning("error importing event: ".$e->getMessage());
  }
}

function addEvent($oIndex, $Username, $Teamname, $Time) {
  $oEvent = new \Grepodata\Library\Model\IndexV2\Event();
  $oEvent->world = $oIndex->world;
  $oEvent->local_time = $Time;
  $oEvent->admin_only = false;
  $oEvent->index_key = $oIndex->key_code;
  $aEvent = array(
    eventPart('text', 'User '),
    eventPart('user', $Username),
    eventPart('text', ' created team '),
    eventPart('team', $Teamname),
  );
  $oEvent->json = json_encode($aEvent);
  $oEvent->save();
}

use Illuminate\Database\Capsule\Manager as DB;
$aRoles = DB::select( DB::raw(
"SELECT `User`.`username`, Indexer_roles.index_key, World.grep_id, World.php_timezone, Indexer_info.index_name, Indexer_roles.created_at FROM Indexer_roles
LEFT JOIN Indexer_info ON Indexer_info.key_code = Indexer_roles.index_key
LEFT JOIN World ON World.grep_id = Indexer_info.world
LEFT JOIN `User` ON `User`.id = Indexer_roles.user_id
WHERE Indexer_roles.role != 'owner'"
  ));
foreach ($aRoles as $oIndexRole) {
  $oIndexRole = (array) $oIndexRole;
  $Time =  Carbon::createFromFormat('Y-m-d H:i:s', $oIndexRole['created_at'])->setTimezone($oIndexRole['php_timezone']);

  $oEvent = new \Grepodata\Library\Model\IndexV2\Event();
  $oEvent->world = $oIndexRole['grep_id'];
  $oEvent->local_time = $Time;
  $oEvent->admin_only = false;
  $oEvent->index_key = $oIndexRole['index_key'];
  $aEvent = array(
    eventPart('text', 'User '),
    eventPart('user', $oIndexRole['username']),
    eventPart('text', ' joined team '),
    eventPart('team', $oIndexRole['index_name']),
  );
  $oEvent->json = json_encode($aEvent);
  $oEvent->save();
}


function eventPart($Type = 'text', $Text = '', $Params = array())
{
  return array(
    'type' => $Type,
    'text' => $Text,
    'params' => $Params,
  );
}

$t=2;

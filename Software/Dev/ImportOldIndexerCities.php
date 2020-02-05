<?php

namespace Grepodata\Dev;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Model\Indexer\City;

require(__DIR__ . '/../config.php');

$aIndex = Common::getAllActiveIndexes();
if ($aIndex === false) {
  die();
}

foreach ($aIndex as $oIndex) {

  // Get old city records
  $aCities = \Grepodata\Dev\CityOld::where('index_id', '=', $oIndex->id)->get();
  $Size = sizeof($aCities);
  echo "Index $oIndex->key_code, world $oIndex->world, records $Size".PHP_EOL;

  foreach ($aCities as $oCityOld) {
    $result = ParseReport($oIndex->key_code, $oCityOld, $oIndex);
  }
}

function ParseReport($IndexKey, $aCityInfo, $oIndexInfo)
{
  try {
    $oPlayer = Player::firstByName($oIndexInfo->world, $aCityInfo['player_name']);
    if ($oPlayer === null) {
      return false;
    }
    $TownId = $aCityInfo['city_name'];

    $aBuildingIds = array('senate', 'wood', 'farm', 'stone', 'silver', 'baracks', 'temple', 'storage', 'trade', 'port', 'academy', 'wall', 'cave', 'special_1', 'special_2');
    $aLandUnitIds = array('sword', 'sling', 'bow', 'spear', 'caval', 'strijd', 'kata', 'gezant');
    $aMythUnitIds = array('mino', 'manti', 'sea_monster', 'harp', 'medusa', 'centaur', 'pegasus', 'cerberus', 'erinyes', 'cyclope', 'griff', 'boar');
    $aSeaUnitIds  = array('slow_tp', 'bir', 'brander', 'fast_tp', 'trireme', 'kolo');

    $aBuildings = array();
    foreach ($aBuildingIds as $Building) {
      if (isset($aCityInfo[$Building])) $aBuildings[$Building] = $aCityInfo[$Building];
    }
    $aLandUnits = array();
    foreach ($aLandUnitIds as $LandUnit) {
      if (isset($aCityInfo[$LandUnit])) $aLandUnits[$LandUnit] = $aCityInfo[$LandUnit];
    }
    $aMythUnits = array();
    foreach ($aMythUnitIds as $MythUnit) {
      if (isset($aCityInfo[$MythUnit])) $aMythUnits[$MythUnit] = $aCityInfo[$MythUnit];
    }
    $aSeaUnits = array();
    foreach ($aSeaUnitIds as $SeaUnit) {
      if (isset($aCityInfo[$SeaUnit])) $aSeaUnits[$SeaUnit] = $aCityInfo[$SeaUnit];
    }
    $Fireships = '';
    if (isset($aCityInfo['fireship'])) $Fireships = $aCityInfo['fireship'];

    $oCity = new City();
    $oCity->index_key   = $IndexKey;
    $oCity->town_id     = $TownId;
    $oCity->player_id   = $oPlayer->grep_id;
    $oCity->alliance_id = $oPlayer->alliance_id;
    $oCity->report_date = $aCityInfo['mutation_date'];
    $oCity->report_type = $aCityInfo['report_type'];
    $oCity->hero        = $aCityInfo['hero'];
    $oCity->god         = $aCityInfo['god'];
    $oCity->silver      = $aCityInfo['silver_in_cave'];
    $oCity->buildings   = json_encode($aBuildings);
    $oCity->land_units  = json_encode($aLandUnits);
    $oCity->mythical_units = json_encode($aMythUnits);
    $oCity->sea_units   = json_encode($aSeaUnits);
    $oCity->fireships   = $Fireships;

    if ($oCity->save()) {
      return $oCity->id;
    } else return 0;
  } catch (\Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'Integrity constraint violation') === false) echo $msg . PHP_EOL;
    return 0;
  }

}

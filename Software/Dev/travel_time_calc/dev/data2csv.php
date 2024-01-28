<?php
//
//use Grepodata\Library\Model\Town;
//use Grepodata\Library\Logger\Logger;
//
//if (PHP_SAPI !== 'cli') {
//  die('not allowed');
//}
//
//require(__DIR__ . '/../../config.php');
//Logger::enableDebug();
//
//$aMovementSpeed = array(
//  "sword"             => array('speed' => 8,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "slinger"           => array('speed' => 14,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "archer"            => array('speed' => 12,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "hoplite"           => array('speed' => 6,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "rider"             => array('speed' => 22,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "chariot"           => array('speed' => 18,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "catapult"          => array('speed' => 2,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//
//  "manticore"         => array('speed' => 22,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
//  "sea_monster"       => array('speed' => 8,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "harpy"             => array('speed' => 28,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
//  "pegasus"           => array('speed' => 35,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
//  "griffin"           => array('speed' => 18,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => false),
//  "minotaur"          => array('speed' => 10,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "zyklop"            => array('speed' => 8,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "medusa"            => array('speed' => 6,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "centaur"           => array('speed' => 18,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "cerberus"          => array('speed' => 4,   'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "fury"              => array('speed' => 20,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "calydonian_boar"   => array('speed' => 16,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//  "godsent"           => array('speed' => 16,  'uses_meteorology' => true, 'uses_cartography' => false, 'requires_transport' => true),
//
//  "big_transporter"   => array('speed' => 8,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "bireme"            => array('speed' => 15,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "attack_ship"       => array('speed' => 13,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "demolition_ship"   => array('speed' => 5,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "small_transporter" => array('speed' => 15,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "trireme"           => array('speed' => 15,  'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//  "colonize_ship"     => array('speed' => 3,   'uses_meteorology' => false, 'uses_cartography' => true, 'requires_transport' => false),
//);
//
//$mapdatastr = file_get_contents("MapTiles.json");
//$aMapData = json_decode($mapdatastr, true);
//
//
///** @var \Grepodata\Library\Model\Indexer\UnitInfo $UnitSpeed */
//$aRows = \Grepodata\Library\Model\Indexer\UnitInfo::
////where('world', '=', 'nl79', 'and')->
////where('world', '=', 'en123', 'and')->
//get();
//$aDataset = array();
//foreach ($aRows as $UnitSpeed) {
//  $aCsvRows = dataToCsvRow($UnitSpeed);
//
//  $aDataset = array_merge($aDataset, $aCsvRows);
//  $t=2;
//}
//$t=2;
//
//$fp = fopen('data.csv', 'w');
//
//foreach ($aDataset as $fields) {
//  fputcsv($fp, $fields);
//}
//
//fclose($fp);
//
//function dataToCsvRow(\Grepodata\Library\Model\Indexer\UnitInfo $UnitSpeed)
//{
//  global $aMovementSpeed;
//  $aRows = array();
//
//  try {
//    $aUnitData = json_decode($UnitSpeed->units, true);
//
//    if (!isset($aUnitData['same_island'])) {
//      throw new Exception("missing property: same_island");
//    }
//    $bIsSameIsland = $aUnitData['same_island'];
//    if ($bIsSameIsland !== false && $bIsSameIsland !== true) {
//      throw new Exception("invalid property: same_island");
//    }
//
//    if ($UnitSpeed->source_town == $UnitSpeed->target_town) {
//      return array();
//    }
//
//    $SourceTown = \Grepodata\Library\Controller\Town::firstOrFail($UnitSpeed->source_town, $UnitSpeed->world);
//    $TargetTown = \Grepodata\Library\Controller\Town::firstOrFail($UnitSpeed->target_town, $UnitSpeed->world);
//    $Distance = (int) getTownDistance($SourceTown, $TargetTown);
//    //$IslandDistance = (int) euclideanDistance($SourceTown->island_x, $TargetTown->island_x, $SourceTown->island_y, $TargetTown->island_y);
//    if ($Distance <= 0) {
//      throw new Exception("invalid distance: $Distance");
//    }
//
//    // Check movement modifiers
//    $bHasCartography = false;
//    $bHasMeteorology = false;
//    $bHasSetSail = false;
//    $bHasLighthouse = false;
//    $bHasMoveBoost = false;
//    if (isset($aUnitData['researches'])) {
//      $aResearches = $aUnitData['researches'];
//      if (isset($aResearches['cartography'])) {
//        $bHasCartography = true;
//      }
//      if (isset($aResearches['meteorology'])) {
//        $bHasMeteorology = true;
//      }
//    }
//
//    if (!isset($aUnitData['units'])) {
//      throw new Exception("missing property: units");
//    }
//    foreach ($aUnitData['units'] as $Unit => $Data) {
//      if (!isset($Data['duration_without_bonus'])) {
//        continue;
//      }
//      if (!isset($aMovementSpeed[$Unit])) {
//        throw new Exception("unknown unit: $Unit");
//      }
//      if ($aMovementSpeed[$Unit]['requires_transport'] == true && $bIsSameIsland == false) {
//        continue;
//      }
//
//      if (!($Unit == 'colonize_ship' || $Unit == 'big_transporter')) {
//        continue;
//      }
//
//      $BoostedFactor = 1;
//      if ($aMovementSpeed[$Unit]['uses_cartography']==true && $bHasCartography) {
//        $BoostedFactor -= .1;
//      }
//      if ($aMovementSpeed[$Unit]['uses_cartography']==true && $bHasLighthouse) {
//        $BoostedFactor -= .15;
//      }
//      if ($aMovementSpeed[$Unit]['uses_meteorology']==true && $bHasMeteorology) {
//        $BoostedFactor -= .1;
//      }
//      if ($Unit == 'colonize_ship' && $bHasSetSail) {
//        $BoostedFactor -= .1;
//      }
//      if ($bHasMoveBoost) {
//        $BoostedFactor -= .3;
//      }
//      $TravelTimeBoosted = $Data['duration_without_bonus'];
//      $TravelTimeBase = ($TravelTimeBoosted / $BoostedFactor) * 1;
//
//      $aRows[] = array(
//        'unit_speed'     => $aMovementSpeed[$Unit]['speed'] * (int) $UnitSpeed->game_speed,
//        'world_speed'    => (int) $UnitSpeed->game_speed,
//        'is_same_island' => $bIsSameIsland ? 1 : 0,
//        'distance'       => $Distance,
//        'travel_time'    => (int) $TravelTimeBase,
//        'world'          => $UnitSpeed->world,
//        'unitdata'       => $aUnitData,
//        'unitspeed'      => $UnitSpeed,
//        'raw_speed'      => $Data['duration_without_bonus'],
//      );
//      $t=2;
//    }
//  } catch (Exception $e) {
//    echo "Skipping record with id $UnitSpeed->id: " . $e->getMessage() . "\n";
//  }
//
//  foreach ($aRows as $aRow) {
//    foreach ($aRows as $aRow2) {
//      if ($aRow2['travel_time']==$aRow['travel_time'] && $aRow2['unit_speed']!=$aRow['unit_speed']) {
//        $t = '???';
//      }
//    }
//  }
//
//
//  return $aRows;
//}
//
//function getTownDistance(Town $Town1, Town $Town2) {
//  $Town1Abs = getAbsoluteCoordinates($Town1);
//  $Town2Abs = getAbsoluteCoordinates($Town2);
//  $x1 = $Town1Abs['abs_x'];
//  $x2 = $Town2Abs['abs_x'];
//  $y1 = $Town1Abs['abs_y'];
//  $y2 = $Town2Abs['abs_y'];
//  $Distance = euclideanDistance($x1, $x2, $y1, $y2);
//
//  return $Distance;
//}
//
//function getAbsoluteCoordinates(Town $Town)
//{
//  global $aMapData;
//  $oIsland = \Grepodata\Library\Controller\Island::firstByXY($Town->island_x, $Town->island_y, $Town->world);
//  if ($oIsland == false) {
//    throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Island not found: $Town->island_x, $Town->island_y, $Town->world");
//  }
//  if (!isset($oIsland->island_type) || !isset($oIsland->island_x) || !isset($aMapData[$oIsland->island_type])) {
//    throw new \Illuminate\Database\Eloquent\ModelNotFoundException("No map data for island with type: $oIsland->island_type, $Town->world");
//  }
//  $aIslandData = $aMapData[$oIsland->island_type];
//  //echo "Island: $Town->name: ".json_encode($aIslandData). "\n";
//  if (!isset($aIslandData['town_offsets']) || !isset($aIslandData['town_offsets'][$Town->island_i])) {
//    throw new \Illuminate\Database\Eloquent\ModelNotFoundException("No town offset data for town with type $Town->island_i and island type $oIsland->island_type, $Town->world");
//  }
//  $aTownOffset = $aIslandData['town_offsets'][$Town->island_i];
//  $IslandAbsX = 128 * $oIsland->island_x;
//  $IslandAbsY = 128 * $oIsland->island_y;
//  $TownAbsX = $IslandAbsX + $aTownOffset['x'];
//  $TownAbsY = $IslandAbsY + $aTownOffset['y'];
//  return array(
//    'abs_x' => $TownAbsX + 10, // 10 = default
//    'abs_y' => $TownAbsY + 10 + 64, // 10 = default, 64 = half of ytile size
//  );
//}
//
//function euclideanDistance($x1, $x2, $y1, $y2)
//{
//  return sqrt(pow(($x1 - $x2), 2) + pow(($y1 - $y2), 2));
//}
//
//class RuntimeInfo
//{
//
//  static function getGeneralModifier($bHasUnitMovementBoost = false, $UnitMovementBoost = 30)
//  {
//    // UnitMovementBoost = 30% power
//    // GameData.additional_runtime_modifier = $UnitMovementBoost
//    $Mod = 1;
//    if ($bHasUnitMovementBoost) {
//      $Mod += .01 * $UnitMovementBoost;
//    }
//    return $Mod;
//  }
//
//  static function getGroundModifier($bHasMeteorology = false, $BonusMeteorologySpeed = 0.1)
//  {
//    // Meteorology = 10% boost for land units
//    // GameData.research_bonus = $BonusMeteorologySpeed
//    $Mod = 1;
//    if ($bHasMeteorology) {
//      $Mod += $BonusMeteorologySpeed;
//    }
//    return $Mod;
//  }
//
//  static function getNavalModifier($bHasCartography = false, $bHasLighthouse = false, $BonusCartographySpeed = 0.1, $BonusLighthouseSpeed = 0.15)
//  {
//    // Meteorology = 10% boost for land units
//    // GameData.research_bonus = $BonusMeteorologySpeed
//    // GameData.additional_runtime_modifier = $BonusLighthouseSpeed
//    $Mod = 1;
//    if ($bHasCartography) {
//      $Mod += $BonusCartographySpeed;
//    }
//    if ($bHasLighthouse) {
//      $Mod += $BonusLighthouseSpeed;
//    }
//    return $Mod;
//  }
//
//}
//
//$t=2;

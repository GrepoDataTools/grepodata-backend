<?php

namespace Grepodata\Library\Controller\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\IndexV2\Intel;
use Grepodata\Library\Model\World;
use Illuminate\Database\Eloquent\Collection;

class CityInfo
{
  const land_units = array(
    'sword' => 'sword', 'sling' => 'slinger',
    'bow' => 'archer', 'spear' => 'hoplite',
    'caval' => 'rider', 'strijd' => 'chariot',
    'kata' => 'catapult', 'gezant' => 'godsent',
    'militia' => 'militia', 'unknown' => 'unknown');
  const myth_units = array(
    'sea_monster' => 'sea_monster', 'cyclope' => 'zyklop',
    'harp'      => 'harpy', 'medusa' => 'medusa',
    'mino'      => 'minotaur','manti' => 'manticore',
    'centaur'   => 'centaur', 'pegasus' => 'pegasus',
    'cerberus'  => 'cerberus', 'erinyes' => 'fury',
    'griff'     => 'griffin', 'boar' => 'calydonian_boar');
  const sea_units  = array(
    'slow_tp' => 'big_transporter', 'bir' => 'bireme',
    'attack_ship' => 'attack_ship', 'brander' => 'demolition_ship',
    'fast_tp' => 'small_transporter', 'trireme' => 'trireme',
    'kolo'    => 'colonize_ship', 'unknown_naval' => 'unknown_naval');
  const build = array(
    'senate' => 'main',
    'wood' => 'lumber',
    'farm' => 'farm',
    'stone' => 'stoner',
    'silver' => 'ironer',
    'baracks' => 'barracks',
    'temple' => 'temple',
    'storage' => 'storage',
    'trade' => 'market',
    'port' => 'docks',
    'academy' => 'academy',
    'wall' => 'wall',
    'cave' =>  'hide',
    'theater'=> 'theater',
    'badhuis' => 'thermal',
    'bibliotheek' => 'library',
    'vuurtoren' => 'lighthouse',
    'toren' => 'tower',
    'godenbeeld' => 'statue',
    'orakel' => 'oracle',
    'handelskantoor' => 'trade_office',
  );
  const fireship = 'attack_ship';
  const sea_monster = 'sea_monster';

  /**
   * @param $Key string Index identifier
   * @param $Id int Conquest identifier
   * @return City[]
   */
  public static function allByConquestId($Key, $Id)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key, 'and')
      ->where('conquest_id', '=', $Id)
      ->orderBy('parsed_date', 'desc')
      ->get();
  }

  /**
   * @param $Key string Index identifier
   * @return Collection
   */
  public static function allByKey($Key)
  {
    return \Grepodata\Library\Model\Indexer\City::where('index_key', '=', $Key)
      ->get();
  }

  /**
   * Returns a merged array of all the units in the City record
   * @param Intel $oCity
   * @return array
   */
  public static function getMergedUnits(Intel $oCity)
  {
    $aUnits = array();
    if (!empty($oCity->mythical_units) && $oCity->mythical_units != "[]") {
      self::parseUnitField($aUnits, $oCity->mythical_units);
    }
    if (!empty($oCity->land_units) && $oCity->land_units != "[]") {
      self::parseUnitField($aUnits, $oCity->land_units);
    }
    if (!empty($oCity->sea_units) && $oCity->sea_units != "[]") {
      self::parseUnitField($aUnits, $oCity->sea_units);
    }
    if (!empty($oCity->fireships) && is_string($oCity->fireships) && strlen($oCity->fireships) > 0) {
      $aUnits[self::fireship] = $oCity->fireships;
    }
    return $aUnits;
  }

  /**
   * Split land and sea units and parse their values
   * @param $aUnits
   * @return array
   */
  public static function splitLandSeaUnits($aUnits)
  {
    $aSplitUnits = array(
      'sea' => array(),
      'land' => array(),
    );
    foreach ($aUnits as $Unit => $Value) {
      if ($Unit == self::fireship || $Unit == self::sea_monster || in_array($Unit, array_values(self::sea_units))) {
        $aSplitUnits['sea'][$Unit] = $Value;
      } else {
        $aSplitUnits['land'][$Unit] = $Value;
      }
    }
    $aSplitUnits['sea'] = self::parseUnitLossCount($aSplitUnits['sea']);
    $aSplitUnits['land'] = self::parseUnitLossCount($aSplitUnits['land']);
    return $aSplitUnits;
  }

  /**
   * Adds the units from the $InputField to the $aUnits array
   * translates unit names to proper keys
   * @param $aUnits
   * @param $InputField
   */
  public static function parseUnitField(&$aUnits, $InputField)
  {
    $aInputUnits = json_decode($InputField, true);
    foreach ($aInputUnits as $Key => $Value) {
      $Key = self::land_units[$Key] ?? $Key;
      $Key = self::sea_units[$Key] ?? $Key;
      $Key = self::myth_units[$Key] ?? $Key;
      $aUnits[$Key] = $Value;
    }
  }

  /**
   * Parses unit losses. e.g. slinger:400(-200) to [count: 400, loss: 200, name: slinger, raw: 400(-200)]
   * @param $aInputUnits
   * @return array
   */
  public static function parseUnitLossCount($aInputUnits)
  {
    $aParsed = array();
    foreach ($aInputUnits as $Key => $Value) {
      $Count = 0;
      $Killed = 0;
      preg_match_all('/[0-9]{1,}/', $Value, $aValueMatches);
      if (!empty($aValueMatches) && isset($aValueMatches[0][0])) {
        $Count = (int) $aValueMatches[0][0];
        if (isset($aValueMatches[0][1])) {
          $Killed = (int) $aValueMatches[0][1];
        }
      }
      $aParsed[] = array(
        'count' => $Count,
        'killed' => $Killed,
        'name' => $Key,
        'raw' => $Value,
      );
    }
    return $aParsed;
  }
}

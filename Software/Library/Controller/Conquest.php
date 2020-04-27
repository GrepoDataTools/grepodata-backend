<?php

namespace Grepodata\Library\Controller;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Conquest
{
  static $aTownNames = array();
  static $aPlayerNames = array();
  static $aAllianceNames = array();

  /**
   * @param $Id string Town id
   * @param $World string World identifier
   * @param $Time string UTC datestring 'Y-m-d H:i:s'
   * @return \Grepodata\Library\Model\Conquest Conquest
   */
  public static function firstOrFail($Id, $World, $Time)
  {
    return \Grepodata\Library\Model\Conquest::where('town_id', '=', $Id, 'and')
      ->where('world', '=', $World, 'and')
      ->where('time', '=', $Time)
      ->firstOrFail();
  }

  /**
   * @param $Id string Town id
   * @param $World string World identifier
   * @param $Time string UTC datestring 'Y-m-d H:i:s'
   * @return \Grepodata\Library\Model\Conquest | Model Conquest
   */
  public static function firstOrNew($Id, $World, $Time)
  {
    return \Grepodata\Library\Model\Conquest::firstOrNew(array(
      'world'   => $World,
      'time'    => $Time,
      'town_id' => $Id,
    ));
  }

  /**
   * @param $Id
   * @param $World
   * @return \Illuminate\Database\Eloquent\Collection Player conquest records
   */
  public static function getPlayerConquests($Id, $World)
  {
    $New = \Grepodata\Library\Model\Conquest::where('world', '=', $World)
      ->where('n_p_id', '=', $Id)
      ->get();
    $Old = \Grepodata\Library\Model\Conquest::where('world', '=', $World)
      ->where('o_p_id', '=', $Id)
      ->get();

    /** @var Collection $Combined */
    $Combined = $New->merge($Old);
    $Combined = $Combined->SortByDesc('id');
    return $Combined;
  }

  /**
   * @param $Id
   * @param $World
   * @return \Illuminate\Database\Eloquent\Collection Alliance conquest records
   */
  public static function getAllianceConquests($Id, $World)
  {
    $New = \Grepodata\Library\Model\Conquest::where('world', '=', $World)
      ->where('n_a_id', '=', $Id)
      ->get();
    $Old = \Grepodata\Library\Model\Conquest::where('world', '=', $World)
      ->where('o_a_id', '=', $Id)
      ->get();

    /** @var Collection $Combined */
    $Combined = $New->merge($Old);
    $Combined = $Combined->SortByDesc('id');
    return $Combined;
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Conquest[]
   */
  public static function getTownConquests($Id, $World)
  {
    return \Grepodata\Library\Model\Conquest::where('town_id', '=', $Id, 'and')
      ->where('world', '=', $World)
      ->orderBy('id', 'desc')
      ->get();
  }

  public static function expandConquestRecord(\Grepodata\Library\Model\Conquest $oConquest, \Grepodata\Library\Model\World $oWorld) {
    $aConquest = $oConquest->getPublicFields();
    $aConquest['type'] = $oConquest['type'];

    // Find town name
    if (!isset(static::$aTownNames[$oConquest->town_id])) {
      try {
        $oTown = \Grepodata\Library\Controller\Town::first($oConquest->town_id, $oWorld->grep_id);
        static::$aTownNames[$oConquest->town_id] = $oTown->name;
        $aConquest['town_name'] = $oTown->name;
      } catch (ModelNotFoundException $e) {
        $aConquest['town_name'] = '';
      }
    } else {
      $aConquest['town_name'] = static::$aTownNames[$oConquest->town_id];
    }

    // Find new player
    if (!isset(static::$aPlayerNames[$oConquest->n_p_id])) {
      try {
        $oPlayer = \Grepodata\Library\Controller\Player::first($oConquest->n_p_id, $oWorld->grep_id);
        if ($oPlayer === null) throw new ModelNotFoundException();
        static::$aPlayerNames[$oConquest->n_p_id] = $oPlayer->name;
        $aConquest['n_p_name'] = $oPlayer->name;
      } catch (ModelNotFoundException $e) {
        $aConquest['n_p_name'] = '';
      }
    } else {
      $aConquest['n_p_name'] = static::$aPlayerNames[$oConquest->n_p_id];
    }

    // Find old player
    if (!isset(static::$aPlayerNames[$oConquest->o_p_id])) {
      try {
        $oPlayer = \Grepodata\Library\Controller\Player::first($oConquest->o_p_id, $oWorld->grep_id);
        if ($oPlayer === null) throw new ModelNotFoundException();
        static::$aPlayerNames[$oConquest->o_p_id] = $oPlayer->name;
        $aConquest['o_p_name'] = $oPlayer->name;
      } catch (ModelNotFoundException $e) {
        $aConquest['o_p_name'] = '';
      }
    } else {
      $aConquest['o_p_name'] = static::$aPlayerNames[$oConquest->o_p_id];
    }

    // Find new alliance
    if (!isset(static::$aAllianceNames[$oConquest->n_a_id])) {
      try {
        $oAlliance = \Grepodata\Library\Controller\Alliance::first($oConquest->n_a_id, $oWorld->grep_id);
        if ($oAlliance === null) throw new ModelNotFoundException();
        static::$aAllianceNames[$oConquest->n_a_id] = $oAlliance->name;
        $aConquest['n_a_name'] = $oAlliance->name;
      } catch (ModelNotFoundException $e) {
        $aConquest['n_a_name'] = '';
      }
    } else {
      $aConquest['n_a_name'] = static::$aAllianceNames[$oConquest->n_a_id];
    }

    // Find old alliance
    if (!isset(static::$aAllianceNames[$oConquest->o_a_id])) {
      try {
        $oAlliance = \Grepodata\Library\Controller\Alliance::first($oConquest->o_a_id, $oWorld->grep_id);
        if ($oAlliance === null) throw new ModelNotFoundException();
        static::$aAllianceNames[$oConquest->o_a_id] = $oAlliance->name;
        $aConquest['o_a_name'] = $oAlliance->name;
      } catch (ModelNotFoundException $e) {
        $aConquest['o_a_name'] = '';
      }
    } else {
      $aConquest['o_a_name'] = static::$aAllianceNames[$oConquest->o_a_id];
    }

    // Parse time from UTC to server timezone
    try {
      $Time = $aConquest['time'];
      $Time = World::utcTimestampToServerTime($oWorld, strtotime($Time));
      $Time = $Time->format('Y-m-d H:i:s');
      $aConquest['time'] = $Time;
    } catch (Exception $e) {}

    return $aConquest;
  }

}
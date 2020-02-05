<?php

namespace Grepodata\Application\API\Route;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Conquest extends \Grepodata\Library\Router\BaseRoute
{
  public static function AllConquestsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('type', 'world', 'id', 'tfriendly', 'tinternal', 'tenemy', 'city', 'date', 'new_owner', 'old_owner'));

      if (isset($aParams['from'])) $From = $aParams['from']; else $From = 0;
      if (isset($aParams['size'])) $Size = $aParams['size']; else $Size = 15;

      if ($Size > 100) {
        die(self::OutputJson(array(
          'message'     => 'Invalid size.',
          'parameters'  => $aParams
        ), 404));
      }
      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);

      // Check if filters are empty
      $bFiltersEmpty = true;
      if ($aParams['city'] != '_' || $aParams['old_owner'] != '_' || $aParams['new_owner'] != '_') {
        $bFiltersEmpty = false;
      }
      if ($aParams['date']!='_') {
        try {
          // Build UTC date filter
          $oDate = Carbon::parse($aParams['date'], $oWorld->php_timezone);
          $oDate->setTimezone('UTC');
          $DateLowerLimit = $oDate->copy();
          $oDate->addHours(24);
          $DateUpperLimit = $oDate->copy();
        } catch (\Exception $e) {
          $aParams['date'] = null;
        }
      } else {
        $aParams['date'] = null;
      }

      // Find model
      $bPlayerSearch = false;
      $bAllianceSearch = false;
      $bTownSearch = false;
      switch ($aParams['type']) {
        case 'player':
          $aAllConquests = \Grepodata\Library\Controller\Conquest::getPlayerConquests($aParams['id'], $aParams['world']);
          $bPlayerSearch = true;
          break;
        case 'alliance':
          $aAllConquests = \Grepodata\Library\Controller\Conquest::getAllianceConquests($aParams['id'], $aParams['world']);
          $bAllianceSearch = true;
          break;
        case 'town':
          $aAllConquests = \Grepodata\Library\Controller\Conquest::getTownConquests($aParams['id'], $aParams['world']);
          $bTownSearch = true;
          break;
        default:
          die(self::OutputJson(array(
            'message'     => 'Invalid type in params.',
            'parameters'  => $aParams
          ), 404));
      }

      // Type toggles first
      $aToggledConquests = array();
      /** @var \Grepodata\Library\Model\Conquest $oConquest */
      foreach ($aAllConquests as $oConquest) {
        // Check type
        $Type = '';
        if ($bPlayerSearch) {
          if ($oConquest->n_a_id === $oConquest->o_a_id) $Type = 'internal';
          else if ($aParams['id'] == $oConquest->n_p_id) $Type = 'friendly';
          else if ($aParams['id'] == $oConquest->o_p_id) $Type = 'enemy';
        } else if ($bAllianceSearch) {
          if ($oConquest->n_a_id === $oConquest->o_a_id) $Type = 'internal';
          else if ($aParams['id'] == $oConquest->n_a_id) $Type = 'friendly';
          else if ($aParams['id'] == $oConquest->o_a_id) $Type = 'enemy';
        } else {
          if ($oConquest->n_a_id === $oConquest->o_a_id) $Type = 'internal';
          else $Type = 'friendly';
        }

        if (($aParams['tfriendly'] == 'true' && $Type == 'friendly')
          || ($aParams['tinternal'] == 'true' && $Type == 'internal')
          || ($aParams['tenemy'] == 'true' && $Type == 'enemy')) {
          $bDateFilter = true;
          if (!is_null($aParams['date']) && isset($DateUpperLimit) && isset($DateLowerLimit)) {
            $Conqdate = Carbon::createFromFormat('Y-m-d H:i:s', $oConquest->time);
            if ($DateLowerLimit > $Conqdate || $Conqdate > $DateUpperLimit) {
              $bDateFilter = false;
            }
          }
          if ($bDateFilter) {
            $oConquest['type'] = $Type;
            $aToggledConquests[] = $oConquest;
          }
        }
      }

      $aResponse = array(
        'count' => sizeof($aAllConquests),
        'conq'  => array()
      );
      $j=0;
      $i=0;
      $expanded=0;
      $count = 0;
      foreach ($aToggledConquests as $oConquest) {
        $bFilterMatch = true;
        if (!$bFiltersEmpty && $i < $Size && $expanded < (1000 + $i * 200)) {
          $expanded++;
          $aConquest = \Grepodata\Library\Controller\Conquest::expandConquestRecord($oConquest, $oWorld);
          $bFilterMatch = self::checkFilters($aConquest, $aParams);
        }
        if ($bFilterMatch || $bFiltersEmpty) {
          $j++;
          $count++;
        }
        if ($j >= $From && $i < $Size && $bFilterMatch && $expanded < (1000 + $i * 200)) {
          if ($bFiltersEmpty) $aConquest = \Grepodata\Library\Controller\Conquest::expandConquestRecord($oConquest, $oWorld);
          $aResponse['conq'][] = $aConquest;
          $i++;
        }
      }

      $aResponse['count'] = $count;
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No conquests found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * Returns false if the $aConquest object matches any of the $aFilters
   * @param $aConquest
   * @param $aFilters
   * @return bool
   */
  private static function checkFilters($aConquest, $aFilters)
  {
    // City filter
    if (isset($aFilters['city']) && $aFilters['city']!='_') {
      if (strpos(strtolower($aConquest['town_name']), strtolower($aFilters['city'])) === false) {
        return false;
      }
    }

    // Old player filter
    if (isset($aFilters['old_owner']) && $aFilters['old_owner']!='_') {
      if (strpos(strtolower($aConquest['o_p_name']), strtolower($aFilters['old_owner'])) === false
        && strpos(strtolower($aConquest['o_a_name']), strtolower($aFilters['old_owner'])) === false
        && !($aConquest['o_p_id']==0 && strpos('ghost city spook', strtolower($aFilters['old_owner'])) !== false)) {
        return false;
      }
    }

    // New player filter
    if (isset($aFilters['new_owner']) && $aFilters['new_owner']!='_') {
      if (strpos(strtolower($aConquest['n_p_name']), strtolower($aFilters['new_owner'])) === false
        && strpos(strtolower($aConquest['n_a_name']), strtolower($aFilters['new_owner'])) === false) {
        return false;
      }
    }

    return true;
  }
}
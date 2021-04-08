<?php

namespace Grepodata\Application\API\Route\Indexer;

use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\IndexV2\IndexOverview;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;

class DeprecatedRoutesV1 extends \Grepodata\Library\Router\BaseRoute
{
  // === \Index
  public static function NewKeyRequestGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function CleanupRequestGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function ResetIndexOwnersGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function ForgotKeysRequestGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function ConfirmActionGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function NewIndexV1GET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function IsValidGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function GetIndexV1GET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }

  // === \Browse
  public static function GetTownGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function GetPlayerGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function GetAllianceGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }

  // === \Owners
  public static function ResetOwnersGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function ExcludeAllianceGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function IncludeAllianceGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }

  // === \IndexApi
  public static function DeleteGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function DeleteUndoGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function CalculateRuntimePOST()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function AddNoteGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }
  public static function DeleteNoteGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
  }

  // === \Report
  public static function LatestReportHashesGET()
  {
    die(self::OutputJson(array(
      'i' => array(),
      'f' => array(),
      'deprecated' => true
    ), 200));
  }
  public static function AddReportFromInboxPOST()
  {
    die(self::OutputJson(array(), 200));
  }
  public static function AddReportFromForumPOST()
  {
    die(self::OutputJson(array(), 200));
  }

  // === \Reporting
  public static function BugReportDeprecatedPOST()
  {
    die(self::OutputJson(array('success' => true), 200));
  }

  // === \IndexApi (siege routes)


  public static function GetConquestReportsGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
    // TODO: add conquests to V2
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array());

      // Find main objects using uid
      if (isset($aParams['uid'])) {
        $oConquest = Conquest::firstByUid($aParams['uid']);
        $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($oConquest->index_key);
        $oWorld = World::getWorldById($oIndex->world);
      } else if (isset($aParams['conquest_id']) && isset($aParams['key'])) {
        // find objects using real id + key, iff key is valid

        // Validate key
        $oIndex = Validator::IsValidIndex($aParams['key']);
        if ($oIndex === null || $oIndex === false) {
          die(self::OutputJson(array(
            'message'     => 'Unauthorized index key.',
          ), 401));
        }
        $oConquest = Conquest::firstOrFail($aParams['conquest_id']);
        $oWorld = World::getWorldById($oIndex->world);
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: uid OR (conquest_id AND key)'
        ), 400));
      }

      // format response
      $aResponse = array(
        'world' => $oWorld->grep_id,
        'conquest' => $oConquest->getPublicFields($oWorld),
        'intel' => array()
      );

      $bHideRemainingDefences = false;
      if (isset($aResponse['conquest']['hide_details']) && $aResponse['conquest']['hide_details'] == true) {
        $bHideRemainingDefences = true;
      }

      // Find reports
      $aReports = CityInfo::allByConquestId($oIndex->key_code, $oConquest->id);
      if (empty($aReports) || count($aReports) <= 0) {
        return self::OutputJson($aResponse);
      }

      foreach ($aReports as $oCity) {
        $aConqDetails = json_decode($oCity->conquest_details, true);
        $aAttOnConq = array(
          'date' => $oCity->parsed_date,
          'attacker' => array(
            'town_id' => $oCity->town_id,
            'town_name' => $oCity->town_name,
            'player_id' => $oCity->player_id,
            'player_name' => $oCity->player_name,
            'alliance_id' => $oCity->alliance_id,
            'attack_type' => 'attack',
            'friendly' => false,
            'units' => CityInfo::parseUnitLossCount(CityInfo::getMergedUnits($oCity)),
          ),
          'defender' => array(
            'units' => array(),
            'wall' => $aConqDetails['wall'] ?? 0,
            'hidden' => $bHideRemainingDefences
          )
        );

        if (!empty($aAttOnConq['attacker']['units'])) {
          foreach ($aAttOnConq['attacker']['units'] as $aUnit) {
            if (isset($aUnit['name']) && (
                in_array($aUnit['name'], CityInfo::sea_units) ||
                $aUnit['name'] == CityInfo::sea_monster)) {
              $aAttOnConq['attacker']['attack_type'] = 'sea_attack';
              break;
            }
          }
        }

        if ($bHideRemainingDefences == false) {
          $aAttOnConq['defender']['units'] = CityInfo::splitLandSeaUnits($aConqDetails['siege_units']) ?? array();
        }

        $aResponse['intel'][] = $aAttOnConq;
      }

      try {
        // Hide owner intel
        $aOwners = IndexOverview::getOwnerAllianceIds($oIndex->key_code);
        if (!empty($aResponse['intel'])) {
          foreach ($aResponse['intel'] as $Id => $aAttOnConq) {
            if (isset($aAttOnConq['attacker']['alliance_id']) && $aAttOnConq['attacker']['alliance_id'] > 0) {
              if (in_array($aAttOnConq['attacker']['alliance_id'], $aOwners)) {
                // Friendly intel is hidden
                $aResponse['intel'][$Id]['attacker']['town_id'] = 0;
                $aResponse['intel'][$Id]['attacker']['town_name'] = '';
                $aResponse['intel'][$Id]['attacker']['friendly'] = true;
              }
            }
          }
        }
      } catch (Exception $e) {}

      return self::OutputJson($aResponse);
    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No conquests found with this uid.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetSiegelistGET()
  {
    die(self::OutputJson(array('deprecated' => true), 200));
    // TODO: add conquests to V2
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('key'));

      // Validate key
      $SearchKey = $aParams['key'];
      $bValidIndex = false;
      $oIndex = null;
      $Attempts = 0;
      while (!$bValidIndex && $Attempts <= 30) {
        $Attempts += 1;
        $oIndex = Validator::IsValidIndex($SearchKey);
        if ($oIndex === null || $oIndex === false) {
          die(self::OutputJson(array(
            'message'     => 'Unauthorized index key. Please enter the correct index key.',
          ), 401));
        }
        if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
          $SearchKey = $oIndex->moved_to_index; // redirect to new index
        } else {
          $bValidIndex = true;
        }
      }

      $From = 0;
      $Limit = 300;
      if (isset($aParams['from']) && is_numeric($aParams['from'])) {
        $From = $aParams['from'];
      }
      if (isset($aParams['limit']) && is_numeric($aParams['limit'])) {
        $Limit = $aParams['limit'];
      }

      // Find sieges
      $oWorld = World::getWorldById($oIndex->world);
      $aSieges = Conquest::allByIndex($oIndex, $From, $Limit);
      if (empty($aSieges) || count($aSieges) <= 0) {
        throw new Exception("No reports found for this conquest");
      }

      // format response
      $aResponse = array('sieges' => array());
      foreach ($aSieges as $oConquest) {
        $aResponse['sieges'][] = $oConquest->getPublicFields($oWorld);
      }

      return self::OutputJson($aResponse);
    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No sieges found in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }
}

<?php

namespace Grepodata\Application\API\Route\Indexer;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\CityInfo;
use Grepodata\Library\Controller\Indexer\Conquest;
use Grepodata\Library\Controller\Indexer\IndexOverview;
use Grepodata\Library\Controller\Indexer\Notes;
use Grepodata\Library\Controller\Player;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndexApi extends \Grepodata\Library\Router\BaseRoute
{
  public static function DeleteGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('csa', 'key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Index has moved!'
        ), 200));
      }

      // Validate CSA
      if (!isset($oIndex->csa) || $oIndex->csa == null || $oIndex->csa == '' || $oIndex->csa != $aParams['csa']) {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Invalid csa token'
        ), 200));
      }

      // Set soft delete
      $oCity = CityInfo::getById($aParams['key'], $aParams['id']);
      $oCity->soft_deleted = Carbon::now();
      $oCity->save();

      return self::OutputJson(array('success' => true));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function DeleteUndoGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('csa', 'key', 'id'));

      // Validate index
      $oIndex = Validator::IsValidIndex($aParams['key']);
      if ($oIndex === null || $oIndex === false) {
        die(self::OutputJson(array(
          'message'     => 'Unauthorized index key.',
        ), 401));
      }
      if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Index has moved!'
        ), 200));
      }

      // Validate CSA
      if (!isset($oIndex->csa) || $oIndex->csa == null || $oIndex->csa == '' || $oIndex->csa != $aParams['csa']) {
        die(self::OutputJson(array(
          'success' => false,
          'message' => 'Invalid csa token'
        ), 200));
      }

      // Undo soft delete
      $oCity = CityInfo::getById($aParams['key'], $aParams['id']);
      $oCity->soft_deleted = null;
      $oCity->save();

      return self::OutputJson(array('success' => true));

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found for this id.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function CalculateRuntimePOST()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('units', 'world', 'speed'));
      die(self::OutputJson(array('speed' => $aParams['speed'], 'world' => $aParams['world']), 200));

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message' => 'Unable to load unit runtime.',
        'parameters' => $aParams
      ), 404));
    }
  }

  public static function AddNoteGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('town_id', 'poster_name', 'poster_id', 'message'));

      $aInputKeys = array();
      if (isset($aParams['key'])) {
        $aInputKeys[] = $aParams['key'];
      } else if (isset($aParams['keys'])) {
        $aKeys = json_decode($aParams['keys']);
        $aInputKeys = $aKeys;
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: key or keys'
        ), 400));
      }

      /** @var IndexInfo[] $aIndexList */
      $aIndexList = array();
      foreach ($aInputKeys as $Key) {
        // Validate indexes with rerouting
        $SearchKey = $Key;
        $bValidIndex = false;
        $oIndex = null;
        $Attempts = 0;
        while (!$bValidIndex && $Attempts <= 30) {
          $Attempts += 1;
          $oIndex = Validator::IsValidIndex($SearchKey);
          if ($oIndex === null || $oIndex === false) {
            die(self::OutputJson(array(
              'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
            ), 401));
          }
          if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
            $SearchKey = $oIndex->moved_to_index; // redirect to new index
          } else {
            $bValidIndex = true;
          }
        }
        $aIndexList[] = $oIndex;
      }
      $PrimaryIndex = $aIndexList[0];
      $oWorld = World::getWorldById($PrimaryIndex->world);

      // Save note to each index
      $PrimaryId = 0;
      foreach ($aIndexList as $oIndex) {
        $oNote = new \Grepodata\Library\Model\Indexer\Notes();
        $oNote->index_key = $oIndex->key_code;
        $oNote->town_id = $aParams['town_id'];
        $oNote->poster_id = $aParams['poster_id'];
        $oNote->poster_name = $aParams['poster_name'];
        $oNote->message = json_encode(substr($aParams['message'], 0, 500));
        if ($PrimaryId != 0) {
          $oNote->note_id = $PrimaryId;
        }
        $oNote->save();
        if ($PrimaryId == 0) {
          $PrimaryId = $oNote->id;
          $oNote->note_id = $PrimaryId;
          $oNote->save();
        }
      }

      $aInsertedNote = array();
      if (isset($oNote)) {
        $aInsertedNote = $oNote->getPublicFields();
        $Created = $oNote->created_at;
        $Created->setTimezone($oWorld->php_timezone);
        $aInsertedNote['date'] = $Created->format('d-m-y H:i');
      }

      return self::OutputJson(array(
        'success' => true,
        'note' => $aInsertedNote
      ));

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'Unable to add note.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function DeleteNoteGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams();

      $aInputKeys = array();
      if (isset($aParams['key'])) {
        $aInputKeys[] = $aParams['key'];
      } else if (isset($aParams['keys'])) {
        $aKeys = json_decode($aParams['keys']);
        $aInputKeys = $aKeys;
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: key or keys'
        ), 400));
      }

      if (isset($aParams['note_id'])) {
        // Delete by note id
        $aNotes = Notes::allByKeysByNoteId($aInputKeys, $aParams['note_id']);
        if (sizeof($aNotes) > 10) {
          Logger::error("Note delete mismatch.  keys: " . json_encode($aInputKeys) . ", note_id: " . json_encode($aParams['note_id']));
          throw new Exception("Note delete count mismatch");
        }
        foreach ($aNotes as $oNote) {
          $bDelete = false;
          if (isset($aParams['poster_name']) && $oNote->poster_name == $aParams['poster_name']) {
            $bDelete = true;
          } elseif (isset($aParams['csa'])) {
            // Validate csa
            $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::first($oNote->index_key);
            if ($oIndex == false || !isset($oIndex->csa) || $oIndex->csa == null || $oIndex->csa == '' || $oIndex->csa != $aParams['csa']) {
              die(self::OutputJson(array(
                'success' => false,
                'message' => 'Invalid csa token'
              ), 200));
            } else {
              $bDelete = true;
            }
          }

          if ($bDelete == true)  {
            $oNote->delete();
          }
        }
      }
      else if (isset($aParams['date']) && isset($aParams['poster_name']) && isset($aParams['message'])) {
        // Old delete method
        $PrimaryIndex = $aInputKeys[0];
        $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::first($PrimaryIndex);
        $oWorld = World::getWorldById($oIndex->world);

        // Find notes
        $aNotes = Notes::allByKeysByPoster($aInputKeys, $aParams['poster_name']);

        $msg = json_encode($aParams['message']);
        foreach ($aNotes as $oNote) {
          $Created = $oNote->created_at;
          $Created->setTimezone($oWorld->php_timezone);
          if ($Created->format('d-m-y H:i') == $aParams['date'] && $oNote->message == $msg) {
            $oNote->delete();
          }
        }
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: note_id OR date,poster_name,message'
        ), 400));
      }

      return self::OutputJson(array('success' => true));

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'Unable to delete note.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetConquestReportsGET()
  {
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
            'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
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

  public static function GetTownGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams();

      $aInputKeys = array();
      if (isset($aParams['key'])) {
        $aInputKeys[] = $aParams['key'];
      } else if (isset($aParams['keys'])) {
        $aKeys = json_decode($aParams['keys']);
        $aInputKeys = $aKeys;
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: key or keys'
        ), 400));
      }

      /** @var IndexInfo[] $aIndexList */
      $aIndexList = array();
      foreach ($aInputKeys as $Key) {
        // Validate indexes with rerouting
        $SearchKey = $Key;
        $bValidIndex = false;
        $oIndex = null;
        $Attempts = 0;
        while (!$bValidIndex && $Attempts <= 30) {
          $Attempts += 1;
          $oIndex = Validator::IsValidIndex($SearchKey);
          if ($oIndex === null || $oIndex === false) {
            die(self::OutputJson(array(
              'message'     => 'Unauthorized index key. Please enter the correct index key. You will be banned after 10 incorrect attempts.',
            ), 401));
          }
          if (isset($oIndex->moved_to_index) && $oIndex->moved_to_index !== null && $oIndex->moved_to_index != '') {
            if ($oIndex->moved_to_index == 'deleted1') {
              die(self::OutputJson(array(
                'moved'       => true,
                'message'     => 'Index was deleted'
              ), 200));
            }
            $SearchKey = $oIndex->moved_to_index; // redirect to new index
          } else {
            $bValidIndex = true;
          }
        }
        $aIndexList[] = $oIndex;
      }
      $oPrimaryIndex = $aIndexList[0];

      // World
      /** @var \Grepodata\Library\Model\World $oWorld */
      $oWorld = World::getWorldById($oPrimaryIndex->world);

      $TownId = 0;
      if (isset($aParams['id']) && $aParams['id'] != '') {
        // Get by id
        $TownId = $aParams['id'];
      } elseif (isset($aParams['name']) && $aParams['name'] != '') {
        // Get by name
        $aTowns = CityInfo::allByName($oPrimaryIndex->key_code, $aParams['name']);
        if (!is_null($aTowns) && sizeof($aTowns) > 0) {
          /** @var City $oCity */
          $oCity = $aTowns[0];
          $TownId = $oCity->town_id;
        } else {
          throw new ModelNotFoundException();
        }
      } else {
        die(self::OutputJson(array(
          'message' => 'Bad request! Invalid or missing fields.',
          'fields'  => 'Missing: id or name'
        ), 400));
      }

      // Get town
      $oTown = Town::firstById($oWorld->grep_id, $TownId);
      if ($oTown == false || is_null($oTown)) {
        throw new ModelNotFoundException();
      }

      // Find cities
      $aRawKeys = array();
      foreach ($aIndexList as $oIndex) {
        $aRawKeys[] = $oIndex->key_code;
      }
      $aCities = CityInfo::allByTownIdByKeys($aRawKeys, $TownId);

      // Parse cities
      $oNow = Carbon::now();
      $aResponse = array(
        'world' => $oWorld->grep_id,
        'town_id' => $TownId,
        'name' => $oTown->name,
        'ix' => $oTown->island_x,
        'iy' => $oTown->island_y,
        'player_id' => $oTown->player_id,
        'alliance_id' => 0,
        'player_name' => '',
        'has_stonehail' => false,
        'notes' => array(),
        'buildings' => array(),
        'intel' => array(),
        'latest_version' => $oPrimaryIndex->script_version,
        'update_message' => USERSCRIPT_UPDATE_INFO,
      );
      $bHasIntel = false;
      $aDuplicateCheck = array();
      /** @var City $oCity */
      foreach ($aCities as $oCity) {
        if ($oCity->soft_deleted != null) {
          $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
          if ($oNow->diffInHours($oSoftDeleted) > 24) {
            continue;
          }
        }
        $bHasIntel = true;

        // Override newest info
        $aResponse['player_id'] = $oCity->player_id;
        $aResponse['player_name'] = $oCity->player_name;
        $aResponse['alliance_id'] = $oCity->alliance_id;
        $aResponse['name'] = $oCity->town_name;

        //$citystring = "_".$oCity->town_id.$oCity->parsed_date.$oCity->land_units.$oCity->sea_units.$oCity->mythical_units.$oCity->fireships.$oCity->buildings;
        $citystring = "_".$oCity->town_id.$oCity->parsed_date;
        $cityhash = md5($citystring);
        if (!in_array($cityhash, $aDuplicateCheck)) {
          $aDuplicateCheck[] = $cityhash;

          $aRecord = CityInfo::formatAsTownIntel($oCity, $oWorld, $aResponse['buildings']);
          if (!empty($aRecord['stonehail'])) {
            $aResponse['has_stonehail'] = true;
          }

          $aResponse['intel'][] = $aRecord;
        }
      }
      $aResponse['has_intel'] = $bHasIntel;

      if ($bHasIntel == false) {
        if ($oTown->player_id > 0) {
          $oPlayer = Player::firstById($oPrimaryIndex->world, $oTown->player_id);
          if ($oPlayer !== false) {
            $aResponse['player_name'] = $oPlayer->name;
            $aResponse['alliance_id'] = $oPlayer->alliance_id;
          }
        }
      }

      // Find notes
      $aNotes = Notes::allByTownIdByKeys($aRawKeys, $TownId);
      $aDuplicates = array();
      foreach ($aNotes as $Note) {
        $aNote = $Note->getPublicFields();
        $Created = $Note->created_at;
        $Created->setTimezone($oWorld->php_timezone);
        $aNote['date'] = $Created->format('d-m-y H:i');
        if (!in_array($Note->note_id, $aDuplicates)) {
          $aResponse['notes'][] = $aNote;
          $aDuplicates[] = $Note->note_id;
        }
      }

      try {
        // Hide owner intel
        $aOwners = IndexOverview::getOwnerAllianceIds($oPrimaryIndex->key_code);
        if (isset($aResponse['alliance_id']) && $aResponse['alliance_id']!==null) {
          if (in_array($aResponse['alliance_id'], $aOwners)) {
            $aResponse['intel'] = array();
            $aResponse['hidden_owner_intel'] = true;
          }
        }
      } catch (Exception $e) {}

      // Sort intel by sort_date descending
      //$aResponse['intel'] = array_reverse($aResponse['intel']);
      usort($aResponse['intel'], function ($a, $b) {
        return ($a['sort_date'] > $b['sort_date']) ? -1 : 1;
      });

      // Give newest record a cost boost
      if (sizeof($aResponse['intel'])>0) {
        $aResponse['intel'][0]['cost'] *= 5;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }



}
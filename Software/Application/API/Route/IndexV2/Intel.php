<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Alliance;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\Town;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Indexer\Validator;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\IndexV2\Conquest;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Intel extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * API route: /indexer/v2/userintel
   * Method: GET
   * Returns all intel collected by this user, ordered by index date
   */
  public static function GetIntelForUserGET()
  {
    try {
      $aParams = self::validateParams(array('access_token'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], true, false, true);

      $From = $aParams['from'] ?? 0;
      $Size = $aParams['size'] ?? 20;

      if ($From > 2000 || $Size > 100) {
        throw new ModelNotFoundException();
      }
      $Start = round(microtime(true) * 1000);
      $aIntelResult = \Grepodata\Library\Controller\IndexV2\Intel::allByUser($oUser, $From, $Size);
      $aIntel = $aIntelResult[0];
      $Total = $aIntelResult[1];
      $ElapsedMs = round(microtime(true) * 1000) - $Start;

      if ($ElapsedMs > 1000) {
        Logger::warning("Slow query warning: GetIntelForUserGet > ".$ElapsedMs."ms > ".$oUser->id);
      }

//      $aRoles = Roles::allByUser($oUser);
//      $aUserKeyList = array();
//      foreach ($aRoles as $oRole) {
//        if ($oRole->contribute == 1) {
//          $aUserKeyList[] = $oRole->index_key;
//        }
//      }

      $aIntelData = array();
      $aWorlds = array();
      foreach ($aIntel as $oIntel) {
        try {
          if (!key_exists($oIntel->world, $aWorlds)) {
            $aWorlds[$oIntel->world] = World::getWorldById($oIntel->world);
          }
          $aBuildings = array();
          $aTownIntelRecord = \Grepodata\Library\Controller\IndexV2\Intel::formatAsTownIntel($oIntel, $aWorlds[$oIntel->world], $aBuildings);
          $aTownIntelRecord['source_type'] = $oIntel->source_type;
          $aTownIntelRecord['parsed'] = ($oIntel->parsing_failed==0?true:false); // Is the report parsed?
          $aTownIntelRecord['parsed_err'] = ($oIntel->parsing_error==0?false:true); // Was there a parsing error?
          $aTownIntelRecord['parsed_msg'] = ($oIntel->parsing_failed==0?'':$oIntel->debug_explain); // Reason for not parsing
          $aTownIntelRecord['world'] = $oIntel->world;
          $aTownIntelRecord['town_id'] = $oIntel->town_id;
          $aTownIntelRecord['town_name'] = $oIntel->town_name;
          $aTownIntelRecord['player_id'] = $oIntel->player_id;
          $aTownIntelRecord['player_name'] = $oIntel->player_name;
          $aTownIntelRecord['alliance_id'] = $oIntel->alliance_id;
          $aTownIntelRecord['alliance_name'] = Alliance::first($oIntel->alliance_id, $oIntel->world)->name ?? '';
//          if (isset($oIntel['shared_via_indexes'])) {
//             $aTeamsFiltered = array();
//             $aTargets = explode(", ", $oIntel['shared_via_indexes']);
//             foreach ($aTargets as $IndexKey) {
//               if (in_array($IndexKey, $aUserKeyList)) {
//                 $aTeamsFiltered[] = $IndexKey;
//               }
//             }
//            $aTownIntelRecord['shared_via_indexes'] = join(", ", $aTeamsFiltered);
//          }
          $aIntelData[] = $aTownIntelRecord;
        } catch (\Exception $e) {
          Logger::warning("Unable to render user intel record: " . $e->getMessage());
        }
      }

      $aResponse = array(
        'query_ms'    => $ElapsedMs,
        'batch_size'  => sizeof($aIntel),
        'total_items' => $Total,
        'items'       => $aIntelData
      );

      ResponseCode::success($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * API route: /indexer/v2/export
   * Method: GET
   * This route is for developers only. Due to the high resource demand, you need a dev_token to call this route.
   * If you want to use this route, please contact admin@grepodata.com to get a dev_token
   * @throws Exception
   */
  public static function GetAllForIndexGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));

      if (!key_exists('dev_token', $aParams)) {
        die(self::OutputJson(array(
          'message'     => 'Missing parameter: dev_token. This route requires a dev_token. Please contact admin@grepodata.com to get one.'
        ), 403));
      }

      if ($aParams['dev_token'] !== PRIVATE_DEV_TOKEN) {
        die(self::OutputJson(array(
          'message'     => 'dev_token invalid/expired. Please contact admin@grepodata.com to get a new dev_token.'
        ), 403));
      }

      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Validate index key
      $oIndex = Validator::IsValidIndex($aParams['index_key']);
      if ($oIndex === null || $oIndex === false) {
        ResponseCode::errorCode(7101, array());
      }
      $oWorld = World::getWorldById($oIndex->world);

      // Check if user is allowed to see intel
      IndexManagement::verifyUserCanRead($oUser, $oIndex->key_code);

      ini_set('memory_limit','1G');

      // Get intel
      $aCursor = \Grepodata\Library\Controller\IndexV2\Intel::indexCursor($oIndex, true);

      $aOutput = array();
      $aBuildings = array();
      foreach ($aCursor as $oIntel) {
//        $aOutput[] = $oIntel->getMinimalFields();
        $aIntel = \Grepodata\Library\Controller\IndexV2\Intel::formatAsTownIntel($oIntel, $oWorld, $aBuildings);
        $aIntel['town_id'] = $oIntel->town_id;
        $aIntel['town_name'] = $oIntel->town_name;
        $aIntel['player_id'] = $oIntel->player_id;
        $aIntel['player_name'] = $oIntel->player_name;
        $aIntel['alliance_id'] = $oIntel->alliance_id;
        $aOutput[] = $aIntel;
      }

      die(self::OutputJson($aOutput));
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetTownGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'world', 'town_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token'], true, false, true);

      // get world
      $oWorld = World::getWorldById($aParams['world']);

      // get town
      $oTown = null;
      $TownId = $aParams['town_id'];
      try {
        $oTown = Town::firstOrFail($TownId, $oWorld->grep_id);
      } catch (Exception $e) {}

      // get intel
      $Start = round(microtime(true) * 1000);
      $aIntel = \Grepodata\Library\Controller\IndexV2\Intel::allByUserForTown($oUser, $oWorld->grep_id, $TownId, true);
      $ElapsedMs = round(microtime(true) * 1000) - $Start;

      // Parse cities
      $oNow = Carbon::now();
      $aResponse = array(
        'query_ms' => $ElapsedMs,
        'world' => $oWorld->grep_id,
        'town_id' => $TownId,
        'name' => $oTown ? $oTown->name : '',
        'ix' => $oTown ? $oTown->island_x : 0,
        'iy' => $oTown ? $oTown->island_y : 0,
        'player_id' => $oTown ? $oTown->player_id : 0,
        'alliance_id' => 0,
        'player_name' => '',
        'alliance_name' => '',
        'has_stonehail' => false,
        'notes' => array(),
        'buildings' => array(),
        'intel' => array(),
        'teams' => array(),
        'latest_version' => USERSCRIPT_VERSION,
        'update_message' => USERSCRIPT_UPDATE_INFO,
      );
      $bHasIntel = false;
      $aDuplicateCheck = array();
      /** @var \Grepodata\Library\Model\IndexV2\Intel $oCity */
      foreach ($aIntel as $oCity) {
        if ($oCity->soft_deleted != null) {
          $oSoftDeleted = Carbon::parse($oCity->soft_deleted);
          if ($oNow->diffInHours($oSoftDeleted) > 24) {
            continue;
          }
        }
        $bHasIntel = true;

        // add teams to set
        if ($oCity->shared_via_indexes!=null) {
          $aTeams = explode(', ', $oCity->shared_via_indexes);
          foreach ($aTeams as $Team) {
            if (!in_array($Team, $aResponse['teams'])) {
              $aResponse['teams'][] = $Team;
            }
          }
        }

        // Override response with newest info
        $aResponse['player_id'] = $oCity->player_id;
        $aResponse['player_name'] = $oCity->player_name;
        $aResponse['alliance_id'] = $oCity->alliance_id;
        $aResponse['name'] = $oCity->town_name;
        $aResponse['latest_version'] = $oCity->script_version;

        $citystring = "_".$oCity->town_id.$oCity->parsed_date.$oCity->luck;
        $cityhash = md5($citystring);
        if (!in_array($cityhash, $aDuplicateCheck)) {
          $aDuplicateCheck[] = $cityhash;

          $aRecord = \Grepodata\Library\Controller\IndexV2\Intel::formatAsTownIntel($oCity, $oWorld, $aResponse['buildings']);
          if (!empty($aRecord['stonehail'])) {
            $aResponse['has_stonehail'] = true;
          }

          if (isset($oCity['shared_via_indexes'])) {
            $aRecord['shared_via_indexes'] = $oCity['shared_via_indexes'];
          }
          if (isset($oCity['indexed_by_users'])) {
            $aRecord['indexed_by_users'] = $oCity['indexed_by_users'];
          }
          if (isset($oCity['is_previous_owner_intel'])) {
            $aRecord['is_previous_owner_intel'] = $oCity['is_previous_owner_intel']==1;
          }

          $aResponse['intel'][] = $aRecord;
        }
      }
      $aResponse['has_intel'] = $bHasIntel;

      if ($bHasIntel == false) {
        if ($oTown && $oTown->player_id > 0) {
          $oPlayer = \Grepodata\Library\Controller\Player::firstById($oWorld->grep_id, $oTown->player_id);
          if ($oPlayer) {
            $aResponse['player_name'] = $oPlayer->name;
            $aResponse['alliance_id'] = $oPlayer->alliance_id;
          }
        }
      }

      // Get notes
      $aNotes = \Grepodata\Library\Controller\IndexV2\Notes::allByUserForTown($oUser, $oWorld->grep_id, $TownId);
      $aDuplicates = array();
      /** @var \Grepodata\Library\Model\IndexV2\Notes $Note */
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

      // Sort intel by sort_date descending
      //$aResponse['intel'] = array_reverse($aResponse['intel']);
      usort($aResponse['intel'], function ($a, $b) {
        return ($a['sort_date'] > $b['sort_date']) ? -1 : 1;
      });

      // Give newest record a cost boost
      if (sizeof($aResponse['intel'])>0) {
        $aResponse['intel'][0]['cost'] *= 5;
      }

      // try to expand teams
      $aTeams = $aResponse['teams'];
      $aParsedTeams = array();
      foreach ($aTeams as $Team) {
        try {
          $oIndex = IndexInfo::where('key_code', '=', $Team)->firstOrFail();
          $aParsedTeams[] = array(
            'index_key' => $oIndex->key_code,
            'index_name' => $oIndex->index_name,
          );
        } catch (Exception $e) {}
      }
      $aResponse['teams'] = $aParsedTeams;

      // get alliance
      try {
        $oAlliance = Alliance::firstOrFail($aResponse['alliance_id'], $oWorld->grep_id);
        $aResponse['alliance_name'] = $oAlliance->name;
      } catch (Exception $e) {}

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No intel found on this town in this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetConquestReportsGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'conquest_id'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $ConquestId = $aParams['conquest_id'];

      $oConquest = \Grepodata\Library\Controller\IndexV2\Conquest::getByUserByConquestId($oUser, $ConquestId);
      $oWorld = World::getWorldById($oConquest->world);

      // format response
      $aResponse = array(
        'world' => $oWorld->grep_id,
        'conquest' => Conquest::getMixedConquestFields($oConquest, $oWorld),
        'intel' => array()
      );

      $bHideRemainingDefences = false;
      if (isset($aResponse['conquest']['hide_details']) && $aResponse['conquest']['hide_details'] == true) {
        $bHideRemainingDefences = true;
      }

      // Find reports
      $aReports = \Grepodata\Library\Controller\IndexV2\Intel::allByUserForConquest($oUser, $ConquestId, false);
      if (empty($aReports) || count($aReports) <= 0) {
        return self::OutputJson($aResponse);
      }

      foreach ($aReports as $oCity) {
        $aConqDetails = json_decode($oCity->conquest_details, true);
        $aAttOnConq = array(
          'date' => $oCity->parsed_date,
          'sort_date' => Carbon::parse($oCity->parsed_date),
          'attacker' => array(
            'hash' => $oCity->hash,
            'town_id' => $oCity->town_id,
            'town_name' => $oCity->town_name,
            'player_id' => $oCity->player_id,
            'player_name' => $oCity->player_name,
            'alliance_id' => $oCity->alliance_id,
            'luck' => $oCity->luck,
            'attack_type' => 'attack',
            'friendly' => false,
            'units' => \Grepodata\Library\Controller\IndexV2\Intel::parseUnitLossCount(\Grepodata\Library\Controller\IndexV2\Intel::getMergedUnits($oCity)),
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
                in_array($aUnit['name'], \Grepodata\Library\Controller\IndexV2\Intel::sea_units) ||
                $aUnit['name'] == \Grepodata\Library\Controller\IndexV2\Intel::sea_monster)) {
              $aAttOnConq['attacker']['attack_type'] = 'sea_attack';
              break;
            }
          }
        }

        if ($bHideRemainingDefences == false) {
          $aAttOnConq['defender']['units'] = \Grepodata\Library\Controller\IndexV2\Intel::splitLandSeaUnits($aConqDetails['siege_units']) ?? array();
        }

        $aResponse['intel'][] = $aAttOnConq;
      }

      // sort by sort_date desc
      usort($aResponse['intel'], function ($a, $b) {
        if ($a['sort_date'] == $b['sort_date']) {
          return 0;
        }
        return ($a['sort_date'] < $b['sort_date']) ? 1 : -1;
      });

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No reports found for this conquest.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GetSiegelistGET()
  {
    try {
      $aParams = self::validateParams(array('access_token', 'index_key'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::firstOrFail($aParams['index_key']);

      if (isset($aParams['from'])) $From = $aParams['from']; else $From = 0;
      if (isset($aParams['size'])) $Size = $aParams['size']; else $Size = 30;

      $aConquestsList = array();

      $oWorld = World::getWorldById($oIndex->world);
      $aConquests = \Grepodata\Library\Controller\IndexV2\Conquest::allByIndex($oIndex, $From, $Size);
      foreach ($aConquests as $oConquestOverview) {
        $aConquestOverview = \Grepodata\Library\Model\IndexV2\Conquest::getMixedConquestFields($oConquestOverview, $oWorld);
        $aConquestsList[] = $aConquestOverview;
      }

      $Total = null;
      if ($From == 0) {
        $Total = \Grepodata\Library\Controller\IndexV2\Conquest::countByIndex($oIndex);
      }

      $aResponse = array(
        'success' => true,
        'batch_size'  => sizeof($aConquestsList),
        'total_items' => $Total,
        'items'       => $aConquestsList
      );

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No conquests found for this index.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

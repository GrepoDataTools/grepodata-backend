<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Exception;
use Grepodata\Library\Indexer\UnitStats;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\IndexV2\Command;
use Grepodata\Library\Router\ResponseCode;

class Commands extends \Grepodata\Library\Router\BaseRoute
{

//  public static function GetCommandsGET()
//  {
//    $aParams = array();
//    try {
//      // Validate params
//      $aParams = self::validateParams(array('access_token', 'world'));
//
//      // TODO: upload to user via access_token => disabled for demo
//      //$oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
//
//      // TODO: Get world => disabled for demo
//      //$oWorld = World::getWorldById($aParams['world']);
//
//      // Get commands for user (temporarily get by world param for demo)
//      $aCommandsList = Command::where('world', '=', $aParams['world'])
//        ->where('arrival_at', '>', time())
//        ->orderBy('arrival_at', 'asc')
//        ->orderBy('command_id', 'desc')
//        ->get();
//
//      $aCommandsResponse = array();
//      foreach ($aCommandsList as $oCommand) {
//        $aCommandsResponse[] = $oCommand->getPublicFields();
//      }
//
//      $aResponse = array(
//        'count' => count($aCommandsResponse),
//        'items' => $aCommandsResponse
//      );
//      ResponseCode::success($aResponse);
//
//    } catch (Exception $e) {
//      die(self::OutputJson(array(
//        'message'     => 'Unable to load commands.',
//        'parameters'  => $aParams
//      ), 404));
//    }
//  }
//
//  public static function UploadCommandsPOST()
//  {
//    $aParams = array();
//    try {
//      // Validate params
//      $aParams = self::validateParams(array('access_token', 'world', 'commands', 'alliance_id', 'player_id', 'player_name'));
//
//      // TODO: upload to user via access_token => disabled for demo
//      //$oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);
//
//      // TODO: Get world => disabled for demo
//      //$oWorld = World::getWorldById($aParams['world']);
//
//      // Parse commands and persist to database
//      $aCommandsList = json_decode($aParams['commands'], true);
//
//      // Save commands
//      $SavedCount = 0;
//      foreach ($aCommandsList as $aCommand) {
//        // Save command to Indexer_command
//        try {
//          if (!isset($aCommand['id']) || $aCommand['id'] <= 0) {
//            continue;
//          }
//
//          // basic command info
//          // TODO: check if returning command has a different id
//          // TODO: check what command_id looks like if source user and target user both upload the same command
//          $oCommand = new Command();
//          $oCommand->world = $aParams['world'];
//          $oCommand->command_id = $aCommand['id'];
//          $oCommand->type = $aCommand['type']??'default';
//          $oCommand->is_returning = $aCommand['return']===true?1:0;
//          $oCommand->attacking_strategy = $aCommand['attacking_strategies'][0]??'regular';
//
//          // source
//          $oCommand->source_town_id = $aCommand['origin_town_id']??0;
//          $oCommand->source_town_name = $aCommand['origin_town_name']??'';
//          $oCommand->source_player_id = $aCommand['origin_town_player_id']??0;
//          $oCommand->source_player_name = $aCommand['origin_town_player_name']??'';
//          $oCommand->source_alliance_id = $aCommand['origin_town_player_alliance_id']??0;
//          $oCommand->source_alliance_name = $aCommand['origin_town_player_alliance_name']??'';
//
//          // target
//          $oCommand->target_town_id = $aCommand['destination_town_id']??0;
//          $oCommand->target_town_name = $aCommand['destination_town_name']??'';
//          $oCommand->target_player_id = $aCommand['destination_town_player_id']??0;
//          $oCommand->target_player_name = $aCommand['destination_town_player_name']??'';
//          $oCommand->target_alliance_id = $aCommand['destination_town_player_alliance_id']??0;
//          $oCommand->target_alliance_name = $aCommand['destination_town_player_alliance_name']??'';
//
//          // timing (TODO: converted to world time?)
////          $oCommand->started_at = Carbon::createFromTimestampUTC($aCommand['started_at'])->setTimezone($oWorld->php_timezone)->getTimestamp();
////          $oCommand->arrival_at = Carbon::createFromTimestampUTC($aCommand['arrival_at'])->setTimezone($oWorld->php_timezone)->getTimestamp();
//          $oCommand->started_at = $aCommand['started_at']??0;
//          $oCommand->arrival_at = $aCommand['arrival_at']??0;
//
//          // parse units
//          $aUnits = array();
//          foreach ($aCommand as $Key => $Value) {
//            if (key_exists($Key, UnitStats::units) && $Value > 0) {
//              $aUnits[$Key] = $Value;
//            }
//          }
//          $oCommand->units = json_encode($aUnits)??'';
//
//          $oCommand->save();
//          $SavedCount++;
//        } catch (Exception $e) {
//          // probably duplicate. ignore
//          Logger::warning('DEMO: upload commands ' .$e->getMessage());
//        }
//
//        // TODO; save command link to Indexer_command_shared for each team that user is a part of => disabled for demo
//      }
//
//
//      $aResponse = array(
//        'new_commands_saved' => $SavedCount
//      );
//
//      ResponseCode::success($aResponse);
//
//    } catch (Exception $e) {
//      die(self::OutputJson(array(
//        'message'     => 'Unable to upload commands.',
//        'parameters'  => $aParams
//      ), 404));
//    }
//  }

}

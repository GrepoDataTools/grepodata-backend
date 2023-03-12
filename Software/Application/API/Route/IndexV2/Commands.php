<?php

namespace Grepodata\Application\API\Route\IndexV2;

use Carbon\Carbon;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Exception;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Controller\IndexV2\Roles;
use Grepodata\Library\Controller\World;
use Grepodata\Library\IndexV2\IndexManagement;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Redis\RedisClient;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Support\Facades\Log;

class Commands extends \Grepodata\Library\Router\BaseRoute
{

  /**
   * API route: /commands/activeteams
   * Method: GET
   */
  public static function ActiveTeamsGET()
  {
    $aParams = array();
    try {
      $aParams = self::validateParams(array('access_token'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $aTeams = IndexInfo::allByUser($oUser, false);
      if (count($aTeams) <= 0) {
        // User has no teams
        ResponseCode::errorCode(7202);
      }

      $aOperations = array();
      $bHasActiveOperation = false;
      $Limit = Carbon::now();
      $Limit = $Limit->subDays(30);
      foreach ($aTeams as $aTeam) {

        // Check in redis if team has an active op
        $OperationStateKey = RedisClient::COMMAND_STATE_PREFIX.$aTeam->key_code;
        $aCachedState = RedisClient::GetKey($OperationStateKey);
        if ($aCachedState == false) {

          // ignore inactive teams
          if ($aTeam->updated_at < $Limit) {
            continue;
          }

          // No active operation for this team (add basic info)
          $aOperation = array(
            'active' => false,
            'sort_id' => $aTeam->sort_id,
            'index' => $aTeam->key_code,
            'team' => $aTeam->index_name,
            'world' => $aTeam->world,
            'commands' => 0,
          );

        } else {
          $bHasActiveOperation = true;
          $aState = json_decode($aCachedState, true);

          $aOperation = array(
            'active' => true,
            'sort_id' => $aTeam->sort_id,
            'index' => $aTeam->key_code,
            'team' => $aTeam->index_name,
            'world' => $aTeam->world,
            'commands' => $aState['num_commands'],
            'players' => $aState['active_players']
          );
        }

        $aOperations[] = $aOperation;
      }

      // Order by biggest op desc
      usort($aOperations, function ($a, $b) {
        if ($a['commands'] == $b['commands']) {
          return $a['sort_id'] > $b['sort_id'] ? -1 : 1;
        }
        return $a['commands'] > $b['commands'] ? -1 : 1;
      });

      $aResponse = array(
        'active' => $bHasActiveOperation,
        'operations' => $aOperations
      );

      ResponseCode::success($aResponse);

    } catch (Exception $e) {
      Logger::warning("OPS: unable to load active teams [".($oUser->id??'')."]. ".$e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to load active teams.',
        'parameters'  => $aParams
      ), 404));
    }


  }

  /**
   * API route: /commands/get
   * Method: GET
   */
  public static function GetCommandsGET()
  {
    $aParams = array();
    try {
      $aParams = self::validateParams(array('access_token', 'team'));

      if (strlen($aParams['team']) != 8) {
        ResponseCode::errorCode(7101);
      }
      $Team = $aParams['team'];

      $LastPull = 0;
      if (isset($aParams['last_get_cmd']) && $aParams['last_get_cmd'] > 0) {
        $LastPull = $aParams['last_get_cmd'];

        // Add a margin to deal with batch insert delays on Elasticsearch
        $LastPull -= 4;
      }

      // Get current timestamp
      $GetTimestamp = time();

      // Check operation status in cache
      // If there is no active operation or if there is no new update, we can return without hitting the database
      $aActivePlayers = array();
      try {
        $OperationStateKey = RedisClient::COMMAND_STATE_PREFIX.$Team;
        $aCachedState = RedisClient::GetKey($OperationStateKey);
        if ($aCachedState != false) {
          $aState = json_decode($aCachedState, true);
          $aActivePlayers = $aState['active_players'];
          if ($LastPull > $aState['updated_at']) {
            // No new commands since last check, return with success code 8010
            ResponseCode::success(array(
              'updated_at' => $GetTimestamp,
            ), 8010);
          }
        } else {
          // No active operation, return with error code 8020
          ResponseCode::errorCode(8020);
        }
      } catch (Exception $e) {
        Logger::warning('OPS: Error checking operation state in Redis ' .$e->getMessage());
      }

      // Try cached response
      $bCachedResponse = false;
      $aCommands = array();
      try {
        if ($LastPull <= 0) {
          $OperationDataKey = RedisClient::COMMAND_DATA_PREFIX.$Team;

          $aCachedData = RedisClient::GetKey($OperationDataKey);
          if ($aCachedData != false) {
            $aCommands = json_decode($aCachedData, true);
            $bCachedResponse = true;
          }
        }
      } catch (Exception $e) {
        Logger::warning('OPS: Error checking cached operation data in Redis ' .$e->getMessage());
      }

      // Verify user
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      // Verify team role
      $oIndexRole = IndexManagement::verifyUserCanRead($oUser, $Team);
      $bUserIsAdmin = in_array($oIndexRole->role, array(Roles::ROLE_ADMIN, Roles::ROLE_OWNER));
      $Team = $oIndexRole->index_key;

      // Get commands from ES
      if (!$bCachedResponse) {
        try {
          $aCommands = \Grepodata\Library\Elasticsearch\Commands::GetCommands($Team, $GetTimestamp, $LastPull);
        } catch (NoNodesAvailableException $e) {
          // ES cluster is down
          Logger::error("OPS get: ES no alive nodes");
          ResponseCode::errorCode(8300, array(), 503);
        }

        // Attempt to save in cache
        try {
          if ($LastPull <= 0 && count($aCommands) > 0) {
            $MaxArrival = 0;
            foreach ($aCommands as $aCommand) {
              if ($aCommand['arrival_at'] > $MaxArrival) {
                $MaxArrival = $aCommand['arrival_at'];
              }
            }
            $OperationDataKey = RedisClient::COMMAND_DATA_PREFIX.$Team;
            $ttl = min($MaxArrival - time(), 3600); // TTL = seconds until last command arrival (up to 1 hour)
            RedisClient::SetKey($OperationDataKey, json_encode($aCommands), $ttl);
          }
        } catch (Exception $e) {
          Logger::warning('OPS: Error checking cached operation data in Redis ' .$e->getMessage());
        }
      }

      // Format output
      $aCommandsResponse = array();
      foreach ($aCommands as $aCommand) {
        // Soft deletions should be treated as hard deletions if user is not uploader or if user is not admin
        if (!$bUserIsAdmin && key_exists('upload_uid', $aCommand) && key_exists('delete_status', $aCommand)) {
          if ($oUser->id != $aCommand['upload_uid'] && $aCommand['delete_status'] == 'soft') {
            $aCommand['delete_status'] = 'hard';
          }
        }
        $aCommandsResponse[] = $aCommand;
      }

      $aResponse = array(
        'count' => count($aCommandsResponse),
        'updated_at' => $GetTimestamp,
        'is_admin' => $bUserIsAdmin,
        'cached' => $bCachedResponse,
        'players' => $aActivePlayers,
        'items' => $aCommandsResponse
      );

      ResponseCode::success($aResponse, 8000);

    } catch (Exception $e) {
      Logger::warning("OPS: Get commands error: ".$e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to load commands.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  /**
   * API route: /commands/update
   * Method: POST
   */
  public static function UpdateCommandPOST()
  {
    $aParams = array();
    try {
      $aParams = self::validateParams(array('access_token', 'team', 'es_id', 'action', 'content', 'world'));
      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $oIndexRole = IndexManagement::verifyUserCanRead($oUser, $aParams['team']);
      $bUserIsAdmin = in_array($oIndexRole->role, array(Roles::ROLE_ADMIN, Roles::ROLE_OWNER));

      $oWorld = World::getWorldById($aParams['world']);

      // Handle update event
      $UpdatedAt = time();
      $aUpdateBody = array();
      $bVerifyUser = false;
      $UpdateType = 'doc';
      switch ($aParams['action']) {
        case 'delete':
          // if content=='soft', then record will be soft deleted. else, soft deletion can be undone
          if ($aParams['content'] == 'soft') {
            $DeleteStatus = 'soft';
          } else {
            $DeleteStatus = '';
          }

          if ($bUserIsAdmin) {
            // If user is admin, we do not have to verify user id because admin can delete any command
            $aUpdateBody = array(
              'doc' => array(
                'delete_status' => $DeleteStatus,
                'updated_at' => $UpdatedAt
              )
            );
          } else {
            // user can only soft delete their own commands (upload_uid == user_id)
            $aUpdateBody = array(
              'script' => array(
                'source' => "if (ctx._source.upload_uid == params.uid) { ctx._source.updated_at = params.updated_at; ctx._source.delete_status = params.delete_status; } else { ctx.op = 'noop' }",
                'lang' => 'painless',
                'params' => array(
                  'uid' => $oUser->id,
                  'delete_status' => $DeleteStatus,
                  'updated_at' => $UpdatedAt
                )
              )
            );
            $UpdateType = 'script';
            $bVerifyUser = true;
          }
          break;
        case 'comment':
          // Add a comment
          $UserName = $oUser->username;
          $TimeHuman = $oWorld->getServerTime()->format('H:i:s M d');
          $TextContent = $aParams['content'];
          $aUpdateBody = array(
            'script' => array(
              'source' => 'ctx._source.comments.add(params.comment); ctx._source.updated_at = params.updated_at;',
              'lang' => 'painless',
              'params' => array(
                'comment' => \Grepodata\Library\Elasticsearch\Commands::EncodeCommandComment($UserName, $TimeHuman, $TextContent),
                'updated_at' => $UpdatedAt
              )
            )
          );
          $UpdateType = 'script';
          break;
        default:
          // Invalid command action
          ResponseCode::errorCode(8110);
      }

      // Do update
      $aUpdateStatus = \Grepodata\Library\Elasticsearch\Commands::UpdateCommand($aParams['es_id'], $aUpdateBody);

      $bUpdateProcessed = false;
      if (isset($aUpdateStatus['_shards']['successful']) && $aUpdateStatus['_shards']['successful'] >= 1) {
        $bUpdateProcessed = true;
      }

      if (!$bUpdateProcessed) {
        Logger::warning('OPS: Update was not processed. ' .json_encode($aUpdateBody). " - ".$aParams['es_id'] . " - " . json_encode($aUpdateStatus));
        if ($bVerifyUser && $aUpdateStatus['result'] == 'noop') {
          // Probably failed because user is unauthorized (noop if uid mismatch)
          ResponseCode::errorCode(8120);
        }
        // Generic update failure
        ResponseCode::errorCode(8200);
      }

      $aUpdatedDocument = array();
      if (isset($aUpdateStatus['get']['_source'])) {
        $aUpdatedDocument = \Grepodata\Library\Elasticsearch\Commands::RenderCommandDocument($aUpdateStatus['get']['_source'], $aParams['es_id'], false);
      }

      // Update Redis cache state
      try {
        $OperationStateKey = RedisClient::COMMAND_STATE_PREFIX.$aParams['team'];

        // Check if key is present
        $aCachedState = RedisClient::GetKey($OperationStateKey);
        if ($aCachedState != false) {
          $aUpdatedState = json_decode($aCachedState, true);

          // Updated operation state
          $aUpdatedState['updated_at'] = $UpdatedAt;
          if (isset($aUpdatedState['max_arrival'])) {
            $ttl = min($aUpdatedState['max_arrival'] - time(), 3600*24); // TTL = seconds until last command arrival (up to 1 day)
          } else {
            $ttl = 3600; // Should not happen under normal circumstances; but still we need a ttl
          }

          // Save updated operation state to cache
          RedisClient::UpsertKey($OperationStateKey, json_encode($aUpdatedState), $ttl);
        }

        // Remove old data from cache
        // TODO: update cached data with new info instead of dropping it
        $OperationDataKey = RedisClient::COMMAND_DATA_PREFIX.$aParams['team'];
        RedisClient::Delete($OperationDataKey);

      } catch (Exception $e) {
        Logger::warning('OPS: Error updating operation state after edit in Redis ' .$e->getMessage());
      }

      $aResponse = array(
        'success' => $bUpdateProcessed,
        'updated_at' => $UpdatedAt,
        'update_type' => $UpdateType,
        'command' => $aUpdatedDocument
      );
      ResponseCode::success($aResponse, 8000);

    } catch (Exception $e) {
      Logger::warning("OPS: unable to update command [".($aParams['es_id']??0).", ".($aParams['action']??'').", ".($aParams['content']??'')."]. ".$e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to update command.',
        'parameters'  => $aParams,
        'error' => $e->getMessage()
      ), 404));
    }

  }

  /**
   * API route: /commands/upload
   * Method: POST
   */
  public static function UploadCommandsPOST()
  {
    $aParams = array();
    try {
      $start = microtime(true) * 1000;

      // Validate params
      $aParams = self::validateParams(array('access_token', 'world', 'del_commands', 'commands', 'alliance_id', 'player_id', 'player_name'));

      $oUser = \Grepodata\Library\Router\Authentication::verifyJWT($aParams['access_token']);

      $oWorld = World::getWorldById($aParams['world']);

      $aTeams = IndexInfo::allByUserAndWorld($oUser, $oWorld->grep_id, false);
      if (count($aTeams) <= 0) {
        ResponseCode::errorCode(7201);
      }

      // TODO: test what happens if a cancelled/deleted command reappears (i.e. enemy used helmet) doc should be updated!

      // Parse commands and persist to database
      $MaxUploadCount = 1000;
      $aCommandsList = json_decode($aParams['commands'], true);
      $aCommandsList = array_slice($aCommandsList,0, $MaxUploadCount);

      // Parse commands that should be deleted
      $aDelCommands = json_decode($aParams['del_commands'], true);

      // Filter commands
      $aNewCommands = array();
      $MaxArrival = 0;
      $Uploads = 0;
      foreach ($aCommandsList as $aCommand) {
        if (!isset($aCommand['id']) || empty($aCommand['id'])) {
          continue;
        }
        $Uploads += 1;
        $aNewCommands[] = $aCommand;
        if ($aCommand['arrival_at'] > $MaxArrival) {
          $MaxArrival = $aCommand['arrival_at'];
        }
      }

      // Index
      $aSkippedTeams = array();
      $aAddedTeams = array();
      $NumErrors = 0;
      foreach ($aTeams as $oTeam) {
        if ($oTeam->role == Roles::ROLE_READ || $oTeam->contribute !== 1) {
          // User is not allowed to write or contributions are disabled
          $aSkippedTeams[] = $oTeam->key_code;
          continue;
        } else{
          $aAddedTeams[] = $oTeam->key_code;
        }

        // Upsert command batch in elasticasearch
        try {
          $NumErrors = \Grepodata\Library\Elasticsearch\Commands::UpsertCommandBatch(
            $oWorld, $oTeam->key_code, $oUser->id, $aParams['player_id'], $aParams['player_name'], $aNewCommands, $aDelCommands);
        } catch (NoNodesAvailableException $e) {
          // ES cluster is down
          Logger::error("OPS upload: ES no alive nodes");
          ResponseCode::errorCode(8300, array(), 503);
        } catch (Exception $e) {
          Logger::warning('OPS: Error indexing commands ' .$e->getMessage());
          $NumErrors = count($aNewCommands);
        }

        // Update cache state
        try {
          $OperationStateKey = RedisClient::COMMAND_STATE_PREFIX.$oTeam->key_code;

          // Check if key is already present
          $aCachedState = RedisClient::GetKey($OperationStateKey);
          $CommandCount = 0;
          $aActivePlayers = array();
          $TeamMaxArrival = $MaxArrival;
          if ($aCachedState != false) {
            $aResponse = json_decode($aCachedState, true);
            $PreviousMaxArrival = $aResponse['max_arrival'];
            $CommandCount = $aResponse['num_commands'];
            $aActivePlayers = $aResponse['active_players'];
            if ($PreviousMaxArrival > $TeamMaxArrival) {
              $TeamMaxArrival = $PreviousMaxArrival;
            }
          }

          // Add uploader to list of active players and increment upload count
          if (key_exists($aParams['player_name'], $aActivePlayers)) {
            $aActivePlayers[$aParams['player_name']] += $Uploads;
          } else {
            $aActivePlayers[$aParams['player_name']] = $Uploads;
          }

          // Updated operation state
          $aUpdatedState = array(
            'updated_at' => time(),
            'max_arrival' => $TeamMaxArrival,
            'num_commands' => $CommandCount + count($aNewCommands),
            'active_players' => $aActivePlayers
          );

          // Update TTL
          $ttl = min($TeamMaxArrival - time(), 3600*24); // TTL = seconds until last command arrival (up to 1 day)

          // Save updated operation state to cache
          RedisClient::UpsertKey($OperationStateKey, json_encode($aUpdatedState), $ttl);

          // Remove old data from cache
          // TODO: expand cached data with new commands instead of dropping it
          $OperationDataKey = RedisClient::COMMAND_DATA_PREFIX.$oTeam->key_code;
          RedisClient::Delete($OperationDataKey);

        } catch (Exception $e) {
          Logger::warning('OPS: Error updating operation state in Redis ' .$e->getMessage());
        }
      }

      $duration = (int) (microtime(true) * 1000 - $start);
      if ($duration > 300) {
        $logmsg = "OPS: CMD index time: ". $duration . "ms, " . count($aNewCommands) . " commands, " . count($aTeams) . " teams";
        Logger::warning($logmsg);
      }

      $aResponse = array(
        'duration' => $duration,
        'num_errors' => $NumErrors,
        'added_teams' => $aAddedTeams,
        'skipped_teams' => $aSkippedTeams
      );

      ResponseCode::success($aResponse, 8000);

    } catch (Exception $e) {
      Logger::warning("OPS: unable to upload commands [".($aParams['world']??'').", ".($aParams['player_name']??'')."]. ".$e->getMessage());
      die(self::OutputJson(array(
        'message'     => 'Unable to upload commands.',
        'parameters'  => $aParams
      ), 404));
    }
  }

}

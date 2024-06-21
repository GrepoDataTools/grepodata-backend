<?php

namespace Grepodata\Library\Elasticsearch;

use Carbon\Carbon;
use Elasticsearch\ClientBuilder;
use Grepodata\Library\Controller\IndexV2\CommandLog;
use Grepodata\Library\Indexer\UnitStats;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\User;

class Commands
{
  const IndexIdentifier = 'commands';
  const TypeCommand = 'cmd';
  const SpyPrefixInt = 6942;
  const ColoPrefixInt = 4269;

  /**
   * Build the Elasticsearch document ID for the given command & team.
   * @param array $aCommand
   * @param string $Team
   * @return string
   */
  public static function BuildCommandId(array $aCommand, string $Team)
  {
    $CommandId = $aCommand['id'];
    if (strpos($CommandId, 'espionage_') !== false) {
      $CommandId = (int) str_replace('espionage_', self::SpyPrefixInt, $CommandId);
    } else if (strpos($CommandId, 'colonization_') !== false) {
      $CommandId = (int) str_replace('colonization_', self::ColoPrefixInt, $CommandId);
    }
    return '' . $aCommand['arrival_at'] . $CommandId . $Team;
  }

  /**
   * Batch upsert commands
   * @param \Grepodata\Library\Model\World $oWorld
   * @param $Team
   * @param $UserId
   * @param $PlayerId
   * @param $PlayerName
   * @param $aNewCommands
   * @param $aDelCommands
   * @return bool
   */
  public static function UpsertCommandBatch(\Grepodata\Library\Model\World $oWorld, $Team, $UserId, $PlayerId, $PlayerName, $aNewCommands, $aDelCommands, $aShareSettings=array())
  {
    self::EnsureIndex();

    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);

    $aParams = array(
      'index' => self::IndexIdentifier,
      'type'  => self::TypeCommand,
      'body'  => array()
    );

    $NumUploaded = 0;
    $NumCreated = 0;
    $NumDeleted = 0;
    $NumUpdated = 0;
    $UpdatedAt = time();
    $start = microtime(true) * 1000;

    // Delete commands: we simply override the command _id using the bulk upsert
    $bHasRecords = false;
    foreach ($aDelCommands as $aCommand) {
      try {
        // This command should already exist; we will override it with the new deleted status
        $_id = self::BuildCommandId($aCommand, $Team);

        // update (The 'update' operation is used to maintain the original document; only the deletion status must be updated)
        $aParams['body'][] = array(
          'update' => array(
            '_index' => self::IndexIdentifier,
            '_type'  => self::TypeCommand,
            '_id'    => $_id,
          )
        );

        // Document update (only deletion status and updated_at should be changed; the original doc is maintained in case the command reappears later)
        $aParams['body'][] = array(
          'doc' => array(
            'updated_at' => $UpdatedAt,
            'delete_status' => 'hard'
          )
        );

        $NumUploaded -= 1;
        $NumDeleted += 1;
        $bHasRecords = true;
      } catch (\Exception $e) {
        Logger::warning('OPS: Exception preparing command delete item: '.$e->getMessage());
      }
    }

    // New commands
    $aParsedNewCommands = array();
    foreach ($aNewCommands as $aCommand) {
      try {

        // main type
        $CommandType = $aCommand['type']??'default';
        $CancelHuman = '';

        // Check user filters
        if (isset($aShareSettings)) {
          if ($CommandType !== 'support' && key_exists('attacks', $aShareSettings) && $aShareSettings['attacks'] === false) {
            // User has chosen to not share attack commands for this team
            continue;
          }
          if ($CommandType === 'support' && key_exists('supports', $aShareSettings) && $aShareSettings['supports'] === false) {
            // User has chosen to not share support commands for this team
            continue;
          }
          if ($aCommand['return']===true && key_exists('returns', $aShareSettings) && $aShareSettings['returns'] === false) {
            // User has chosen to not share returning commands for this team
            continue;
          }
        }

        // Check if it is a planned command and do preprocessing
        $bPlannedCommand = false;
        if (key_exists('plan_name', $aCommand) && !empty($aCommand['plan_name'])) {
          $bPlannedCommand = true;
          $aCommand['id'] = 'pcmd'.$aCommand['id']; // Prepend id with 'pcmd' (planned command) to make it unique in the context of real commands
          $aCommand['return'] = false;

          // We use the cancel_human property to store the expected departure time (otherwise we have to add another field to the index)
          $TargetedDeparture = Carbon::createFromFormat('H:i:s M d', date('H:i:s M d', $aCommand['send_at']), 'UTC');
          $TargetedDeparture->setTimezone($oWorld->php_timezone);
          $CancelHuman = $TargetedDeparture->format('H:i:s M d');
        }

        // Generate id (_id --> {arrival_at}{cmd_id}{team})
        $_id = self::BuildCommandId($aCommand, $Team);

        // create (The 'create' operation ensures that the previous document is NOT overwritten if already existing)
        // It is not guaranteed that the document does not already exist (user can upload the same command twice)
        // This will raise a version conflict if the doc already exists; these must be caught later
        $aParams['body'][] = array(
          'create' => array(
            '_index' => self::IndexIdentifier,
            '_type'  => self::TypeCommand,
            '_id'    => $_id,
          )
        );

        // parse units
        if ($aCommand['type'] == 'attack_spy') {
          $Units = $aCommand['payed_iron']??'';
        } else {
          $aUnits = array();
          $aUnitArray = $aCommand;
          if ($bPlannedCommand && key_exists('units', $aCommand)) {
            $aUnitArray = $aCommand['units'];
          }
          foreach ($aUnitArray as $Key => $Value) {
            if (key_exists($Key, UnitStats::units) && $Value > 0) {
              $aUnits[$Key] = $Value;
            }
          }
          $Units = json_encode($aUnits)??'';
        }

        // parse arrival time
        $ArrivalHuman = Carbon::createFromFormat('H:i:s M d', date('H:i:s M d', $aCommand['arrival_at']), 'UTC');
        $ArrivalHuman->setTimezone($oWorld->php_timezone);
        $ArrivalHuman = $ArrivalHuman->format('H:i:s M d');

        // parse cancel time
        if (key_exists('cancelable', $aCommand) && $aCommand['cancelable'] === true) {
          $CancelTime = 600; // default 10 minutes
          if (strpos($aCommand['id'], 'espionage_') !== false) {
            $CancelTime = 300; // spionage can only be cancelled in first 5 minutes
          }

          $CancelableUntil = min($aCommand['started_at'] + $CancelTime, $aCommand['arrival_at']);
          $CancelHuman = Carbon::createFromFormat('H:i:s M d', date('H:i:s M d', $CancelableUntil ), 'UTC');
          $CancelHuman->setTimezone($oWorld->php_timezone);
          $CancelHuman = $CancelHuman->format('H:i:s M d');
        }

        //if ($CommandType == 'revolt') {
        //  error_log(json_encode($aCommand));
        //}

        if (key_exists('griffin', $aCommand) && key_exists('manticore', $aCommand) && $aCommand['griffin'] > 0 && $aCommand['manticore'] > 0 ) {
          // Command can not have mythical units from 2 different gods. Probably a command where all units are 1; weird
          $Units = json_encode(array())??'';
          if (key_exists('own_command', $aCommand) && $aCommand['own_command']!==false) {
            Logger::warning("OPS: check invalid command input: ".json_encode($aCommand));
          }
        }

        // Subtype
        $subtype = 'default';
        $ConquestTownId = null;
        if (key_exists('is_attack_spot', $aCommand) && $aCommand['is_attack_spot'] === true) {
          $subtype = 'attack_spot';
        } elseif (key_exists('is_quest', $aCommand) && $aCommand['is_quest'] === true) {
          $subtype = 'quest';
        } elseif ($CommandType == 'portal_attack_olympus' || $CommandType == 'portal_support_olympus') {
          $subtype = 'portal';
        } elseif (key_exists('is_temple', $aCommand) && $aCommand['is_temple'] === true) {
          $subtype = 'temple';
        } else if ($CommandType == 'attack_takeover' && key_exists('command_type', $aCommand)) {
          // takeover actual
          // if travelling cs, then command_type is 'command'
          // if landed cs, then command_type is 'attack_takeover'
          $subtype = $aCommand['command_type'];
          $ConquestTownId = $aCommand['destination_town_id'];
        } else if ($CommandType == 'revolt' && key_exists('started_at', $aCommand) && key_exists('finished_at', $aCommand) && !key_exists('sword', $aCommand)) {
          // Parse ongoing revolt
          // The started_at property indicates the phase 2 start, the finished_at property indicates the phase 2 end.
          // Note: finished_at==arrival_at for these commands, so we don't have to save finished_at
          $subtype = 'ongoing_revolt';

          // We use the cancel_human property to store the phase 2 start (otherwise we need to drop index to add a column)
          // The phase 2 end human time is already stored in arrival_human
          $RevoltPhase2Start = Carbon::createFromFormat('H:i:s M d', date('H:i:s M d', $aCommand['started_at']), 'UTC');
          $RevoltPhase2Start->setTimezone($oWorld->php_timezone);
          $CancelHuman = $RevoltPhase2Start->format('H:i:s M d');
        } elseif (key_exists('colonization_finished_at', $aCommand) && $aCommand['colonization_finished_at'] > 0) {
          // This might only appear for foundation commands (non-cs)
          $subtype = 'cs_eta';
        }

        // Comments
        $aComments = array();
        if (key_exists('custom_command_name', $aCommand) && !is_null($aCommand['custom_command_name'])) {
          $Now = $oWorld->getServerTime()->format('H:i:s M d');
          $aComments[] = self::EncodeCommandComment($PlayerName, $Now, $aCommand['custom_command_name']);
        }

        // Target town & foundation override
        $TargetTown = $aCommand['destination_town_name']??$aCommand['target_town_name']??'';
        if (strpos($aCommand['id'], 'colonization_') !== false && $CommandType == 'colonization') {
          if (key_exists('islandurl_destination', $aCommand)) {
            $TargetTown = strip_tags($aCommand['islandurl_destination']??'');

            if (key_exists('colonization_finished_at', $aCommand)) {
              $CommandType = 'foundation';
            }
          }
        }

        // create document body
        $aParsedCommand = array(
          'team'       => $Team,
          'updated_at' => $UpdatedAt,
          'arrival_at' => $aCommand['arrival_at']??0,
          'arrival_human' => $ArrivalHuman,
          'cancel_human' => $CancelHuman,
          'started_at' => $aCommand['started_at']??$aCommand['send_at']??0,
          'upload_uid' => $UserId,
          'upload_id'  => $PlayerId,
          'upload_n'   => $PlayerName,
          'cmd_id'     => $aCommand['id'],
          'type'       => $CommandType,
          'subtype'    => $subtype??'default',
          'own_command' => $aCommand['own_command']??$aCommand['can_edit']??true,
          'is_planned' => $bPlannedCommand,
          'return'     => $aCommand['return']===true,
          'attacking_strategy' => $aCommand['attacking_strategies'][0]??'regular',
          'units'      => $Units,
          'comments'   => $aComments,
          'delete_status' => '',
          'src_twn_id' => $aCommand['origin_town_id']??0,
          'src_all_id' => $aCommand['origin_town_player_alliance_id']??$aCommand['origin_alliance_id']??0,
          'src_ply_id' => $aCommand['origin_town_player_id']??$aCommand['origin_player_id']??0,
          'src_twn_n'  => $aCommand['origin_town_name']??'',
          'src_all_n'  => $aCommand['origin_town_player_alliance_name']??$aCommand['origin_alliance_name']??'',
          'src_ply_n'  => $aCommand['origin_town_player_name']??$aCommand['origin_player_name']??'',
          'trg_twn_id' => $aCommand['destination_town_id']??$aCommand['target_town_id']??0,
          'trg_all_id' => $aCommand['destination_town_player_alliance_id']??$aCommand['target_alliance_id']??0,
          'trg_ply_id' => $aCommand['destination_town_player_id']??$aCommand['target_player_id']??0,
          'trg_twn_n'  => $TargetTown,
          'trg_all_n'  => $aCommand['destination_town_player_alliance_name']??$aCommand['target_alliance_name']??'',
          'trg_ply_n'  => $aCommand['destination_town_player_name']??$aCommand['target_player_name']??'',
        );
        if ($ConquestTownId!=null) {
          // Needed to keep track of conquest master command
          $aParsedCommand['conquest_town_id']=$ConquestTownId;
        }
        $aParams['body'][] = $aParsedCommand;
        $bHasRecords = true;
        $NumUploaded += 1;
        $NumCreated += 1;

        // Save parsed command in case we need it to retry later
        $aParsedNewCommands[$_id] = $aParsedCommand;
      } catch (\Exception $e) {
        Logger::warning('OPS: Exception preparing command batch item: '.$e->getMessage());
      }
    }

    if (!$bHasRecords) {
      // No changes to be done. return
      return 0;
    }

    // Upload to elasticsearch
    //error_log(json_encode($aParams));
    $aResponse = $ElasticsearchClient->bulk($aParams);
    //error_log(json_encode($aResponse));

    // Check for errors
    $aRetryAsUpdateIds = array();
    $bCaughtError = false;
    if (isset($aResponse['errors']) && $aResponse['errors'] == true) {
      foreach ($aResponse['items'] as $aItem) {
        if (isset($aItem['update']['error'])) {
          // This can happen when a tracked command is deleted by userscript but was never actually indexed because user did not share with this team or command type
          Logger::warning("OPS: ES command update errors: " . json_encode($aItem['update']['error']));
          $NumUploaded += 1; // delete failed so decrement is undone
          $NumDeleted -= 1;
          $bCaughtError = true;
        } else if (isset($aItem['create']['error'])) {
          $NumUploaded -= 1; // create failed so increment is undone
          $NumCreated -= 1;
          if (isset($aItem['create']['error']['type']) && $aItem['create']['error']['type'] == 'version_conflict_engine_exception') {
            // this is a normal failure mode for 'create' operations. Doc already existed so it is skipped
            // However, we still need to update the command because units might have changed or the command might have been hidden and reappeared (spell)
            // Therefore, we should retry using an update query where only the units and deletion status is updated

            // Save the id of the failed command
            $aRetryAsUpdateIds[] = $aItem['create']['_id'];
            $bCaughtError = true;
            continue;
          }
          Logger::warning("OPS: ES command create errors: " . json_encode($aItem['create']['error']));
          $bCaughtError = true;
        }
      }
      if (!$bCaughtError) {
        Logger::warning("OPS: ES command uncaught bulk insert error: " . json_encode($aResponse));
      }
    }

    if (count($aRetryAsUpdateIds) > 0) {
      // Retry the failed create documents using an update query

      try {
        // scenario 1: units changed after spell was applied to command --> doc exists but units update is required
        // scenario 2: hidden command reappears, but user already hard deleted the original upload --> doc exists but delete status needs to be reset
        // scenario 3: friendly attack on a friendly town (e.g. under siege) --> duplicate command uploads by the attacker and the friendly defender --> only the attacker's command has unit info

        $aRetryParams = array(
          'index' => self::IndexIdentifier,
          'type' => self::TypeCommand,
          'body' => array()
        );
        error_log("Num retries: " . count($aRetryAsUpdateIds));
        foreach ($aRetryAsUpdateIds as $_id) {
          if (!key_exists($_id, $aParsedNewCommands)) {
            continue;
          }
          $aCommand = $aParsedNewCommands[$_id];

          // For the retry, we use a script update operation instead of a create operation
          $aRetryParams['body'][] = array(
            'update' => array(
              '_index' => self::IndexIdentifier,
              '_type' => self::TypeCommand,
              '_id' => $_id,
            )
          );

          // Command may have reappeared after being hidden or the units might have changed after a spell was applied
          // script update (only deletion status, units and updated_at should be changed; the original doc is maintained in case the command reappears later)
          // https://www.elastic.co/guide/en/elasticsearch/painless/current/painless-lang-spec.html
          // The units are only updated if the unit value has changed but the number of units (derived from colons) is greater or equal. This allows for spell updates but ignores duplicate friendly overrides.
          $aRetryParams['body'][] = array(
            'script' => array(
              'source' => "
              boolean noop = true; 
              if (ctx._source.delete_status == 'hard') { 
                ctx._source.delete_status = ''; 
                noop = false
              }
              int countColons(String s) {
                return s.chars().filter(ch -> ch == ':').count();
              }
              if (ctx._source.units != params.units && countColons(params.units) >= countColons(ctx._source.units)) { 
                ctx._source.units = params.units; 
                noop = false
              }
              if (noop === true) {
                ctx.op = 'noop'
              } else {
                ctx._source.updated_at = params.updated_at
              }
            ",
              'lang' => 'painless',
              'params' => array(
                'units' => $aCommand['units'],
                'updated_at' => $UpdatedAt
              )
            )
          );
        }

        $aRetryResponse = $ElasticsearchClient->bulk($aRetryParams);

        //error_log(json_encode($aRetryParams));
        //error_log(json_encode($aRetryResponse));

        // Catch update errors
        foreach ($aRetryResponse['items'] as $aItem) {
          if (isset($aItem['update']['error'])) {
            Logger::warning("OPS: ES command batch update errors: " . json_encode($aItem['update']['error']));
          } else if (isset($aItem['update']['result']) && $aItem['update']['result'] !== 'noop') {
            $NumUpdated += 1;
          }
        }

      } catch (\Exception $e) {
        Logger::warning('OPS: Error retrying bulk update: ' . $e->getMessage());
      }
    }

    $duration = (int) (microtime(true) * 1000 - $start);
    if ($duration > 300) {
      $logmsg = "OPS: CMD index time: ". $duration . "ms, " . count($aNewCommands) . " commands, " . $Team;
      Logger::warning($logmsg);
    }

    CommandLog::log('upload', $Team, count($aNewCommands), $NumCreated, $NumUpdated, $NumDeleted, $duration, $UserId);

    return $NumUploaded;
  }

  /**
   * Update a command by id and update doc/script.
   * @param string $Id Elasticsearch document ID for the command
   * @param array $aUpdateBody Update body. can be a 'script' or 'doc' update
   */
  public static function UpdateCommand(string $Id, array $aUpdateBody): array
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);

    $aUpdateStatus = $ElasticsearchClient->update(array(
      'index' => self::IndexIdentifier,
      'type' => self::TypeCommand,
      'id' => $Id,
      'body' => $aUpdateBody,
      'fields' => '_source'
    ));

    return $aUpdateStatus;
  }

  /**
   * Get all active commands for the given team that have been updated since $UpdatedAt
   * @param string $Team
   * @param int $ArrivalAt
   * @param int $UpdatedAt
   * @return array
   */
  public static function GetCommands(string $Team, int $ArrivalAt, int $UpdatedAt = 0): array
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    $aSearchParams = array(
      'size' => 10000,
      'query' => array(
        'bool' => array(
          'must' => array(
            array(
              'range' => array(
                'updated_at' => array(
                  'gte' => $UpdatedAt
                )
              )
            ),
            array(
              'range' => array(
                'arrival_at' => array(
                  'gt' => $ArrivalAt
                )
              )
            ),
            array(
              'match' => array(
                'team' => $Team
              )
            ),
          )
        )
      ),
    );

    $aCommands = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeCommand,
      'body' => $aSearchParams
    ));

    $aResponse = array();
    if (isset($aCommands['hits']['total']) && $aCommands['hits']['total'] > 0) {
      foreach ($aCommands['hits']['hits'] as $aHit) {
        if ($UpdatedAt <= 0 && $aHit['_source']['delete_status'] == 'hard') {
          // We don't care about hard deleted commands since this is a clean session
          continue;
        }

        $aResponse[] = self::RenderCommandDocument($aHit['_source'], $aHit['_id'], false);
      }
    }

    return $aResponse;
  }

  /**
   * Hard delete all commands by from user. Also checks if user is allowed to delete the commands
   * @param string $Team 8 character index key
   * @param int $UserId
   * @param bool $bDeleteByUserId If false, user must be admin and all commands for this team will be updated.
   * @param string $DeleteStatus Can be one of: soft, hard
   * @param int $UpdatedAt Update UNIX timestamp
   * @return int|mixed
   */
  public static function UpdateDeleteStatusByTeamOrUser(string $Team, int $UserId, bool $bDeleteByUserId=false, string $DeleteStatus = 'soft', int $UpdatedAt = 0)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    if ($UpdatedAt <= 0) {
      $UpdatedAt = time();
    }

    // Update delete_status and updated_at for all documents that match the query
    $aDeleteScript = array(
      'script' => array(
        'source' => "
            if (ctx._source.delete_status == 'hard') { 
              ctx.op = 'noop'
            } else {
              ctx._source.updated_at = params.updated_at; ctx._source.delete_status = params.delete_status
            }",
        'lang' => 'painless',
        'params' => array(
          'delete_status' => $DeleteStatus,
          'updated_at' => $UpdatedAt
        )
      ),
      'query' => array(
        'bool' => array(
          'must' => array(
            array(
              'term' => array(
                'team' => $Team, // Only update commands in the given team
              )
            ),
            array(
              'range' => array(
                'arrival_at' => array(
                  'gt' => time() // Only update future commands, others have expired already
                )
              )
            )
          )
        )
      )
    );

    if ($bDeleteByUserId==true) {
      // Only delete commands for the given user
      $aDeleteScript['query']['bool']['must'][] = array(
        'term' => array(
          'upload_uid' => $UserId // Only update commands uploaded by the given user
        )
      );
    }

    $aResponse = $ElasticsearchClient->updateByQuery(array(
      'index' => $IndexName,
      'type' => self::TypeCommand,
      'body' => $aDeleteScript
    ));

    $NumUpdated = 0;
    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      $NumUpdated = $aResponse['total'];
    } else {
      Logger::warning("OPS: unexpected update by query result; ". json_encode($aResponse));
    }

    Logger::warning('OPS: Batch update result ['.$NumUpdated.']. ' .json_encode($aDeleteScript)." -- ".json_encode($aResponse));

    if (isset($aResponse['failures']) && is_array($aResponse['failures']) && count($aResponse['failures'])>0) {
      Logger::warning("OPS: ES update by query error; ". json_encode($aResponse));
    }

    return $NumUpdated;
  }

  /**
   * Clean expired commands
   * @return bool
   * @throws \Exception
   */
  public static function CleanCommands()
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // A command is expired if its UNIX arrival time is in the past
    $Limit = time();

    $aSearchParams = array(
      'query' => array(
        'bool' => array(
          'must' => array(
            array('range' => array('arrival_at' => array('lt' => $Limit)))
          )
        )
      )
    );
    $aResponse = $ElasticsearchClient->deleteByQuery(array(
      'index' => $IndexName,
      'type' => self::TypeCommand,
      'body' => $aSearchParams
    ));

    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      return $aResponse['total'];
    } else {
      throw new \Exception('Unable to delete expired commands in elasticsearch. invalid response.');
    }
  }

  /**
   * Delete all planned commands uploaded by the given user within a specific team)
   * @throws \Exception
   */
  public static function DeletePlannedCommands($UserId, $Team)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    $aSearchParams = array(
      'query' => array(
        'bool' => array(
          'must' => array(
            array('match' => array('team' => $Team)),
            array('match' => array('upload_uid' => $UserId)),
            array('match' => array('is_planned' => true))
          )
        )
      )
    );
    $aResponse = $ElasticsearchClient->deleteByQuery(array(
      'index' => $IndexName,
      'type' => self::TypeCommand,
      'body' => $aSearchParams
    ));

    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      return $aResponse['total'];
    } else {
      throw new \Exception('Unable to delete planned commands in elasticsearch. invalid response.');
    }
  }

  /**
   * Delete all commands that are converging on a conquest
   * @throws \Exception
   */
  public static function DeleteConquestCommands($ConquestTownId, $Team)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    if (empty($ConquestTownId) || $ConquestTownId <= 0) {
      throw new \Exception('Invalid conquest town id in ES delete request: '.$ConquestTownId);
    }

    $aSearchParams = array(
      'query' => array(
        'bool' => array(
          'must' => array(
            array('match' => array('team' => $Team)),
            array('match' => array('conquest_town_id' => $ConquestTownId)),
          )
        )
      )
    );
    $aResponse = $ElasticsearchClient->deleteByQuery(array(
      'index' => $IndexName,
      'type' => self::TypeCommand,
      'body' => $aSearchParams
    ));

    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      return $aResponse['total'];
    } else {
      throw new \Exception('Unable to delete planned commands in elasticsearch. invalid response.');
    }
  }

  public static function EnsureIndex(&$IndexName = '')
  {
    /**
     * Ensures command index is created. Only the following fields will be indexed (index is used at search-time):
     * team --> exact string match; used to lookup specific team commands
     * arrival_at --> integer; used to filter on active commands only
     * updated_at --> integer; used to filter updated commands only
     * upload_uid --> integer; used to verify user when deleting untracked commands
     * is_planned --> boolean; used to drop all planned commands for a user
     * conquest_town_id --> exact string match; used to get/drop all commands for a conquest
     *
     * _id --> {arrival_at}{cmd_id}{team}
     *
     * Note: updating existing fields is not possible without dropping the old index but you can add new fields via the API: https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html
     */
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);

    $IndexName = self::IndexIdentifier;

    if (!$ElasticsearchClient->indices()->exists(array('index' => $IndexName))) {
      try {
        $aIndexParams = array(
          'index' => $IndexName,
          'body'  => array(
            'settings' => array(
              'number_of_shards'    => 1,
              'number_of_replicas'  => 0,
            ),
            'mappings' => array(
              self::TypeCommand => array(
                'properties' => array(
                  'team'       => array('type' => 'keyword', 'index' => true),
                  'updated_at' => array('type' => 'integer', 'index' => true),
                  'arrival_at' => array('type' => 'integer', 'index' => true),
                  'started_at' => array('type' => 'integer', 'index' => false),
                  'arrival_human' => array('type' => 'keyword', 'index' => false),
                  'cancel_human' => array('type' => 'keyword', 'index' => false),

                  'upload_uid' => array('type' => 'integer', 'index' => true),
                  'upload_id'  => array('type' => 'integer', 'index' => false),
                  'upload_n'   => array('type' => 'keyword', 'index' => false),

                  'cmd_id'  => array('type' => 'keyword', 'index' => false),
                  'conquest_town_id' => array('type' => 'integer', 'index' => true),
                  'type'    => array('type' => 'keyword', 'index' => false),
                  'subtype' => array('type' => 'keyword', 'index' => false),
                  'is_planned' => array('type' => 'boolean', 'index' => true),
                  'return'  => array('type' => 'boolean', 'index' => false),
                  'own_command' => array('type' => 'boolean', 'index' => false),
                  'delete_status' => array('type' => 'keyword', 'index' => false),
                  'attacking_strategy' => array('type' => 'keyword', 'index' => false),

                  'units' => array('type' => 'keyword', 'index' => false),
                  'comments' => array('type' => 'keyword', 'index' => false),

                  'src_twn_id' => array('type' => 'integer', 'index' => false),
                  'src_all_id' => array('type' => 'integer', 'index' => false),
                  'src_ply_id' => array('type' => 'integer', 'index' => false),
                  'src_twn_n'  => array('type' => 'keyword', 'index' => false),
                  'src_all_n'  => array('type' => 'keyword', 'index' => false),
                  'src_ply_n'  => array('type' => 'keyword', 'index' => false),

                  'trg_twn_id' => array('type' => 'integer', 'index' => false),
                  'trg_all_id' => array('type' => 'integer', 'index' => false),
                  'trg_ply_id' => array('type' => 'integer', 'index' => false),
                  'trg_twn_n'  => array('type' => 'keyword', 'index' => false),
                  'trg_all_n'  => array('type' => 'keyword', 'index' => false),
                  'trg_ply_n'  => array('type' => 'keyword', 'index' => false),
                )
              )
            )
          )
        );

        $ElasticsearchClient->indices()->create($aIndexParams);
        return true;
      } catch (\Exception $e) {
        Logger::error('Error ensuring index: '.$IndexName.'. (' . $e->getMessage() . ')');
        return false;
      }
    } else {
      return true;
    }
  }

  /**
   * Takes a raw command document from elastiscsearch and formats it
   * @param $aSource
   * @param $esID
   * @param false $bMinimal
   * @return mixed
   */
  public static function RenderCommandDocument($aSource, $esID, bool $bMinimal = false)
  {
    $aData = $aSource;

    // GET postprocessing
    $aData['es_id'] = $esID;
    if (key_exists('cancel_human', $aData) && $aData['cancel_human'] != '') {
      $aData['cancelable'] = true;
    }
    if (!key_exists('is_planned', $aData)) {
      $aData['is_planned'] = false;
    }

    // Parse comments
    if (key_exists('comments', $aData) && is_array($aData['comments'])) {
      $aComments = array();
      foreach ($aData['comments'] as $comment) {
        $aComments[] = self::DecodeCommandComment($comment);
      }
      $aData['comments'] = $aComments;
    }

    // Units
    if (key_exists('units', $aData) && strlen($aData['units']) > 0) {
      $Encoded = $aData['units'];
      $aData['units'] = json_decode($Encoded, true);
      if (key_exists('griffin', $aData['units']) && key_exists('manticore', $aData['units']) && $aData['units']['griffin'] > 0 && $aData['units']['manticore'] > 0 ) {
        Logger::error("OPS: check invalid units response: ".json_encode($Encoded));
      }
    }

    return $aData;
  }

  public static function EncodeCommandComment($UserName, $TimeHuman, $TextContent)
  {
    return base64_encode(json_encode(array(
      'user' => $UserName,
      'time' => $TimeHuman,
      'text' => $TextContent,
    )));
  }

  public static function DecodeCommandComment($EncodedComment)
  {
    return json_decode(base64_decode($EncodedComment));
  }

}

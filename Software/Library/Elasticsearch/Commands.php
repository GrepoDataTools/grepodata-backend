<?php

namespace Grepodata\Library\Elasticsearch;

use Carbon\Carbon;
use Elasticsearch\ClientBuilder;
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
    $UpdatedAt = time();

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
          foreach ($aCommand as $Key => $Value) {
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
        $CancelHuman = '';
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

        if ($CommandType == 'revolt' || strpos('colonization', $CommandType) !== false) {
          error_log(json_encode($aCommand));
        }

        // Subtype
        $subtype = 'default';
        if (key_exists('is_attack_spot', $aCommand) && $aCommand['is_attack_spot'] === true) {
          $subtype = 'attack_spot';
        } elseif (key_exists('is_quest', $aCommand) && $aCommand['is_quest'] === true) {
          $subtype = 'quest';
        } elseif (key_exists('is_temple', $aCommand) && $aCommand['is_temple'] === true) {
          $subtype = 'temple';
        } else if ($CommandType == 'attack_takeover' && key_exists('command_type', $aCommand)) {
          // takeover actual
          // if travelling cs, then command_type is 'command'
          // if landed cs, then command_type is 'attack_takeover'
          $subtype = $aCommand['command_type'];
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
        $TargetTown = $aCommand['destination_town_name']??'';
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
          'started_at' => $aCommand['started_at']??0,
          'upload_uid' => $UserId,
          'upload_id'  => $PlayerId,
          'upload_n'   => $PlayerName,
          'cmd_id'     => $aCommand['id'],
          'type'       => $CommandType,
          'subtype'    => $subtype??'default',
          'own_command' => $aCommand['own_command']??true,
          'return'     => $aCommand['return']===true,
          'attacking_strategy' => $aCommand['attacking_strategies'][0]??'regular',
          'units'      => $Units,
          'comments'   => $aComments,
          'delete_status' => '',
          'src_twn_id' => $aCommand['origin_town_id']??0,
          'src_all_id' => $aCommand['origin_town_player_alliance_id']??0,
          'src_ply_id' => $aCommand['origin_town_player_id']??0,
          'src_twn_n'  => $aCommand['origin_town_name']??'',
          'src_all_n'  => $aCommand['origin_town_player_alliance_name']??'',
          'src_ply_n'  => $aCommand['origin_town_player_name']??$aCommand['origin_player_name']??'',
          'trg_twn_id' => $aCommand['destination_town_id']??0,
          'trg_all_id' => $aCommand['destination_town_player_alliance_id']??0,
          'trg_ply_id' => $aCommand['destination_town_player_id']??0,
          'trg_twn_n'  => $TargetTown,
          'trg_all_n'  => $aCommand['destination_town_player_alliance_name']??'',
          'trg_ply_n'  => $aCommand['destination_town_player_name']??'',
        );
        $aParams['body'][] = $aParsedCommand;
        $bHasRecords = true;
        $NumUploaded += 1;

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
          $bCaughtError = true;
        } else if (isset($aItem['create']['error'])) {
          $NumUploaded -= 1; // create failed so increment is undone
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

    if (count($aRetryAsUpdateIds) <= 0){
      // No updates required, all creates succeeded
      error_log("No retries needed");
      return $NumUploaded;
    }

    try {
      // scenario 1: units changed after spell was applied to command --> doc exists but units update is required
      // scenario 2: hidden command reappears, but user already hard deleted the original upload --> doc exists but delete status needs to be reset

      // Retry the failed create documents using an update query
      $aRetryParams = array(
        'index' => self::IndexIdentifier,
        'type'  => self::TypeCommand,
        'body'  => array()
      );
      error_log("Num retries: ".count($aRetryAsUpdateIds));
      foreach ($aRetryAsUpdateIds as $_id) {
        if (!key_exists($_id, $aParsedNewCommands)) {
          continue;
        }
        $aCommand = $aParsedNewCommands[$_id];

        // For the retry, we use a script update operation instead of a create operation
        $aRetryParams['body'][] = array(
          'update' => array(
            '_index' => self::IndexIdentifier,
            '_type'  => self::TypeCommand,
            '_id'    => $_id,
          )
        );

        // Command may have reappeared after being hidden or the units might have changed after a spell was applied
        // script update (only deletion status, units and updated_at should be changed; the original doc is maintained in case the command reappears later)
        // https://www.elastic.co/guide/en/elasticsearch/painless/current/painless-lang-spec.html
        $aRetryParams['body'][] = array(
          'script' => array(
            'source' => "
            boolean noop = true; 
            if (ctx._source.delete_status == 'hard') { 
              ctx._source.delete_status = ''; 
              noop = false
            }
            if (ctx._source.units != params.units) { 
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
      if (isset($aRetryResponse['errors']) && $aRetryResponse['errors'] == true) {
        foreach ($aRetryResponse['items'] as $aItem) {
          if (isset($aItem['update']['error'])) {
            Logger::warning("OPS: ES command batch update errors: " . json_encode($aItem['update']['error']));
          }
        }
      }

    } catch (\Exception $e) {
      Logger::warning('OPS: Error retrying bulk update: '.$e->getMessage());
    }

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

    error_log("OPS: delete by query result: ".json_encode($aResponse));

    $NumUpdated = 0;
    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      $NumUpdated = $aResponse['total'];
    } else {
      Logger::warning("OPS: unexpected update by query result; ". json_encode($aResponse));
    }

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
      throw new \Exception('Unable to delete old diff records in elasticsearch. invalid response.');
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
     *
     * _id --> {arrival_at}{cmd_id}{team}
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
                  'type'    => array('type' => 'keyword', 'index' => false),
                  'subtype' => array('type' => 'keyword', 'index' => false),
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
      $aData['units'] = json_decode($aData['units'], true);
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
<?php

namespace Grepodata\Library\Elasticsearch;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Alliance;
use Grepodata\Library\Model\Player;
use Grepodata\Library\Model\Town;

class Import
{
  const IndexIdentifier = "grepodata_dev";
  const IndexTown       = "grepodata_towns";
  const TypePlayer      = "player";
  const TypeAlliance    = "alliance";
  const TypeTown        = "town";

  public static function DeletePlayer(Player $oPlayer)
  {
    try {
      $_id = self::TypePlayer . '_' . $oPlayer->grep_id . '_' . $oPlayer->world;
      $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);
      $ElasticsearchClient->delete(array(
        'id'    => $_id,
        'index' => self::IndexIdentifier,
        'type'  => self::TypePlayer,
      ));
      return true;
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), '"not_found"') >= 0) {
        //Logger::warning("Unable to find player in ES with id: " . $_id . ". Error: " . $e->getMessage());
        return false;
      } else {
        Logger::warning("Error deleting player from ES: " . $e->getMessage());
        return false;
      }
    }
  }

  public static function DeleteAlliance(Alliance $oAlliance)
  {
    try {
      $_id = self::TypeAlliance . '_' . $oAlliance->grep_id . '_' . $oAlliance->world;
      $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);
      $ElasticsearchClient->delete(array(
        'id'    => $_id,
        'index' => self::IndexIdentifier,
        'type'  => self::TypeAlliance,
      ));
      return true;
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), '"not_found"') >= 0) {
        return false;
      } else {
        Logger::warning("Error deleting alliance from ES: " . $e->getMessage());
        return false;
      }
    }
  }

  public static function DeleteTowns(Town $oTown)
  {
    try {
      $_id = self::TypeTown . '_' . $oTown->grep_id . '_' . $oTown->world;
      $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);
      $ElasticsearchClient->delete(array(
        'id'    => $_id,
        'index' => self::IndexTown,
        'type'  => self::TypeTown,
      ));
      return true;
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), '"not_found"') >= 0) {
        return false;
      } else {
        Logger::warning("Error deleting town from ES: " . $e->getMessage());
        return false;
      }
    }
  }

  public static function SavePlayerBatch($aPlayerBatch)
  {
    $aParams = array(
      'index' => self::IndexIdentifier,
      'type'  => self::TypePlayer,
      'body'  => array()
    );

    foreach ($aPlayerBatch as $aPlayer) {

      /** @var Player $oPlayer */
      $oPlayer = $aPlayer['player'];
      $AllianceName = $aPlayer['alliance_name'];

      // Generate id
      $_id = self::TypePlayer . '_' . $oPlayer->grep_id . '_' . $oPlayer->world;

      // index
      $aParams['body'][] = array(
        'index' => array(
          '_index' => self::IndexIdentifier,
          '_type'  => self::TypePlayer,
          '_id'    => $_id,
        )
      );

      // body
      $Active = true;
//      if ($oPlayer->towns === 0) {
//        $Active = false;
//      }
      $aParams['body'][] = array(
        'WorldId'      => $oPlayer->world,
        'Server'       => substr($oPlayer->world, 0, 2),
        'GrepId'       => $oPlayer->grep_id,
        'Name'         => $oPlayer->name,
        'AllianceId'   => $oPlayer->alliance_id,
        'AllianceName' => $AllianceName,
        'Rank'         => $oPlayer->rank,
        'Points'       => $oPlayer->points,
        'Att'          => $oPlayer->att,
        'Def'          => $oPlayer->def,
        'AttDef'       => $oPlayer->att + $oPlayer->def,
        'Towns'        => $oPlayer->towns,
        'Active'       => $Active,
      );
    }

    // Upload to elasticsearch
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);
    $ElasticsearchClient->bulk($aParams);

    return true;
  }

  public static function SaveTownBatch($aTownBatch)
  {
    $aParams = array(
      'index' => self::IndexIdentifier,
      'type'  => self::TypePlayer,
      'body'  => array()
    );

    /** @var Town $oTown */
    foreach ($aTownBatch as $oTown) {

      // Generate id
      $_id = self::TypeTown . '_' . $oTown->grep_id . '_' . $oTown->world;

      // index
      $aParams['body'][] = array(
        'index' => array(
          '_index' => self::IndexTown,
          '_type'  => self::TypeTown,
          '_id'    => $_id,
        )
      );

      // body
      $aParams['body'][] = array(
        'WorldId'   => $oTown->world,
        'Server'    => substr($oTown->world, 0, 2),
        'GrepId'    => $oTown->grep_id,
        'PlayerId'  => $oTown->player_id,
        'island_x'  => $oTown->island_x,
        'island_y'  => $oTown->island_y,
        'island_i'  => $oTown->island_i,
        'Points'    => $oTown->points,
        'Name'      => $oTown->name,
      );
    }

    // Upload to elasticsearch
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);
    $ElasticsearchClient->bulk($aParams);

    return true;
  }

  public static function SaveAlliance(Alliance $oAlliance)
  {
    self::EnsureIndex();

    // Generate id
    $_id = self::TypeAlliance . '_' . $oAlliance->grep_id . '_' . $oAlliance->world;

    // Check updated date
    $Active = true;
    if ($oAlliance->members === 0) {
      $Active = false;
    } else {
      try {
        $DateLimit = Carbon::now()->subDays(10);
        $RecordUpdated = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($oAlliance->updated_at)), 'UTC');
        if ($DateLimit > $RecordUpdated) {
          $Active = false;
        }
      } catch (\Exception $e) {
        Logger::warning("Exception checking alliance update date: " . $e->getMessage());
        return;
      }
    }

    // Build elasticsearch document
    $aElasticsearchBody = array(
      'WorldId'      => $oAlliance->world,
      'Server'       => substr($oAlliance->world, 0, 2),
      'GrepId'       => $oAlliance->grep_id,
      'Rank'         => $oAlliance->rank,
      'Points'       => $oAlliance->points,
      'Att'          => $oAlliance->att,
      'Def'          => $oAlliance->def,
      'AttDef'       => $oAlliance->att + $oAlliance->def,
      'Towns'        => $oAlliance->towns,
      'Members'      => $oAlliance->members,
      'Name'         => $oAlliance->name,
      'Active'       => $Active,
    );

//    $aServers = World::getServers();
//    foreach ($aServers as $Server) {
//      if (strpos($oAlliance->world, $Server) !== false) $aElasticsearchBody['Server'] = $Server;
//    }

    // Upload to elasticsearch
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(10);
    $ElasticsearchClient->index(
      array(
        'index' => self::IndexIdentifier,
        'type'  => self::TypeAlliance,
        'id'    => $_id,
        'body'  => $aElasticsearchBody
      )
    );
  }

  public static function EnsureIndex(&$IndexName = '', $bForceNewConnection = false)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(5, $bForceNewConnection);

    $IndexName = self::IndexIdentifier;

    if (!$ElasticsearchClient->indices()->exists(array('index' => $IndexName))) {
      // Create index
      try {
        $aIndexParams = array(
          'index' => $IndexName,
          'body'  => array(
            'settings' => array(
              'number_of_shards'    => 1,
              'number_of_replicas'  => 0,
              'analysis'  => array(
                "filter"  => array(
                  "autocomplete_filter" => array (
                    "type"      => "ngram",
                    "min_gram"  => 2,
                    "max_gram"  => 20
                  )
                ),
                "analyzer" => array (
                  "autocomplete" => array (
                    "type"      => "custom",
                    "tokenizer" => "keyword",
                    "filter"    => array (
                      "lowercase",
                      "autocomplete_filter"
                    )
                  )
                )
              )
            ),
            'mappings' => array(
              self::TypePlayer => array(
                'properties' => array(
                  'WorldId'      => array('type' => 'keyword', 'store' => true),
                  'Server'       => array('type' => 'keyword', 'store' => true),
                  'GrepId'       => array('type' => 'integer', 'store' => true),
                  'AllianceId'   => array('type' => 'integer', 'store' => true),
                  'AllianceName' => array('type' => 'keyword', 'store' => true),
                  'Rank'         => array('type' => 'integer', 'store' => true),
                  'Points'       => array('type' => 'integer', 'store' => true),
                  'Att'          => array('type' => 'integer', 'store' => true),
                  'Def'          => array('type' => 'integer', 'store' => true),
                  'AttDef'       => array('type' => 'integer', 'store' => true),
                  'Towns'        => array('type' => 'integer', 'store' => true),
                  'Active'       => array('type' => 'boolean', 'store' => true),
                  'Name'         => array(
                    'type' => 'text',
                    'store' => true,
                    'analyzer' => 'autocomplete',
                  )
                )
              ),
              self::TypeAlliance => array(
                'properties' => array(
                  'WorldId'      => array('type' => 'keyword', 'store' => true),
                  'Server'       => array('type' => 'keyword', 'store' => true),
                  'GrepId'       => array('type' => 'integer', 'store' => true),
                  'Rank'         => array('type' => 'integer', 'store' => true),
                  'Points'       => array('type' => 'integer', 'store' => true),
                  'Att'          => array('type' => 'integer', 'store' => true),
                  'Def'          => array('type' => 'integer', 'store' => true),
                  'AttDef'       => array('type' => 'integer', 'store' => true),
                  'Towns'        => array('type' => 'integer', 'store' => true),
                  'Members'      => array('type' => 'integer', 'store' => true),
                  'Active'       => array('type' => 'boolean', 'store' => true),
                  'Name'         => array(
                    'type' => 'text',
                    'store' => true,
                    'analyzer' => 'autocomplete',
                  )
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

  public static function EnsureTownIndex(&$IndexName = '', $bForceNewConnection = false)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(5, $bForceNewConnection);

    $IndexName = self::IndexTown;

    if (!$ElasticsearchClient->indices()->exists(array('index' => $IndexName))) {
      // Create index
      try {
        $aIndexParams = array(
          'index' => $IndexName,
          'body'  => array(
            'settings' => array(
              'number_of_shards'    => 1,
              'number_of_replicas'  => 0,
              'analysis'  => array(
                "filter"  => array(
                  "autocomplete_filter" => array (
                    "type"      => "ngram",
                    "min_gram"  => 2,
                    "max_gram"  => 20
                  )
                ),
                "analyzer" => array (
                  "autocomplete" => array (
                    "type"      => "custom",
                    "tokenizer" => "keyword",
                    "filter"    => array (
                      "lowercase",
                      "autocomplete_filter"
                    )
                  )
                )
              )
            ),
            'mappings' => array(
              self::TypeTown => array(
                'properties' => array(
                  'WorldId'      => array('type' => 'keyword', 'store' => true),
                  'Server'       => array('type' => 'keyword', 'store' => true),
                  'GrepId'       => array('type' => 'integer', 'store' => true),
                  'PlayerId'     => array('type' => 'integer', 'store' => true),
                  'island_x'     => array('type' => 'integer', 'store' => true),
                  'island_y'     => array('type' => 'integer', 'store' => true),
                  'island_i'     => array('type' => 'integer', 'store' => true),
                  'Points'       => array('type' => 'integer', 'store' => true),
                  'Name'         => array(
                    'type' => 'text',
                    'store' => true,
                    'analyzer' => 'autocomplete',
                  )
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

}
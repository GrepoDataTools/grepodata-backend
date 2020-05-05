<?php

namespace Grepodata\Library\Elasticsearch;

use Carbon\Carbon;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Alliance;
use Grepodata\Library\Model\Player;

class Diff
{
  const IndexIdentifier = "diff_grepodata";
  const TypeAtt         = "att_diff";
  const TypeDef         = "def_diff";

  public static function SaveAttDiff(Player $oPlayer, $Diff, $Date, $DayOfWeek, $HourOfDay)
  {
    self::EnsureIndex();

    // Build elasticsearch document
    $aElasticsearchBody = array(
      'WorldId'      => $oPlayer->world,
      'Server'       => substr($oPlayer->world, 0, 2),
      'PlayerId'     => $oPlayer->grep_id,
      'PlayerName'   => $oPlayer->name,
      'AllianceId'   => $oPlayer->alliance_id,
      'Diff'         => $Diff,
      'Date'         => $Date->timestamp,
      'DayOfWeek'    => $DayOfWeek,
      'HourOfDay'    => $HourOfDay,
    );

    // Upload to elasticsearch
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(5);
    $ElasticsearchClient->index(
      array(
        'index' => self::IndexIdentifier,
        'type'  => self::TypeAtt,
        'body'  => $aElasticsearchBody
      )
    );

    return $HourOfDay;
  }

  public static function SaveDefDiff(Player $oPlayer, $Diff, $Date, $DayOfWeek, $HourOfDay)
  {
    self::EnsureIndex();

    // Build elasticsearch document
    $aElasticsearchBody = array(
      'WorldId'      => $oPlayer->world,
      'Server'       => substr($oPlayer->world, 0, 2),
      'PlayerId'     => $oPlayer->grep_id,
      'PlayerName'   => $oPlayer->name,
      'AllianceId'   => $oPlayer->alliance_id,
      'Diff'         => $Diff,
      'Date'         => $Date->timestamp,
      'DayOfWeek'    => $DayOfWeek,
      'HourOfDay'    => $HourOfDay,
    );

    // Upload to elasticsearch
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(5);
    $ElasticsearchClient->index(
      array(
        'index' => self::IndexIdentifier,
        'type'  => self::TypeDef,
        'body'  => $aElasticsearchBody
      )
    );

    return $HourOfDay;
  }

  public static function GetMostRecentDiffs(\Grepodata\Library\Model\World $oWorld, $Limit = 10)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find hour limit
    $Date = $oWorld->getServerTime();
    $Date->subHours(2);

    $aSearchParams = array(
      'size' => $Limit,
      'sort' => array(
        'Diff'=>'desc',
      ),
      'query' => array(
        'bool' => array(
          'must' => array(
            array(
              'range' => array(
                'Date' => array(
                  'gte' => $Date->timestamp
                )
              )
            ),
            array(
              'match' => array(
                'WorldId' => $oWorld->grep_id
              )
            ),
            array(
              'range' => array(
                'Diff' => array(
                  'gte' => 600
                )
              )
            ),
          )
        )
      ),
    );
    $aAttDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeAtt,
      'body' => $aSearchParams
    ));
    $aDefDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeDef,
      'body' => $aSearchParams
    ));
    
    $aResponse = array();
    if (isset($aAttDiffs['hits']['total']) && $aAttDiffs['hits']['total'] > 0) {
      $aResponse['att'] = array();
      foreach ($aAttDiffs['hits']['hits'] as $aHit) {
        $aResponse['att'][] = array(
          'id' => $aHit['_source']['PlayerId'],
          'name' => $aHit['_source']['PlayerName'],
          'diff' => $aHit['_source']['Diff'],
        );
      }
    }
    if (isset($aDefDiffs['hits']['total']) && $aDefDiffs['hits']['total'] > 0) {
      $aResponse['def'] = array();
      foreach ($aDefDiffs['hits']['hits'] as $aHit) {
        $aResponse['def'][] = array(
          'id' => $aHit['_source']['PlayerId'],
          'name' => $aHit['_source']['PlayerName'],
          'diff' => $aHit['_source']['Diff'],
        );
      }
    }

    return $aResponse;
  }

  public static function GetDiffsByHour(\Grepodata\Library\Model\World $oWorld, Carbon $Day, $HourOfDay, $Limit=100, $MinValue=5)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find hour limit
    $DayOfWeek = $Day->dayOfWeek;
    $StartDate = $Day;
    $EndDate = $StartDate->copy();
    $StartDate->subHours(12);
    $EndDate->addHours(36);

    // convert midnight day of week
    if ($HourOfDay == "0" || $HourOfDay == "24") {
      $HourOfDay = "0";
      $DayOfWeek = ($DayOfWeek + 1) % 7;
    } else if ($HourOfDay == "1") {
      $DayOfWeek = ($DayOfWeek + 1) % 7;
    }

    $aSearchParams = array(
      'size' => $Limit,
      'sort' => array(
        'Diff'=>'desc',
      ),
      'query' => array(
        'bool' => array(
          'must' => array(
            array(
              'range' => array(
                'Date' => array(
                  'gte' => $StartDate->timestamp,
                  'lt' => $EndDate->timestamp
                )
              )
            ),
            array(
              'range' => array(
                'Diff' => array(
                  'gte' => $MinValue
                )
              )
            ),
            array(
              'match' => array(
                'WorldId' => $oWorld->grep_id
              )
            ),
            array(
              'match' => array(
                'DayOfWeek' => $DayOfWeek
              )
            ),
            array(
              'match' => array(
                'HourOfDay' => $HourOfDay
              )
            ),
          )
        )
      ),
    );
    $aAttDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeAtt,
      'body' => $aSearchParams
    ));
    $aDefDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeDef,
      'body' => $aSearchParams
    ));

    $aResponse = array(
      'att' => array(),
      'def' => array(),
    );

    if (isset($aAttDiffs['hits']['total']) && $aAttDiffs['hits']['total'] > 0) {
      foreach ($aAttDiffs['hits']['hits'] as $aHit) {
        if (isset($aResponse['att'][$aHit['_source']['PlayerId']])) {
          $aResponse['att'][$aHit['_source']['PlayerId']]['value'] += $aHit['_source']['Diff'];
        } else {
          $aResponse['att'][$aHit['_source']['PlayerId']] = array(
            'id' => $aHit['_source']['PlayerId'],
            'name' => $aHit['_source']['PlayerName'],
            'alliance_id' => $aHit['_source']['AllianceId'],
            'value' => $aHit['_source']['Diff'],
          );
        }
      }
    }
    uasort($aResponse['att'], function ($Player1, $Player2) {
      // sort by score descending
      return $Player1['value'] > $Player2['value'] ? -1 : 1;
    });
    $aResponse['att'] = array_values($aResponse['att']);
    
    if (isset($aDefDiffs['hits']['total']) && $aDefDiffs['hits']['total'] > 0) {
      foreach ($aDefDiffs['hits']['hits'] as $aHit) {
        if (isset($aResponse['def'][$aHit['_source']['PlayerId']])) {
          $aResponse['def'][$aHit['_source']['PlayerId']]['value'] += $aHit['_source']['Diff'];
        } else {
          $aResponse['def'][$aHit['_source']['PlayerId']] = array(
            'id'          => $aHit['_source']['PlayerId'],
            'name'        => $aHit['_source']['PlayerName'],
            'alliance_id' => $aHit['_source']['AllianceId'],
            'value'       => $aHit['_source']['Diff'],
          );
        }
      }
    }
    uasort($aResponse['def'], function ($Player1, $Player2) {
      // sort by score descending
      return $Player1['value'] > $Player2['value'] ? -1 : 1;
    });
    $aResponse['def'] = array_values($aResponse['def']);

    return $aResponse;
  }

  public static function GetPlayerDiffsByDay(\Grepodata\Library\Model\World $oWorld, Carbon $Day, Player $oPlayer)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find hour limit
    $DayOfWeek = $Day->dayOfWeek;
    $DayOfWeekNext = ($Day->dayOfWeek + 1) % 7;
    $StartDate = $Day;
    $EndDate = $StartDate->copy();
    $StartDate->subHours(12);
    $EndDate->addHours(36);

    $aSearchParams = array(
      'sort' => array(
        'Date'=>'asc',
      ),
      'from' => 0,
      'size' => 100,
      'query' => array(
        'bool' => array(
          'must' => array(
            array(
              'range' => array(
                'Date' => array(
                  'gte' => $StartDate->timestamp,
                  'lt' => $EndDate->timestamp
                )
              )
            ),
            array(
              'match' => array(
                'WorldId' => $oWorld->grep_id
              )
            ),
            array(
              'bool' => array(
                'should' => array(
                  array(
                    'bool' => array(
                      'must' => array(
                        array(
                          'match' => array(
                            'DayOfWeek' => $DayOfWeek
                          )
                        ),
                        array(
                          'range' => array(
                            'HourOfDay' => array(
                              'gte' => 2
                            )
                          )
                        )
                      )
                    )
                  ),
                  array(
                    'bool' => array(
                      'must' => array(
                        array(
                          'match' => array(
                            'DayOfWeek' => $DayOfWeekNext
                          )
                        ),
                        array(
                          'range' => array(
                            'HourOfDay' => array(
                              'gte' => 0,
                              'lt' => 2
                            )
                          )
                        )
                      )
                    )
                  ),
                )
              )
            ),
            array(
              'match' => array(
                'PlayerId' => $oPlayer->grep_id
              )
            ),
          )
        )
      ),
    );
    $aAttDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeAtt,
      'body' => $aSearchParams
    ));
    $aDefDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeDef,
      'body' => $aSearchParams
    ));

    $aCombined = array();
    $bInsertEmptyAtt = true;
    if (isset($aAttDiffs['hits']['total']) && $aAttDiffs['hits']['total'] > 0) {
      foreach ($aAttDiffs['hits']['hits'] as $aHit) {
        if (isset($aCombined[$aHit['_source']['HourOfDay']])) {
          $aCombined[$aHit['_source']['HourOfDay']]['att'] += $aHit['_source']['Diff'];
        } else {
          $aCombined[$aHit['_source']['HourOfDay']] = array('att' => $aHit['_source']['Diff']);
        }
      }
    }
    if (isset($aDefDiffs['hits']['total']) && $aDefDiffs['hits']['total'] > 0) {
      foreach ($aDefDiffs['hits']['hits'] as $aHit) {
        if (isset($aCombined[$aHit['_source']['HourOfDay']])) {
          if (key_exists('def', $aCombined[$aHit['_source']['HourOfDay']])) {
            $aCombined[$aHit['_source']['HourOfDay']]['def'] += $aHit['_source']['Diff'];
          } else {
            $aCombined[$aHit['_source']['HourOfDay']]['def'] = $aHit['_source']['Diff'];
          }
        } else {
          $aDef = array();
          if ($bInsertEmptyAtt === true) {
            $aDef['att'] = 0;
          }
          $aDef['def'] = $aHit['_source']['Diff'];
          $aCombined[$aHit['_source']['HourOfDay']] = $aDef;
          $bInsertEmptyAtt = false;
        }
      }
    }

    // We need to sort because inserting def records may have jumbled the order
    ksort($aCombined);

    // Move 0 and 1 back to the end of the array
    foreach (array(0, 1) as $Hour) {
      if (isset($aCombined[$Hour])) {
        $v = $aCombined[$Hour];
        unset($aCombined[$Hour]);
        $aCombined[$Hour] = $v;
      }
    }

    $aResponse = array();
    foreach ($aCombined as $Hour => $aDiffs) {
      $aSeries = array();
      if (isset($aDiffs['att'])) $aSeries[] = array("name" => 'Attacking', "value" => $aDiffs['att']);
      if (isset($aDiffs['def'])) $aSeries[] = array("name" => 'Defending', "value" => $aDiffs['def']);
      if ($Hour == 0) {
        $Hour = 24;
      }
      $aResponse[] = array(
          "name" => (strlen($Hour) == 1 ? '0':'') . $Hour . ':00',
          "series" => $aSeries
        );
    }

    return $aResponse;
  }

  public static function GetAllianceDiffsByDay(\Grepodata\Library\Model\World $oWorld, Carbon $Day, Alliance $oAlliance)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find hour limit
    $DayOfWeek = $Day->dayOfWeek;
    $DayOfWeekNext = ($Day->dayOfWeek + 1) % 7;
    $StartDate = $Day;
    $EndDate = $StartDate->copy();
    $StartDate->subHours(12);
    $EndDate->addHours(36);

    $aSearchParams = array(
      'sort' => array(
        'Date'=>'asc',
      ),
      'from' => 0,
      'size' => 3000,
      'query' => array(
        'bool' => array(
          'must' => array(
            array(
              'range' => array(
                'Date' => array(
                  'gte' => $StartDate->timestamp,
                  'lt' => $EndDate->timestamp
                )
              )
            ),
            array(
              'match' => array(
                'WorldId' => $oWorld->grep_id
              )
            ),
            array(
              'bool' => array(
                'should' => array(
                  array(
                    'bool' => array(
                      'must' => array(
                        array(
                          'match' => array(
                            'DayOfWeek' => $DayOfWeek
                          )
                        ),
                        array(
                          'range' => array(
                            'HourOfDay' => array(
                              'gte' => 2
                            )
                          )
                        )
                      )
                    )
                  ),
                  array(
                    'bool' => array(
                      'must' => array(
                        array(
                          'match' => array(
                            'DayOfWeek' => $DayOfWeekNext
                          )
                        ),
                        array(
                          'range' => array(
                            'HourOfDay' => array(
                              'gte' => 0,
                              'lt' => 2
                            )
                          )
                        )
                      )
                    )
                  ),
                )
              )
            ),
            array(
              'match' => array(
                'AllianceId' => $oAlliance->grep_id
              )
            ),
          )
        )
      ),
    );
    $aAttDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeAtt,
      'body' => $aSearchParams
    ));
    $aDefDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeDef,
      'body' => $aSearchParams
    ));

    $aCombined = array();
    $bInsertEmptyAtt = true;
    if (isset($aAttDiffs['hits']['total']) && $aAttDiffs['hits']['total'] > 0) {
      foreach ($aAttDiffs['hits']['hits'] as $aHit) {
        if (isset($aCombined[$aHit['_source']['PlayerName']])) {
          $aCombined[$aHit['_source']['PlayerName']]['att'] += $aHit['_source']['Diff'];
        } else {
          $aCombined[$aHit['_source']['PlayerName']] = array(
            'id' => $aHit['_source']['PlayerId'],
            'att' => $aHit['_source']['Diff']
          );
        }
      }
    }
    if (isset($aDefDiffs['hits']['total']) && $aDefDiffs['hits']['total'] > 0) {
      foreach ($aDefDiffs['hits']['hits'] as $aHit) {
        if (isset($aCombined[$aHit['_source']['PlayerName']])) {
          if (key_exists('def', $aCombined[$aHit['_source']['PlayerName']])) {
            $aCombined[$aHit['_source']['PlayerName']]['def'] += $aHit['_source']['Diff'];
          } else {
            $aCombined[$aHit['_source']['PlayerName']]['def'] = $aHit['_source']['Diff'];
          }
        } else {
          $aDef = array(
            'id' => $aHit['_source']['PlayerId'],
            );
          if ($bInsertEmptyAtt === true) {
            $aDef['att'] = 0;
          }
          $aDef['def'] = $aHit['_source']['Diff'];
          $aCombined[$aHit['_source']['PlayerName']] = $aDef;
          $bInsertEmptyAtt = false;
        }
      }
    }

    // Sort by score desc
    uasort($aCombined, function ($Player1, $Player2) {
      // sort by score descending
      return ($Player1['att']??0)+($Player1['def']??0) > ($Player2['att']??0)+($Player2['def']??0) ? -1 : 1;
    });

    // Format response for ngx-charts data
    $aResponse = array();
    foreach ($aCombined as $Player => $aDiffs) {
      $aSeries = array();
      if (isset($aDiffs['att'])) $aSeries[] = array("name" => 'Attacking', "value" => $aDiffs['att']);
      if (isset($aDiffs['def'])) $aSeries[] = array("name" => 'Defending', "value" => $aDiffs['def']);
      $aResponse[] = array(
          "name" => $Player,
          "id" => $aDiffs['id']??0,
          "series" => $aSeries
        );
    }

    return $aResponse;
  }

  /**
   * Returns the day of week and hour of day aggregations for the given player
   * @param Player $oPlayer
   * @return array
   */
  public static function GetAttDiffHeatmapByPlayer(\Grepodata\Library\Model\Player $oPlayer)
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find diff heatmap
    $aSearchParams = array(
      "query" => array(
        "bool" => array(
          "must" => array(
            array("match" => array("PlayerId" => $oPlayer->grep_id)),
            array("match" => array("Server"   => substr($oPlayer->world, 0, 2))),
            array("range" => array("Date"     => array("gte" => strtotime("-4 week"))))
          )
        )
      ),
      "aggregations" => array(
        "day" => array("terms"=>array("field"=>"DayOfWeek", "size"=>10)),
        "hour" => array("terms"=>array("field"=>"HourOfDay", "size"=>30)),
      )
    );
    $aAttDiffs = $ElasticsearchClient->search(array(
      'index' => $IndexName,
      'type' => self::TypeAtt,
      'body' => $aSearchParams
    ));

    $aResponse = array();
    if (isset($aAttDiffs['hits']['total']) && $aAttDiffs['hits']['total'] > 0 && isset($aAttDiffs['aggregations']['hour']['buckets'])) {
      $aResponse['hour'] = array();
      foreach ($aAttDiffs['aggregations']['hour']['buckets'] as $aHour) {
        if ($aHour['key'] >= 0) {
          $aResponse['hour'][$aHour['key']] = $aHour['doc_count'];
        }
      }
      $aResponse['day'] = array();
      foreach ($aAttDiffs['aggregations']['day']['buckets'] as $aHour) {
        $aResponse['day'][$aHour['key']] = $aHour['doc_count'];
      }
    }

    return $aResponse;
  }

  /**
   * Clean old att diff records in elasticsearch for all worlds
   * @param string $Timespan
   * @return bool
   * @throws \Exception
   */
  public static function CleanAttDiffRecords($Timespan = "-5 week")
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find diff heatmap
    $Limit = strtotime($Timespan);
    if ($Limit == false || $Limit < 0) {
      throw new \Exception("Invalid timestamp for diff cleanup: ".json_encode($Limit));
    }

    $aSearchParams = array(
      "query" => array(
        "bool" => array(
          "must" => array(
            array("range" => array("Date" => array("lt" => $Limit)))
          )
        )
      )
    );
    $aResponse = $ElasticsearchClient->deleteByQuery(array(
      'index' => $IndexName,
      'type' => self::TypeAtt,
      'body' => $aSearchParams
    ));

    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      return $aResponse['total'];
    } else {
      throw new \Exception("Unable to delete old diff records in elasticsearch. invalid response.");
    }
  }

  /**
   * Clean old def diff records in elasticsearch for all worlds
   * @param string $Timespan
   * @return bool
   * @throws \Exception
   */
  public static function CleanDefDiffRecords($Timespan = "-5 week")
  {
    $ElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    $IndexName = self::IndexIdentifier;

    // Find diff heatmap
    $Limit = strtotime($Timespan);
    if ($Limit == false || $Limit < 0) {
      throw new \Exception("Invalid timestamp for diff cleanup: ".json_encode($Limit));
    }

    $aSearchParams = array(
      "query" => array(
        "bool" => array(
          "must" => array(
            array("range" => array("Date" => array("lt" => $Limit)))
          )
        )
      )
    );
    $aResponse = $ElasticsearchClient->deleteByQuery(array(
      'index' => $IndexName,
      'type' => self::TypeDef,
      'body' => $aSearchParams
    ));

    if ($aResponse !== false && is_array($aResponse) && isset($aResponse['total']) && $aResponse['total'] >= 0) {
      return $aResponse['total'];
    } else {
      throw new \Exception("Unable to delete old diff records in elasticsearch. invalid response.");
    }
  }

  public static function EnsureIndex(&$IndexName = '')
  {
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
              self::TypeAtt => array(
                'properties' => array(
                  'WorldId'      => array('type' => 'keyword', 'store' => true),
                  'Server'       => array('type' => 'keyword', 'store' => true),
                  'PlayerId'     => array('type' => 'integer', 'store' => true),
                  'PlayerName'   => array('type' => 'keyword', 'store' => true),
                  'AllianceId'   => array('type' => 'integer', 'store' => true),
                  'Diff'         => array('type' => 'integer', 'store' => true),
                  'Date'         => array('type' => 'long', 'store' => true),
                  'DayOfWeek'    => array('type' => 'integer', 'store' => true),
                  'HourOfDay'    => array('type' => 'integer', 'store' => true),
                )
              ),
              self::TypeDef => array(
                'properties' => array(
                  'WorldId'      => array('type' => 'keyword', 'store' => true),
                  'Server'       => array('type' => 'keyword', 'store' => true),
                  'PlayerId'     => array('type' => 'integer', 'store' => true),
                  'PlayerName'   => array('type' => 'keyword', 'store' => true),
                  'AllianceId'   => array('type' => 'integer', 'store' => true),
                  'Diff'         => array('type' => 'integer', 'store' => true),
                  'Date'         => array('type' => 'long', 'store' => true),
                  'DayOfWeek'    => array('type' => 'integer', 'store' => true),
                  'HourOfDay'    => array('type' => 'integer', 'store' => true),
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
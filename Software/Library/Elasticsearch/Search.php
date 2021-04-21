<?php

namespace Grepodata\Library\Elasticsearch;

use Grepodata\Library\Cron\Common;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Alliance;
use Grepodata\Library\Model\Player;
use Grepodata\Library\Model\World;

class Search
{
  const IndexIdentifier = "grepodata_dev";
  const IndexTowns      = "grepodata_towns";
  const TypePlayer      = "player";
  const TypeAlliance    = "alliance";
  const TypeTown        = "town";

  public static function FindPlayers($aOptions = array(), $buildForm = false)
  {
    $aElasticsearchResult = self::SearchPlayersEs($aOptions);
    $aAggResult = $aElasticsearchResult;

    // Build aggregation form using only the query if custom filters are used
    if ($buildForm && isset($aOptions['query']) && (isset($aOptions['world']) || isset($aOptions['server']))) {
      $aAggResult = self::SearchPlayersEs(array('query' => $aOptions['query']));
    }

    $aSearchOutput = array(
      'success' => true,
      'count'   => $aElasticsearchResult['hits']['total'],
      'results' => self::RenderPlayerResults($aElasticsearchResult),
      'form'    => self::RenderForm($aAggResult),
    );

    return $aSearchOutput;
  }

  public static function FindAlliances($aOptions = array())
  {
    $oElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    if (!$oElasticsearchClient->indices()->exists(array('index' => self::IndexIdentifier))) return false;

    // Initial search setup
    $aSearchParams = array(
      'size'  => 30,
      'from'  => 0,
      'sort'  => array(
        array(
          'Points' => array(
            'order' => 'desc'
          )
        )
      ),
      'query' => array(
        'bool' => array(
          'must' => array(
            'match_all' => (object)[]
          ),
          'filter' => array()
        )
      ),
      'aggregations'  => array(
        'world'       => array('terms' => array('field' => 'WorldId', 'size' => 100)),
        'server'      => array('terms' => array('field' => 'Server', 'size' => 30)),
        'max_points'  => array('max'   => array('field' => 'Points')),
        'min_points'  => array('min'   => array('field' => 'Points')),
        'max_members' => array('max'   => array('field' => 'Members')),
        'min_members' => array('min'   => array('field' => 'Members')),
        'max_towns'   => array('max'   => array('field' => 'Towns')),
        'min_towns'   => array('min'   => array('field' => 'Towns')),
        'max_rank'    => array('max'   => array('field' => 'Rank')),
        'min_rank'    => array('min'   => array('field' => 'Rank')),
      )
    );

    // Size filter
    if (isset($aOptions['from'])) $aSearchParams['from'] = $aOptions['from'];
    if (isset($aOptions['size']) && $aOptions['size'] <= 200) $aSearchParams['size'] = $aOptions['size'];

    // Optional query
    if (isset($aOptions['query'])) {
      $aSearchParams['query']['bool']['must'] = array(
        'fuzzy' => array(
          'Name' => array(
            'value' => $aOptions['query'],
            'boost' => 1.0,
            'fuzziness' => 2,
            'prefix_length' => 4,
            'max_expansions' => 50,
          )
        )
      );
    }

    // Sort filters
    if (isset($aOptions['sort_field'])) {
      if (in_array($aOptions['sort_field'], ['Points', 'Rank', 'Towns', 'Att', 'Def', 'Members', 'AttDef'])) {
        $aSort = array(
          $aOptions['sort_field'] => array(
            'order' => ((isset($aOptions['sort_order'])) ? $aOptions['sort_order'] : 'desc')
          )
        );
        $aSearchParams['sort'] = array($aSort);
      }
    }

    // Optional active filter
    if (isset($aOptions['active']) && $aOptions['active'] === 'true') {
      $aSearchParams['query']['bool']['filter'][] = array( 'term' => array( 'Active' => true) );
    }

    // Optional server filter
    if (isset($aOptions['server'])) {
      $aSearchParams['query']['bool']['filter'][] = array( 'term' => array( 'Server' => $aOptions['server']) );
    }

    // Optional world filter
    if (isset($aOptions['world'])) {
      $aSearchParams['query']['bool']['filter'][] = array( 'term' => array( 'WorldId' => $aOptions['world']) );
    } else {
      // Check active worlds only
      $aWorlds = Common::getAllActiveWorlds(false);
      if ($aWorlds !== false) {
        $aWorldFilters = array('bool'=>array('should'=>array()));
        /** @var World $oWorld */
        foreach ($aWorlds as $oWorld) {
          $aWorldFilters['bool']['should'][] = array(
            'term' => array( 'WorldId' => $oWorld->grep_id)
          );
        }
        $aSearchParams['query']['bool']['filter'][] = $aWorldFilters;
      }
    }

    // Optional rank filter
    if (isset($aOptions['min_rank']) || isset($aOptions['max_rank'])) {
      $aFilter = array('range' => array('Rank' => array()));
      if (isset($aOptions['min_rank'])) $aFilter['range']['Rank']['gte'] = $aOptions['min_rank'];
      if (isset($aOptions['max_rank'])) $aFilter['range']['Rank']['le']  = $aOptions['max_rank'];
      $aSearchParams['query']['bool']['filter'][] = $aFilter;
    }

    // Optional cities filter
    if (isset($aOptions['min_towns']) || isset($aOptions['max_towns'])) {
      $aFilter = array('range' => array('Towns' => array()));
      if (isset($aOptions['min_towns'])) $aFilter['range']['Towns']['gte'] = $aOptions['min_towns'];
      if (isset($aOptions['max_towns'])) $aFilter['range']['Towns']['le']  = $aOptions['max_towns'];
      $aSearchParams['query']['bool']['filter'][] = $aFilter;
    }

    // Optional points filter
    if (isset($aOptions['min_points']) || isset($aOptions['max_points'])) {
      $aFilter = array('range' => array('Points' => array()));
      if (isset($aOptions['min_points'])) $aFilter['range']['Points']['gte'] = $aOptions['min_points'];
      if (isset($aOptions['max_points'])) $aFilter['range']['Points']['le'] = $aOptions['max_points'];
      $aSearchParams['query']['bool']['filter'][] = $aFilter;
    }

    // Optional members filter
    if (isset($aOptions['min_members']) || isset($aOptions['max_members'])) {
      $aFilter = array('range' => array('Members' => array()));
      if (isset($aOptions['min_members'])) $aFilter['range']['Members']['gte'] = $aOptions['min_members'];
      if (isset($aOptions['max_members'])) $aFilter['range']['Members']['le'] = $aOptions['max_members'];
      $aSearchParams['query']['bool']['filter'][] = $aFilter;
    }

    // Make sure empty filter has the right type
    if (sizeof($aSearchParams['query']['bool']['filter']) == 0) $aSearchParams['query']['bool']['filter'] = (object)[];

    // Execute
    $aElasticsearchResult = $oElasticsearchClient->search(array(
      'index' => self::IndexIdentifier,
      'type'  => self::TypeAlliance,
      'body'  => $aSearchParams
    ));

    // Format
    $aSearchOutput = array(
      'success' => true,
      'count'   => $aElasticsearchResult['hits']['total'],
      'results' => self::RenderAllianceResults($aElasticsearchResult),
      'form'    => self::RenderForm($aElasticsearchResult),
    );

    return $aSearchOutput;
  }

  public static function FindTowns($aOptions = array())
  {
    $oElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    if (!$oElasticsearchClient->indices()->exists(array('index' => self::IndexTowns))) return false;

    // Initial search setup
    $aSearchParams = array(
      'size'  => 30,
      'from'  => 0,
      'query' => array(
        'bool' => array(
          'must' => array(
            'match_all' => (object)[]
          ),
          'filter' => array(),
          'should' => array()
        ),
      ),
      'aggregations'  => array(
        'world'       => array('terms' => array('field' => 'WorldId', 'size' => 100)),
        'server'      => array('terms' => array('field' => 'Server', 'size' => 30))
      )
    );

    // Size filter
    if (isset($aOptions['from'])) $aSearchParams['from'] = $aOptions['from'];
    if (isset($aOptions['size']) && $aOptions['size'] <= 200) $aSearchParams['size'] = $aOptions['size'];

    // Optional query (use exact match)
    if (isset($aOptions['query'])) {
      // Boost exact match
      $aSearchParams['query']['bool']['must'] = array(
        'term' => array(
          'Name' => $aOptions['query']
        )
      );
    }

    // Sort filters
    if (isset($aOptions['sort_field'])) {
      if (in_array($aOptions['sort_field'], ['Points', 'Name'])) {
        $aSort = array(
          $aOptions['sort_field'] => array(
            'order' => ((isset($aOptions['sort_order'])) ? $aOptions['sort_order'] : 'desc')
          )
        );
        $aSearchParams['sort'] = array($aSort);
      }
    }

    // Optional player id filter
    if (isset($aOptions['player_id'])) {
      $aSearchParams['query']['bool']['filter'][] = array( 'term' => array( 'PlayerId' => $aOptions['player_id']) );
    }

    // Optional server filter
    if (isset($aOptions['server'])) {
      $aSearchParams['query']['bool']['filter'][] = array( 'term' => array( 'Server' => $aOptions['server']) );
    }

    // Optional world filter
    if (isset($aOptions['world'])) {
      $aSearchParams['query']['bool']['filter'][] = array( 'term' => array( 'WorldId' => $aOptions['world']) );
    } elseif (isset($aOptions['user_worlds'])) {
      $aWorldFilters = array('bool'=>array('should'=>array()));
      foreach ($aOptions['user_worlds'] as $World) {
        $aWorldFilters['bool']['should'][] = array(
          'term' => array( 'WorldId' => $World)
        );
      }
      $aSearchParams['query']['bool']['filter'][] = $aWorldFilters;
    }

    // Optional points filter
    if (isset($aOptions['min_points']) || isset($aOptions['max_points'])) {
      $aFilter = array('range' => array('Points' => array()));
      if (isset($aOptions['min_points'])) $aFilter['range']['Points']['gte'] = $aOptions['min_points'];
      if (isset($aOptions['max_points'])) $aFilter['range']['Points']['le'] = $aOptions['max_points'];
      $aSearchParams['query']['bool']['filter'][] = $aFilter;
    }

    // Make sure empty filter has the right type
    if (sizeof($aSearchParams['query']['bool']['filter']) == 0) $aSearchParams['query']['bool']['filter'] = (object)[];

    // Execute
    $aElasticsearchResult = $oElasticsearchClient->search(array(
      'index' => self::IndexTowns,
      'type'  => self::TypeTown,
      'body'  => $aSearchParams
    ));

    // Format
    $aSearchOutput = array(
      'success' => true,
      'count'   => $aElasticsearchResult['hits']['total'],
      'results' => self::RenderTownResults($aElasticsearchResult),
      'form'    => self::RenderTownForm($aElasticsearchResult),
    );

    return $aSearchOutput;
  }

  private static function SearchPlayersEs($aOptions) {
    $oElasticsearchClient = \Grepodata\Library\Elasticsearch\Client::GetInstance(3);
    if (!$oElasticsearchClient->indices()->exists(array('index' => self::IndexIdentifier))) return false;

    // Initial search setup
    $aSearchParams = array(
      'size'  => 30,
      'from'  => 0,
      'query' => array(
        'function_score' => array(
          'query' => array(
            'bool' => array(
              'must' => array(
                'match_all' => (object)[]
              ),
              'filter' => array(),
              'should' => array()
            ),
          ),
          'script_score' => array(
            'script' => array(
              'inline' => "_score"
            )
          )
        )
      ),
      'aggregations' => array(
        'world'      => array('terms' => array('field' => 'WorldId', 'size' => 250)),
        'server'     => array('terms' => array('field' => 'Server', 'size' => 30)),
        'max_points' => array('max'   => array('field' => 'Points')),
        'min_points' => array('min'   => array('field' => 'Points')),
        'max_towns'  => array('max'   => array('field' => 'Towns')),
        'min_towns'  => array('min'   => array('field' => 'Towns')),
        'max_rank'   => array('max'   => array('field' => 'Rank')),
        'min_rank'   => array('min'   => array('field' => 'Rank')),
      )
    );

    // Size filter
    if (isset($aOptions['from'])) $aSearchParams['from'] = $aOptions['from'];
    if (isset($aOptions['size']) && $aOptions['size'] <= 200) {
      $aSearchParams['size'] = $aOptions['size'];
    }

    // Optional query
    if (isset($aOptions['query']) && $aOptions['query'] != '' && is_string($aOptions['query'])) {
      // Fuzzy matching
      $aSearchParams['query']['function_score']['query']['bool']['must'] = array(
        'fuzzy' => array(
          'Name' => array(
            'value' => substr($aOptions['query'], 0, 20),
            'boost' => 1.0,
            'fuzziness' => 2,
            'prefix_length' => 4,
            'max_expansions' => 50,
          )
        )
      );

      // Boost exact match
      $boost = 10000;
      if (isset($aOptions['sql']) && $aOptions['sql']==true) {
        // Discord search has sql=true and requires a higher exact match boost
        $boost = 100000;
      }
      $aSearchParams['query']['function_score']['query']['bool']['should'][] = array(
        'match' => array(
          'Name' => array(
            'query' => $aOptions['query'],
            'boost' => $boost,
          )
        )
      );

      // Optional boost by preferred server
      if (isset($aOptions['preferred_server'])
        && $aOptions['preferred_server'] != ''
        && is_string($aOptions['preferred_server'])
        && strlen($aOptions['preferred_server']) == 2) {
        $boost = (strlen($aOptions['query']) > 4 ? 300.0 : 400.0);
//        if (isset($aOptions['sql']) && $aOptions['sql']==true) {
//          // Discord search has sql=true and requires a higher preferred server boost
//          $boost = 100000;
//        }
        $aSearchParams['query']['function_score']['query']['bool']['should'][] = array(
          'match' => array(
            'Server' => array(
              'query' => $aOptions['preferred_server'],
              'boost' => $boost,
            )
          )
        );
      }
    }

    // Sort filters
    if (isset($aOptions['sort_field'])) {
      if (in_array($aOptions['sort_field'], ['Points', 'Rank', 'Towns', 'Att', 'Def', 'Members', 'AttDef'])) {
        $SortField = $aOptions['sort_field'];
        $aSort = array(
          $SortField => array(
            'order' => ((isset($aOptions['sort_order'])) ? $aOptions['sort_order'] : 'desc')
          )
        );
        $aSearchParams['sort'] = array($aSort);
      }
    } else {
      // Higher player points = increased sort order
      if (isset($aOptions['sql']) && $aOptions['sql']==true) {
        // Discord search has sql=true, give points a bit more kick
        $aSearchParams['query']['function_score']['script_score']['script']['inline'] =
          "_score + (doc['Points'].value)";
      } else {
        $aSearchParams['query']['function_score']['script_score']['script']['inline'] =
          "_score + (doc['Points'].value / 10000)";
      }
    }

    // Search by id
    if (isset($aOptions['grep_id']) && is_numeric($aOptions['grep_id'])) {
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = array( 'term' => array( 'GrepId' => $aOptions['grep_id']) );
    }

    // Optional active filter
    if (isset($aOptions['active']) && $aOptions['active'] === 'true') {
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = array( 'term' => array( 'Active' => true) );
    }

    // Optional server filter
    if (isset($aOptions['server'])) {
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = array( 'term' => array( 'Server' => $aOptions['server']) );
    }

    // Optional world filter
    if (isset($aOptions['world'])) {
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = array( 'term' => array( 'WorldId' => $aOptions['world']) );
    } elseif (isset($aOptions['user_worlds'])) {
      $aWorldFilters = array('bool'=>array('should'=>array()));
      foreach ($aOptions['user_worlds'] as $World) {
        $aWorldFilters['bool']['should'][] = array(
          'term' => array( 'WorldId' => $World)
        );
      }
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = $aWorldFilters;
    } else {
      // Check active worlds only
      $aWorlds = Common::getAllActiveWorlds(false);
      if ($aWorlds !== false) {
        $aWorldFilters = array('bool'=>array('should'=>array()));
        /** @var World $oWorld */
        foreach ($aWorlds as $oWorld) {
          $aWorldFilters['bool']['should'][] = array(
            'term' => array( 'WorldId' => $oWorld->grep_id)
          );
        }
        $aSearchParams['query']['function_score']['query']['bool']['filter'][] = $aWorldFilters;
      }
    }

    // Optional rank filter
    if (isset($aOptions['min_rank']) || isset($aOptions['max_rank'])) {
      $aFilter = array('range' => array('Rank' => array()));
      if (isset($aOptions['min_rank'])) $aFilter['range']['Rank']['gte'] = $aOptions['min_rank'];
      if (isset($aOptions['max_rank'])) $aFilter['range']['Rank']['le']  = $aOptions['max_rank'];
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = $aFilter;
    }

    // Optional cities filter
    if (isset($aOptions['min_towns']) || isset($aOptions['max_towns'])) {
      $aFilter = array('range' => array('Towns' => array()));
      if (isset($aOptions['min_towns'])) $aFilter['range']['Towns']['gte'] = $aOptions['min_towns'];
      if (isset($aOptions['max_towns'])) $aFilter['range']['Towns']['le']  = $aOptions['max_towns'];
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = $aFilter;
    }

    // Optional points filter
    if (isset($aOptions['min_points']) || isset($aOptions['max_points'])) {
      $aFilter = array('range' => array('Points' => array()));
      if (isset($aOptions['min_points'])) $aFilter['range']['Points']['gte'] = $aOptions['min_points'];
      if (isset($aOptions['max_points'])) $aFilter['range']['Points']['le'] = $aOptions['max_points'];
      $aSearchParams['query']['function_score']['query']['bool']['filter'][] = $aFilter;
    }

    // Make sure empty filter has the right type
    if (sizeof($aSearchParams['query']['function_score']['query']['bool']['filter']) == 0) $aSearchParams['query']['function_score']['query']['bool']['filter'] = (object)[];

    // Execute
    $aElasticsearchResult = $oElasticsearchClient->search(array(
      'index' => self::IndexIdentifier,
      'type'  => self::TypePlayer,
      'body'  => $aSearchParams
    ));

    return $aElasticsearchResult;
  }

  private static function RenderForm($aElasticsearchResults) {
    $aForm = array(
      'servers' => $aElasticsearchResults['aggregations']['server']['buckets'],
      'worlds'  => $aElasticsearchResults['aggregations']['world']['buckets'],
      'cities'  => array(
        'min'   => $aElasticsearchResults['aggregations']['min_towns']['value'],
        'max'   => $aElasticsearchResults['aggregations']['max_towns']['value'],
      ),
      'rank'  => array(
        'min'   => $aElasticsearchResults['aggregations']['min_rank']['value'],
        'max'   => $aElasticsearchResults['aggregations']['max_rank']['value'],
      ),
      'points'  => array(
        'min'   => $aElasticsearchResults['aggregations']['min_points']['value'],
        'max'   => $aElasticsearchResults['aggregations']['max_points']['value'],
      ),
    );

    usort($aForm['worlds'], function ($a, $b){
      if ($a['key'] == $b['key']) {
        return 0;
      }
      return ($a['key'] < $b['key']) ? -1 : 1;
    });

    if (isset($aElasticsearchResults['aggregations']['min_members']['value']) && isset($aElasticsearchResults['aggregations']['max_members']['value'])) {
      $aForm['members'] = array(
        'min'   => $aElasticsearchResults['aggregations']['min_members']['value'],
        'max'   => $aElasticsearchResults['aggregations']['max_members']['value'],
      );
    }

    return $aForm;
  }

  private static function RenderTownForm($aElasticsearchResults) {
    $aForm = array(
      'servers' => $aElasticsearchResults['aggregations']['server']['buckets'],
      'worlds'  => $aElasticsearchResults['aggregations']['world']['buckets']
    );

    usort($aForm['worlds'], function ($a, $b){
      if ($a['key'] == $b['key']) {
        return 0;
      }
      return ($a['key'] < $b['key']) ? 1 : -1;
    });

    return $aForm;
  }

  private static function RenderPlayerResults($aElasticsearchResults) {
    $aResults = array();
    foreach ($aElasticsearchResults['hits']['hits'] as $aHit) {
      $aResults[] = array(
        'score'            => $aHit['_score'],
        'id'            => $aHit['_source']['GrepId'],
        'world'         => $aHit['_source']['WorldId'],
        'server'        => $aHit['_source']['Server'],
        'name'          => $aHit['_source']['Name'],
        'alliance_id'   => $aHit['_source']['AllianceId'],
        'alliance_name' => $aHit['_source']['AllianceName'],
        'rank'          => $aHit['_source']['Rank'],
        'points'        => $aHit['_source']['Points'],
        'towns'         => $aHit['_source']['Towns'],
        'att'           => $aHit['_source']['Att'],
        'def'           => $aHit['_source']['Def'],
        'active'        => $aHit['_source']['Active'],
      );
    }
    return $aResults;
  }

  private static function RenderAllianceResults($aElasticsearchResults) {
    $aResults = array();
    foreach ($aElasticsearchResults['hits']['hits'] as $aHit) {
      $aResults[] = array(
        'id'            => $aHit['_source']['GrepId'],
        'world'         => $aHit['_source']['WorldId'],
        'server'        => ($aHit['_source']['Server']!=''?$aHit['_source']['Server']:substr($aHit['_source']['WorldId'], 0, 2)),
        'name'          => $aHit['_source']['Name'],
        'rank'          => $aHit['_source']['Rank'],
        'points'        => $aHit['_source']['Points'],
        'members'       => $aHit['_source']['Members'],
        'towns'         => $aHit['_source']['Towns'],
        'att'           => $aHit['_source']['Att'],
        'def'           => $aHit['_source']['Def'],
        'active'        => $aHit['_source']['Active'],
      );
    }
    return $aResults;
  }

  private static function RenderTownResults($aElasticsearchResults) {
    $aResults = array();
    foreach ($aElasticsearchResults['hits']['hits'] as $aHit) {
      $aResults[] = array(
        'id'            => $aHit['_source']['GrepId'],
        'world'         => $aHit['_source']['WorldId'],
        'server'        => ($aHit['_source']['Server']!=''?$aHit['_source']['Server']:substr($aHit['_source']['WorldId'], 0, 2)),
        'player_id'     => $aHit['_source']['PlayerId'],
        'player_name'   => $aHit['_source']['PlayerName'],
        'alliance_id'   => $aHit['_source']['AllianceId'],
        'alliance_name' => $aHit['_source']['AllianceName'],
        'island_x'      => $aHit['_source']['IslandX'],
        'island_y'      => $aHit['_source']['IslandY'],
        'island_i'      => $aHit['_source']['IslandI'],
        'points'        => $aHit['_source']['Points'],
        'updated_at'    => $aHit['_source']['UpdatedAt'],
        'name'          => $aHit['_source']['Name'],
      );
    }
    return $aResults;
  }

}

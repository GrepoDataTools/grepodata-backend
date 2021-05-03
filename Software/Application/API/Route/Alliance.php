<?php

namespace Grepodata\Application\API\Route;

use Carbon\Carbon;
use Exception;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Elasticsearch\Search;
use Grepodata\Library\Redis\RedisClient;
use Grepodata\Library\Router\ResponseCode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Capsule\Manager as DB;

class Alliance extends \Grepodata\Library\Router\BaseRoute
{
  public static function MailListGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('alliance_ids', 'world'));

      $aMaillist = array();
      foreach (mb_split(',', $aParams['alliance_ids']) as $AllianceId) {
        $aPlayers = \Grepodata\Library\Controller\Alliance::getAllianceMembers($AllianceId, $aParams['world']);
        foreach ($aPlayers as $oPlayer) {
          $aMaillist[] = $oPlayer->name;
        }
      }

      $aResponse = array(
        'mail_list' => join('; ', $aMaillist) . '; '
      );
      ResponseCode::success($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function WarsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('id', 'world'));
      $RedisKey = RedisClient::ALLIANCE_WARS_PREFIX.$aParams['id'].[$aParams['world']];

      // check if cached response is available
      if (is_numeric($aParams['id'])) {
        $CachedResponse = RedisClient::GetKey($RedisKey);
        if ($CachedResponse!=false) {
          $aResponse = json_decode($CachedResponse, true);
          $aResponse['cached_response'] = true;
          ResponseCode::success($aResponse);
        }
      }

      $aTownsLost = \Grepodata\Library\Model\Conquest::select('n_a_id', DB::raw('count(*) as towns_lost'))
        ->where('o_a_id', '=', $aParams['id'])
        ->where('world', '=', $aParams['world'])
        ->groupBy('n_a_id')
        ->get();
      $aTownsWon = \Grepodata\Library\Model\Conquest::select('o_a_id', DB::raw('count(*) as towns_won'))
        ->where('n_a_id', '=', $aParams['id'])
        ->where('world', '=', $aParams['world'])
        ->groupBy('o_a_id')
        ->get();

      $aMergedWars = array();
      foreach ($aTownsLost as $aScore) {
        if ($aScore['n_a_id'] == 0 || $aScore['n_a_id'] == $aParams['id']) {
          continue;
        }
        $aMergedWars[$aScore['n_a_id']] = array(
          'alliance_id' => $aScore['n_a_id'],
          'towns_gained_from' => 0,
          'towns_lost_to' => $aScore['towns_lost']
        );
      }
      foreach ($aTownsWon as $aScore) {
        if ($aScore['o_a_id'] == 0 || $aScore['o_a_id'] == $aParams['id']) {
          continue;
        }
        if (key_exists($aScore['o_a_id'], $aMergedWars)) {
          $aMergedWars[$aScore['o_a_id']]['towns_gained_from'] = $aScore['towns_won'];
        } else {
          $aMergedWars[$aScore['o_a_id']] = array(
            'alliance_id' => $aScore['o_a_id'],
            'towns_gained_from' => $aScore['towns_won'],
            'towns_lost_to' => 0
          );
        }
      }

      usort($aMergedWars, function ($a, $b) {
        return $a['towns_gained_from']+$a['towns_lost_to'] > $b['towns_gained_from']+$b['towns_lost_to'] ? -1 : 1;
      });

      $aMergedWars = array_filter($aMergedWars, function ($a) {
        return $a['towns_gained_from']+$a['towns_lost_to'] > 5;
      });

      // expand names
      $aExpandedWars = array();
      foreach ($aMergedWars as $aWar) {
        try {
          $oAlliance = \Grepodata\Library\Controller\Alliance::firstOrFail($aWar['alliance_id'], $aParams['world']);
          $aWar['alliance_name'] = $oAlliance->name;
          $aExpandedWars[] = $aWar;
        } catch (Exception $e) {}
      }

      $aResponse = array(
        'size' => count($aExpandedWars),
        'data' => $aExpandedWars
      );

      if (is_numeric($aParams['id'])) {
        // Cache response for 1 hour
        RedisClient::SetKey($RedisKey, json_encode($aResponse), 3600);
      }

      ResponseCode::success($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceInfoGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      // Return model
      $oAlliance = \Grepodata\Library\Controller\Alliance::firstOrFail($aParams['id'], $aParams['world']);
      return self::OutputJson($oAlliance->getPublicFields());
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceChangesGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      $From = 0;
      $Size = 10;
      if (isset($aParams['size']) && $aParams['size'] < 50) {
        $Size = $aParams['size'];
      }
      if (isset($aParams['from']) && $aParams['from'] < 5000) {
        $From = $aParams['from'];
      }

      // Find model
      $aAllianceChanges = \Grepodata\Library\Controller\AllianceChanges::getChangesByAllianceId($aParams['id'], $aParams['world'], $From, $Size);

      $Id = $aParams['id'];
      $aResponse = array(
        'count' => \Grepodata\Library\Model\AllianceChanges::where(function ($query) use ($Id) {
          $query->where('new_alliance_grep_id', '=', $Id)
            ->orWhere('old_alliance_grep_id', '=', $Id);
        })
          ->where('world', '=', $aParams['world'])->count(),
        'items' => array()
      );

      foreach ($aAllianceChanges as $oAllianceChange) {
        $aChange = $oAllianceChange->getPublicFields();
        $aChange['date'] = array(
          'date' => $aChange['date']->format('Y-m-d H:i:s')
        );
        $aResponse['items'][] = $aChange;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No changes found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceHistoryGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      // Find model
      $aAllianceHistories = \Grepodata\Library\Controller\AllianceHistory::getAllianceHistory($aParams['id'], $aParams['world'], 0);
      $aResponse = array();
      foreach ($aAllianceHistories as $oAllianceHistory) {
        $aResponse[] = $oAllianceHistory->getPublicFields();
      }
      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance history found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceHistoryRangeGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id','from','to'));

      if ($aParams['from'] > 180) $aParams['from'] = 0;
      if ($aParams['to'] > 180) $aParams['to'] = 180;

      // Find model
      $aAllianceHistories = \Grepodata\Library\Controller\AllianceHistory::getAllianceHistory($aParams['id'], $aParams['world'], $aParams['to']+1);
      $aResponse = array();
      $count = 0;
      $last_date = '';
      foreach ($aAllianceHistories as $oAllianceHistory) {
        $count++;
        if ($count > $aParams['from'] && $count < $aParams['to']) {
          $aAlliance = $oAllianceHistory->getPublicFields();
          $aResponse[] = $aAlliance;
          $last_date = $aAlliance['date'];
        }
      }

      // Add filler records for full range
      try {
        $LastDate = Carbon::createFromFormat("Y-m-d", $last_date);
        while ($count < $aParams['to']) {
          $count++;
          $LastDate->subDay();
          $aResponse[] = array(
            'date' => $LastDate->toDateString(),
            'points' => 0,
            'rank' => 0,
            'att' => 0,
            'def' => 0,
            'towns' => 0,
            'members' => 0
          );
        }
      } catch (Exception $e) {}

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance history found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceMemberHistoryGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world','id'));

      // Find model
      $aAllianceMembers = \Grepodata\Library\Controller\Alliance::getAllianceMembers($aParams['id'], $aParams['world']);
      $aResponse = array(
        'dates'   => array(),
        'members' => array(),
      );
      $CreatedLast = null;
      foreach ($aAllianceMembers as $oPlayer) {
        $aPlayerHistoryShort = array(
          'id'      => $oPlayer->grep_id,
          'name'    => $oPlayer->name,
          'rank'    => $oPlayer->rank,
          'points'  => $oPlayer->points,
          'att'     => $oPlayer->att,
          'def'     => $oPlayer->def,
          'att_rank' => $oPlayer->att_rank,
          'def_rank' => $oPlayer->def_rank,
          'towns'   => $oPlayer->towns,
          'heatmap' => array(), // deprecated
          'hours_inactive' => $oPlayer->getHoursInactive(),
          'history' => array()
        );

        $aPlayerHistories = \Grepodata\Library\Controller\PlayerHistory::getPlayerHistory($oPlayer->grep_id, $aParams['world'], 10);

        // Calculate diffs
        $aDates = array();
        for ($i = 0; $i < sizeof($aPlayerHistories)-1; $i++) {
          $Current = $aPlayerHistories[$i];

          if ($i == 0) {
            $LatestHistoryDate = $Current->created_at;

            // Add latest diff
            $dt = Carbon::createFromTimestampUTC(strtotime($Current->date));
            $dt->addDay();
            $date = $dt->format('Y-m-d');
            $aDates[] = $date;
            $aPlayerHistoryShort['history'][] = array(
              'date'    => $date,
              'points'  => $oPlayer->points - $Current->points,
              'att'     => $oPlayer->att - $Current->att,
              'def'     => $oPlayer->def - $Current->def,
              'towns'   => $oPlayer->towns - $Current->towns,
            );
          }

          $Yesterday = $aPlayerHistories[$i+1];
          $aDates[] = $Current->date;
          $aPlayerHistoryShort['history'][] = array(
            'date'    => $Current->date,
            'points'  => $Current->points - $Yesterday->points,
            'att'     => $Current->att - $Yesterday->att,
            'def'     => $Current->def - $Yesterday->def,
            'towns'   => $Current->towns - $Yesterday->towns,
          );
        }

        // Save most recent set of dates
        if (sizeof($aDates) >= sizeof($aResponse['dates']) && (is_null($CreatedLast) || $LatestHistoryDate > $CreatedLast)) {
          $CreatedLast = $LatestHistoryDate;
          $aResponse['dates'] = array_reverse($aDates);
        }
        $aResponse['members'][] = $aPlayerHistoryShort;
      }

      // Fix history records to match best list of dates (largest and most recent)
      foreach ($aResponse['members'] as $MKey => $Member) {
        $aFixedHistory = array();
        foreach (array_reverse($aResponse['dates']) as $Date) {
          $aFixedHistory[$Date] = array(
            'date'    => $Date,
            'points'  => '',
            'att'     => '',
            'def'     => '',
            'towns'   => ''
          );
        }

        foreach ($Member['history'] as $HistoryRecord) {
          if (in_array($HistoryRecord['date'], $aResponse['dates'])) {
            $aFixedHistory[$HistoryRecord['date']] = $HistoryRecord;
          }
        }

        $aResponse['members'][$MKey]['history'] = array_values($aFixedHistory);
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance history found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function SearchGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams();

      if (isset($aParams['index'])) {
        $oIndex = IndexInfo::firstOrFail($aParams['index']);
        $aParams['world'] = $oIndex->world;
      }

      if (isset($aParams['query']) && strlen($aParams['query']) > 30) {
        throw new Exception("Search input exceeds limit: " . substr($aParams['query'], 0, 200));
      }

      try {
        $aElasticsearchResults = Search::FindAlliances($aParams);
      } catch (Exception $e) {}

      if (isset($aElasticsearchResults) && $aElasticsearchResults != false) {
        $aResponse = $aElasticsearchResults;
      } else {
        // SQL fallback: Find model
        $aAlliances = \Grepodata\Library\Controller\Alliance::search($aParams);
        if ($aAlliances == null || sizeof($aAlliances) <= 0) throw new ModelNotFoundException();

        // Format sql results
        $aResponse = array(
          'success' => true,
          'count'   => sizeof($aAlliances),
          'status'  => 'sql_fallback',
          'results' => array(),
        );
        foreach ($aAlliances as $oAlliance) {
          $aData = $oAlliance->getPublicFields();
          $aData['id'] = $aData['grep_id']; unset($aData['grep_id']);
          $aData['server'] = substr($aData['world'], 0, 2);
          $aResponse['results'][] = $aData;
        }
      }

      return self::OutputJson($aResponse);

    } catch (Exception $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliances found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }
}

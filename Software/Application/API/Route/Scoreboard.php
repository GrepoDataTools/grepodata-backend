<?php

namespace Grepodata\Application\API\Route;

use Carbon\Carbon;
use Grepodata\Library\Controller\AllianceScoreboard;
use Grepodata\Library\Controller\PlayerScoreboard;
use Grepodata\Library\Controller\TownGhost;
use Grepodata\Library\Elasticsearch\Diff;
use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Scoreboard extends \Grepodata\Library\Router\BaseRoute
{
  public static function PlayerScoreboardGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array());

      // Check discord args
      $bMinimal = false;
      if (isset($aParams['minimal']) && $aParams['minimal'] === 'true') {
        $bMinimal = true;
      }
      if (isset($aParams['guild']) && $aParams['guild'] !== '') {
        $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);
        if ($oDiscord->server !== null) {
          if (isset($aParams['world']) && $aParams['world'] != '') {
            // Try to find world using supplied world input (can be text string or actual world id)
            try {
              // try as world id
              $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
            } catch (\Exception $e) {
              // try as world name
              try {
                $oWorld = \Grepodata\Library\Model\World::where('name', 'LIKE', '%'.$aParams['world'].'%', 'and')
                  ->where('grep_id', 'LIKE', substr($oDiscord->server, 0, 2).'%')
                  ->firstOrFail();
                if ($oWorld != false && $oWorld != null) {
                  $aParams['world'] = $oWorld->grep_id;
                } else {
                  $aParams['world'] = $oDiscord->server;
                }
              } catch (\Exception $e) {
                Logger::warning("Cannot find scoreboard for world param: " . $aParams['world']);
                $aParams['world'] = $oDiscord->server;
              }
            }
          } else {
            // use default server world
            $aParams['world'] = $oDiscord->server;
          }
        }
      }

      // Optional world
      $bUsingLatestDefault = false;
      if (!isset($aParams['world']) || $aParams['world'] == '') {
        $aParams['world'] = DEFAULT_WORLD;
        try {
          // optional server
          $Server = DEFAULT_SERVER;
          if (isset($aParams['server']) && $aParams['server'] != '') {
            $Server = $aParams['server'];
          } else {
            $bUsingLatestDefault = true;
          }

          $oLatestWorld = \Grepodata\Library\Controller\World::getLatestByServer($Server);
          $aParams['world'] = $oLatestWorld->grep_id;
        } catch (\Exception $e) {
          $aParams['world'] = DEFAULT_WORLD;
        }
      }

      // Optional using date
      if (isset($aParams['yesterday']) && $aParams['yesterday'] === 'true') {
        $aScoreboard = PlayerScoreboard::yesterdayByWorld($aParams['world']);
        if ($aScoreboard===null) {
          throw new ModelNotFoundException();
        }
      } elseif (isset($aParams['date']) && $aParams['date'] != '') {
        $aScoreboard = PlayerScoreboard::first($aParams['date'], $aParams['world']);
      }

      // Default
      $bFallback = false;
      if (!isset($aScoreboard) || is_null($aScoreboard) || !isset($aScoreboard['date'])) {
        if (isset($aParams['date']) && $aParams['date'] != '') $bFallback = true; // Detect fallback usage
        try {
          $aScoreboard = PlayerScoreboard::latestByWorldOrFail($aParams['world']);
        } catch (\Exception $e) {
          if ($bUsingLatestDefault) {
            // try again with real default
            $aScoreboard = PlayerScoreboard::latestByWorldOrFail(DEFAULT_WORLD);
          }
        }
      }

      // Find min date
      $MinDate = '';
      if ($bMinimal === false) {
        $aMinDate = PlayerScoreboard::getMinDate($aParams['world']);
        if (isset($aMinDate['date'])) $MinDate = $aMinDate['date'];
      }

      // Check allow cache
      $AllowCache = false;
      $Diff = '0';
      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      if (\Grepodata\Library\Controller\World::getScoreboardDate($oWorld) != $aScoreboard['date']) $AllowCache = true;
      else {
        $Diff = \Grepodata\Library\Controller\World::getNextUpdateDiff($oWorld);
        $Diff = str_replace(' before','', $Diff);
      }

      // Add overview
      $aOverview = [];
      if (!is_null($aScoreboard['overview']) && $bMinimal === false) {
        $aOverviewData = json_decode(urldecode($aScoreboard['overview']), true);
        if (sizeof($aOverviewData) > 5) {
          foreach ($aOverviewData as $aHour) {
            if (!is_null($aHour['hour'])) {
              if ($aHour['hour'] == 0) {
                $aHour['hour'] = 24;
              }
              $aOverview[] = array(
                "name" => (strlen($aHour['hour']) == 1 ? '0':'') . $aHour['hour'] . ':00',
                "series" => array(
                  array("name" => 'Attacking', "value" => $aHour['att']),
                  array("name" => 'Defending', "value" => $aHour['def'])
                )
              );
            }
          }
        }
      }

      // Format output
      $aResponse = array(
        'world'       => $aScoreboard['world'],
        'minDate'     => $MinDate,
        'allowCache'  => $AllowCache,
        'fallback'    => $bFallback,
        'date'        => $aScoreboard['date'],
        'time'        => $aScoreboard['server_time'],
        'nextUpdate'  => $Diff,
        'overview'    => $aOverview,
        'att'         => json_decode(urldecode($aScoreboard['att'])),
        'def'         => json_decode(urldecode($aScoreboard['def'])),
        'con'         => $bMinimal?[]:json_decode(urldecode($aScoreboard['con'])),
        'los'         => $bMinimal?[]:json_decode(urldecode($aScoreboard['los'])),
        'ghosts'      => $bMinimal||is_null($aScoreboard['ghosts'])?[]:json_decode(urldecode($aScoreboard['ghosts'])),
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player scoreboard found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function PlayerDiffsByHourGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'date', 'hour'));

      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      $oDate = Carbon::createFromFormat("Y-m-d H:i", $aParams['date'] . " 00:00");

      $aResponse = Diff::GetDiffsByHour($oWorld, $oDate, $aParams['hour'], 500, 20);

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player diffs found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function PlayerDiffsByDayGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'date', 'id'));

      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      $oPlayer = \Grepodata\Library\Controller\Player::firstOrFail($aParams['id'], $aParams['world']);
      $oDate = Carbon::createFromFormat("Y-m-d H:i", $aParams['date'] . " 00:00");

      $aResponse = Diff::GetPlayerDiffsByDay($oWorld, $oDate, $oPlayer);

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player diffs found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceDiffsByDayGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world', 'date', 'id'));

      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      $oAlliance = \Grepodata\Library\Controller\Alliance::firstOrFail($aParams['id'], $aParams['world']);
      $oDate = Carbon::createFromFormat("Y-m-d H:i", $aParams['date'] . " 00:00");

      $aResponse = Diff::GetAllianceDiffsByDay($oWorld, $oDate, $oAlliance);

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player diffs found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function PlayerDiffsGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams(array('world'));

      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      $aResponse = Diff::GetMostRecentDiffs($oWorld, 10);

      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player diffs found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function AllianceScoreboardGET()
  {
    $aParams = array();

    try {
      // Validate params
      $aParams = self::validateParams(array());

      // Check discord args
      $bMinimal = false;
      if (isset($aParams['minimal']) && $aParams['minimal'] === 'true') {
        $bMinimal = true;
      }
      if (isset($aParams['guild']) && $aParams['guild'] !== '') {
        $oDiscord = \Grepodata\Library\Controller\Discord::firstOrNew($aParams['guild']);
        if ($oDiscord->server !== null) {
          $aParams['world'] = $oDiscord->server;
        }
      }

      // Optional world
      if (!isset($aParams['world']) || $aParams['world'] == '') {
        $aParams['world'] = DEFAULT_WORLD;
        try {
          // optional server
          $Server = DEFAULT_SERVER;
          if (isset($aParams['server']) && $aParams['server'] != '') {
            $Server = $aParams['server'];
          }

          $oLatestWorld = \Grepodata\Library\Controller\World::getLatestByServer($Server);
          $aParams['world'] = $oLatestWorld->grep_id;
        } catch (\Exception $e) {
          $aParams['world'] = DEFAULT_WORLD;
        }
      }

      // Optional using date
      if (isset($aParams['yesterday']) && $aParams['yesterday'] === 'true') {
        $aScoreboard = AllianceScoreboard::yesterdayByWorld($aParams['world']);
        if ($aScoreboard===null) {
          throw new ModelNotFoundException();
        }
      } elseif (isset($aParams['date']) && $aParams['date'] != '') {
        $aScoreboard = AllianceScoreboard::first($aParams['date'], $aParams['world']);
      }

      // Default
      $bFallback = false;
      if (!isset($aScoreboard) || is_null($aScoreboard) || !isset($aScoreboard['date'])) {
        if (isset($aParams['date']) && $aParams['date'] != '') $bFallback = true; // Detect fallback usage
        $aScoreboard = AllianceScoreboard::latestByWorldOrFail($aParams['world']);
      }

      // Find min date
      $MinDate = '';
      if ($bMinimal === false) {
        $aMinDate = AllianceScoreboard::getMinDate($aParams['world']);
        if (isset($aMinDate['date'])) $MinDate = $aMinDate['date'];
      }

      // Check allow cache
      $AllowCache = false;
      $Diff = '0';
      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);
      if (\Grepodata\Library\Controller\World::getScoreboardDate($oWorld) != $aScoreboard['date']) $AllowCache = true;
      else {
        $Diff = \Grepodata\Library\Controller\World::getNextUpdateDiff($oWorld);
        $Diff = str_replace(' before','', $Diff);
      }

      // Format output
      $aResponse = array(
        'world'       => $aScoreboard['world'],
        'minDate'     => $MinDate,
        'allowCache'  => $AllowCache,
        'fallback'    => $bFallback, // If true, show 'scoreboard for date X not found' but was redirect to latest scoreboard instead
        'date'        => $aScoreboard['date'],
        'time'        => $aScoreboard['server_time'],
        'nextUpdate'  => $Diff,
        'att'         => json_decode(urldecode($aScoreboard['att'])),
        'def'         => json_decode(urldecode($aScoreboard['def'])),
        'con'         => $bMinimal?[]:json_decode(urldecode($aScoreboard['con'])),
        'los'         => $bMinimal?[]:json_decode(urldecode($aScoreboard['los']))
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No alliance scoreboard found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  public static function GhostsTodayGET()
  {
    $aParams = array();

    try {
      // Validate params
      $aParams = self::validateParams(array());

      // Optional world
      if (!isset($aParams['world']) || $aParams['world'] == '') {
        $aParams['world'] = DEFAULT_WORLD;
        try {
          // optional server
          $Server = DEFAULT_SERVER;
          if (isset($aParams['server']) && $aParams['server'] != '') {
            $Server = $aParams['server'];
          }

          $oLatestWorld = \Grepodata\Library\Controller\World::getLatestByServer($Server);
          $aParams['world'] = $oLatestWorld->grep_id;
        } catch (\Exception $e) {
          $aParams['world'] = DEFAULT_WORLD;
        }
      }

      $oWorld = \Grepodata\Library\Controller\World::getWorldById($aParams['world']);

      $MaxDate = Carbon::now($oWorld->php_timezone);
      if (isset($aParams['date'])) {
        $MaxDate = Carbon::parse($aParams['date'], $oWorld->php_timezone)->addHours(24);
      }

      // Get player resets
      $aGhosts = array();
      try {
        $aGhosts = TownGhost::allRecentByWorld($aParams['world'], $MaxDate);
        $aGhostsFiltered = array();
        foreach ($aGhosts as $oGhost) {
          if ($oGhost['num_towns'] > 3) {
            $oGhost['ghost_time'] = Carbon::parse($oGhost['ghost_time'], $oWorld->php_timezone);
            $aGhostsFiltered[] = $oGhost;
          }
        }
      } catch (\Exception $e) {
        Logger::warning("Error loading player resets: ".$e->getMessage());
      }

      // Format output
      $aResponse = array(
        'count' => count($aGhostsFiltered),
        'items' => $aGhostsFiltered
      );
      return self::OutputJson($aResponse);
    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No player resets found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }
}

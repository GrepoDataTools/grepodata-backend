<?php

namespace Grepodata\Application\API\Route;

use Grepodata\Library\Logger\Logger;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class World extends \Grepodata\Library\Router\BaseRoute
{
  public static function ActiveWorldsGET()
  {
    $aServers = \Grepodata\Library\Controller\World::getServers();
    $aWorlds = \Grepodata\Library\Controller\World::getAllActiveWorlds();

    $aResponse = array();
    foreach ($aServers as $Server) {
      $aServer = array(
        'server'  => $Server
      );
      foreach ($aWorlds as $oWorld) {
        if (strpos($oWorld->grep_id, $Server) !== false) {
          $aServer['timezone'] = $oWorld->php_timezone;
          $aServer['worlds'][] = array(
            'id'    => $oWorld->grep_id,
            'val'   => substr($oWorld->grep_id, 2),
            'name'  => $oWorld->name,
//            'reset' => $oWorld->last_reset_time,
//            'time'  => $oWorld->grep_server_time,
          );
        }
      }
      $aServer['worlds'] = self::SortWorlds($aServer['worlds']);
      $aResponse[] = $aServer;
    }

    return self::OutputJson($aResponse);
  }

  public static function AllianceChangesGET()
  {
    $aParams = array();
    try {
      // Validate params
      $aParams = self::validateParams();

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

      $From = 0;
      $Size = 22;
      if (isset($aParams['size']) && $aParams['size'] < 50) {
        $Size = $aParams['size'];
      }
      if (isset($aParams['from']) && $aParams['from'] < 5000) {
        $From = $aParams['from'];
      }

      // Find model
      if (isset($aParams['date'])) {
        $aAllianceChanges = \Grepodata\Library\Controller\AllianceChanges::getBiggestChangesByDate($aParams['world'], $aParams['date'], $From, $Size);
      } else {
        $aAllianceChanges = \Grepodata\Library\Controller\AllianceChanges::getBiggestChangesByWorld($aParams['world'], $From, $Size);
      }
      $aResponse = array(
        'count' => sizeof($aAllianceChanges),
        'items' => array()
      );
      foreach ($aAllianceChanges as $oAllianceChange) {
        $aFields = $oAllianceChange->getPublicFields();
        $aFields['date'] = array(
          'date' => $aFields['date']->format('Y-m-d H:i:s')
        );
        $aFields['world'] = $aParams['world'];
        $aResponse['items'][] = $aFields;
      }

      return self::OutputJson($aResponse);

    } catch (ModelNotFoundException $e) {
      die(self::OutputJson(array(
        'message'     => 'No changes found for these parameters.',
        'parameters'  => $aParams
      ), 404));
    }
  }

  private static function SortWorlds($aWorlds)
  {
    usort($aWorlds, function ($item1, $item2) {
      if ($item1['val'] == $item2['val']) return 0;
      return $item1['val'] < $item2['val'] ? 1 : -1;
    });
    return $aWorlds;
  }
}
<?php

namespace Grepodata\Library\Cron;

use DOMDocument;
use Grepodata\Library\Logger\Logger;

class WorldData
{
  const endpoint_prefix     = WORLD_DATA_PREFIX;
  const stats_endpoint      = WORLD_DATA_STATS;

  /**
   * Load player data for given world
   *
   * @param $Server String Grepolis server abbreviation
   * @return array List of worlds for this server
   */
  public static function loadServerWorlds($Server)
  {
    $url = self::endpoint_prefix . $Server . self::stats_endpoint;
    $htmlContent = file_get_contents($url);
    $DOM = new DOMDocument();
    libxml_use_internal_errors(true);
    $DOM->loadHTML($htmlContent);
    $xpath = new \DOMXPath($DOM);

    $TableRows = $xpath->query("//table[@class='table']/tr");
    $Count = 0;
    $aRows = array();
    foreach ($TableRows as $aRow) {
      if ($Count < 2) {
        $Count++;
        continue;
      }
      $aRows[] = $aRow;
    }
    $aRows = array_reverse($aRows);

    $aRowColumns = array();
    foreach ($aRows as $aRow) {
      $aRowColumns[] = $xpath->query("descendant::td", $aRow);
    }
    return $aRowColumns;
  }

}
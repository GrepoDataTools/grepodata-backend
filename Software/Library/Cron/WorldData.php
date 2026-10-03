<?php

namespace Grepodata\Library\Cron;

use DOMDocument;
use Grepodata\Library\Logger\Logger;

class WorldData
{
  const endpoint_prefix     = WORLD_DATA_URL;

  /**
   * Load all world names
   * @return array List of worlds
   */
  public static function loadWorldNames()
  {
    $url = self::endpoint_prefix;
    $aContext = stream_context_create(array(
      'http' => array(
        'header' => "User-Agent: Mozilla/5.0\r\n",
        'timeout' => 15,
      ),
    ));
    $htmlContent = file_get_contents($url, false, $aContext);
    if ($htmlContent === false) {
      Logger::warning('WorldData::loadWorldNames - failed to fetch ' . $url);
      return array();
    }
    $DOM = new DOMDocument();
    libxml_use_internal_errors(true);
    $DOM->loadHTML($htmlContent);
    $xpath = new \DOMXPath($DOM);

    $ServersList = $xpath->query('//*[@id="servers-list"]/div[@class="servers"]');
    $aServers = array();
    foreach ($ServersList as $ServerElement) {
      $ServerId = $ServerElement->getAttribute('id');
      $ServerName = substr($ServerId, strpos($ServerId, 'servers-')+8);
      $Worlds = $xpath->query("descendant::a", $ServerElement);
      $aWorlds = array();
      foreach ($Worlds as $World) {
        $WorldText = $World->textContent;
        $SepIndex = strpos($WorldText, ': ');
        $WorldId = substr($WorldText, 0, $SepIndex);
        $WorldName = substr($WorldText, $SepIndex+2);
        $aWorlds[$WorldId] = $WorldName;
      }
      $aServers[$ServerName] = $aWorlds;
    }

    return $aServers;
  }

}
<?php

namespace Grepodata\Library\Cron;

use Grepodata\Library\Logger\Logger;
use Illuminate\Support\Facades\Log;

class InnoData
{
  const endpoint_prefix     = INNO_DATA_PREFIX;
  const player_endpoint     = INNO_DATA_PLAYER;
  const alliance_endpoint   = INNO_DATA_ALLIANCE;
  const town_endpoint       = INNO_DATA_TOWN;
  const island_endpoint     = INNO_DATA_ISLAND;
  const conquers_endpoint   = INNO_DATA_CONQUERS;
  const player_att_endpoint = INNO_DATA_PLAYER_ATT;
  const player_def_endpoint = INNO_DATA_PLAYER_DEF;
  const player_kills_endpoint = INNO_DATA_PLAYER_ALL;
  const alliance_att_endpoint = INNO_DATA_ALLIANCE_ATT;
  const alliance_def_endpoint = INNO_DATA_ALLIANCE_DEF;
  const max_retries = 5;

  /**
   * Load player data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadPlayerData($World, $HeaderOnly = false)
  {
    $Endpoint = self::player_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 6) {
        $InvalidRows++;
        continue;
      }

      $aData[$parts[0]] = array(
        'grep_id'     => $parts[0],
        'name'        => urldecode($parts[1]),
        'alliance_id' => (($parts[2]=='') ? '0' : $parts[2]),
        'points'      => $parts[3],
        'rank'        => $parts[4],
        'towns'       => $parts[5],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
//      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load alliance data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadAllianceData($World, $HeaderOnly = false)
  {
    $Endpoint = self::alliance_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 6) {
        $InvalidRows++;
        continue;
      }

      $aData[$parts[0]] = array(
        'grep_id' => $parts[0],
        'name'    => urldecode($parts[1]),
        'points'  => $parts[2],
        'towns'   => $parts[3],
        'members' => $parts[4],
        'rank'    => $parts[5],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      return array();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load town data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadTownData($World, $HeaderOnly = false)
  {
    $Endpoint = self::town_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 7) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'grep_id'           => $parts[0],
        'player_id'         => (($parts[1]=='') ? '0' : $parts[1]),
        'name'              => urldecode($parts[2]),
        'island_x'          => $parts[3],
        'island_y'          => $parts[4],
        'island_i'          => $parts[5], // number on island
        'points'            => $parts[6],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
//      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }


  /**
   * Load island data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadIslandData($World, $HeaderOnly = false)
  {
    $Endpoint = self::island_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 4) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'grep_id'  => $parts[0],
        'island_x' => $parts[1],
        'island_y' => $parts[2],
        'island_type' => $parts[3],
//        'free_spots' => $parts[4], // unused for now
//        'resource1' => $parts[5], // unused for now
//        'resource2' => $parts[6], // unused for now
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
//      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load conquers data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadConquersData($World, $HeaderOnly = false)
  {
    $Endpoint = self::conquers_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint, false, 2);
    if ($Data == false) return false;

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 7) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'town_id' => $parts[0],
        'time'    => date('Y-m-d H:i:s', $parts[1]), // UTC timestamp!
        'n_p_id'  => (($parts[2]=='') ? '0' :(string)  $parts[2]),
        'o_p_id'  => (($parts[3]=='') ? '0' :(string)  $parts[3]),
        'n_a_id'  => (($parts[4]=='') ? '0' :(string)  $parts[4]),
        'o_a_id'  => (($parts[5]=='') ? '0' :(string)  $parts[5]),
        'points'  => $parts[6],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load player att data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadPlayerAttData($World, $HeaderOnly = false)
  {
    $Endpoint = self::player_att_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) {
      return self::getHeader($Url, 3);
    }

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 3) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'rank'      => $parts[0],
        'player_id' => $parts[1],
        'points'    => $parts[2],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load player kills data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadPlayerKillsData($World, $HeaderOnly = false)
  {
    $Endpoint = self::player_kills_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) {
      return self::getHeader($Url, 3);
    }

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 3) {
        $InvalidRows++;
        continue;
      }

      $aData[$parts[1]] = array(
        'rank'      => $parts[0],
        'points'    => $parts[2],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load player def data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadPlayerDefData($World, $HeaderOnly = false)
  {
    $Endpoint = self::player_def_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 3) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'rank'      => $parts[0],
        'player_id' => $parts[1],
        'points'    => $parts[2],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load alliance att data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadAllianceAttData($World, $HeaderOnly = false)
  {
    $Endpoint = self::alliance_att_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 3) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'rank'        => $parts[0],
        'alliance_id' => $parts[1],
        'points'      => $parts[2],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  /**
   * Load alliance def data for given world
   *
   * @param String $World Grepolis world id
   * @param bool $HeaderOnly Return only the header contents for the selected data endpoint
   * @return array|bool Endpoint data or endpoint header content
   */
  public static function loadAllianceDefData($World, $HeaderOnly = false)
  {
    $Endpoint = self::alliance_def_endpoint;
    $Url = 'https://' . $World . '.' . self::endpoint_prefix . $Endpoint;
    if ($HeaderOnly) return self::getHeader($Url);

    $Data = self::getFileData($Url, $Endpoint);

    $aData = array();
    $InvalidRows = 0;
    foreach ($Data as $row) {
      $parts = explode(',', str_replace("\n",'',$row));
      if (sizeof($parts) < 3) {
        $InvalidRows++;
        continue;
      }

      $aData[] = array(
        'rank'        => $parts[0],
        'alliance_id' => $parts[1],
        'points'      => $parts[2],
      );
    }

    if (sizeof($aData) <= 0) {
      Logger::error("Found 0 valid data rows while loading inno data for endpoint " . $Endpoint);
      die();
    }

    if ($InvalidRows > 0) {
      Logger::warning("Retrieved ".$InvalidRows." invalid rows from endpoint: " . $Endpoint);
    }

    return $aData;
  }

  private static function getFileData($Url, $Endpoint, $FailOnMissingData = true, $MaxRetries = self::max_retries)
  {
    ini_set('default_socket_timeout', 30);
    $Attempts   = 1;
    $bFinished  = false;
    $aData      = '';
    while ($Attempts <= $MaxRetries && !$bFinished) {
      try {
        // Delay retries
        usleep($Attempts * 500000);

        // download data
        $Data = gzdecode(file_get_contents($Url));
        $handle = gzopen($Endpoint, 'w');
        gzwrite($handle, $Data);
        gzclose($handle);
        $Data = gzfile($Endpoint);

        if (sizeof($Data) <= 0) throw new \Exception("Unable to retrieve data from endpoint");
        else {
          $aData      = $Data;
          $bFinished  = true;
        }
      } catch (\Exception $e) {
        Logger::warning("Caught exception while loading inno data endpoint ".$Endpoint.". Attempt ".$Attempts." of ".$MaxRetries.". (".$e->getMessage().")");
        $Attempts += 1;
      }
    }

    if ($bFinished) return $aData;
    else {
      if (!$FailOnMissingData) {
        return false; // Dont fail on conquests
      } else {
        Logger::warning('Failed loading Inno data from endpoint ' . $Endpoint . '. Reached max retries: '.$Attempts.' of '.$MaxRetries.'.');
        return false;
      }
    }

  }

  /**
   * Returns true if the endpoint exists
   * @param $ServerId string Grepolis world server identifier
   * @return bool
   */
  public static function testEndpoint($ServerId) {
    $bExists = false;
    $Endpoint = self::player_att_endpoint;
    $Url = 'https://' . $ServerId . '.' . self::endpoint_prefix . $Endpoint;
    try {
      $aHeaders = get_headers($Url, 1);
      $sHeaders = @implode("", $aHeaders);
      if (sizeof($aHeaders) > 0
        && isset($aHeaders['Last-Modified'])
        && isset($aHeaders['ETag'])
        && strpos($sHeaders, '200') !== false
        && strpos($sHeaders, '404') === false) {
        //echo "Exists!" . PHP_EOL;
        $bExists = true;
      }
    } catch (\Exception $e) {}
    return $bExists;
  }

  private static function getHeader($Url, $FailOnMissingData = true, $MaxRetries = self::max_retries)
  {
    ini_set('default_socket_timeout', 10);
    $Attempts   = 0;
    $bFinished  = false;
    $aData      = '';
    while ($Attempts <= $MaxRetries && !$bFinished) {
      try {
        // Delay retries
        usleep($Attempts * 500000);

        // download headers
        $aHeaders = get_headers($Url, 1);
        if (sizeof($aHeaders) <= 0) throw new \Exception("No header data found for url " . $Url);
        if (!isset($aHeaders['Last-Modified'])) throw new \Exception("No header 'last-modified' found for url " . $Url . " Headers: " . json_encode($aHeaders));
        if (!isset($aHeaders['ETag'])) throw new \Exception("No etag found for url " . $Url);
        else {
          $aData      = $aHeaders;
          $bFinished  = true;
        }
      } catch (\Exception $e) {
        Logger::warning("Caught exception while loading inno headers from url ' . $Url . '. Attempt ".$Attempts." of ".$MaxRetries.". (".$e->getMessage().")");
        $Attempts += 1;
      }
    }

    if ($bFinished) return $aData;
    else {
      if (!$FailOnMissingData) return false; // Dont fail on conquests
      Logger::warning('Failed loading Inno data headers from url ' . $Url . '. Reached max retries: '.$Attempts.' of '.$MaxRetries.'.');
      return false;
    }
  }
}
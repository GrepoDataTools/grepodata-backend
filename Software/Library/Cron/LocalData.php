<?php

namespace Grepodata\Library\Cron;

use Grepodata\Library\Logger\Logger;

class LocalData
{
  const local_storage_dir   = TEMP_DIRECTORY;
  const local_map_dir       = MAP_DIRECTORY;
  const local_data_dir      = DATA_DIRECTORY;
  const town_file           = 'towns.csv';
  const player_file         = 'players.csv';
  const alliance_file       = 'alliances.csv';
  const player_att_file     = 'players_att.csv';
  const player_def_file     = 'players_def.csv';
  const player_idle_file    = 'player_idle.json';

  /**
   * Load local town data for given world
   *
   * @param String $World Grepolis world id
   * @return array|bool Local town data
   */
  public static function getLocalTownData($World)
  {
    return self::loadFileData($World, self::town_file);
  }

  /**
   * Save local town data for given world
   *
   * @param String $World Grepolis world id
   * @param array $aData Town data
   */
  public static function setLocalTownData($World, $aData)
  {
    self::saveFileData($World, self::town_file, $aData);
  }

  /**
   * Load local player attack data for given world
   *
   * @param String $World Grepolis world id
   * @return array|bool Local player att data
   */
  public static function getLocalPlayerAttackData($World)
  {
    return self::loadFileData($World, self::player_att_file);
  }

  /**
   * Save local player attack data for given world
   *
   * @param String $World Grepolis world id
   * @param array $aData Player attack data
   */
  public static function setLocalPlayerAttackData($World, $aData)
  {
    self::saveFileData($World, self::player_att_file, $aData);
  }

  /**
   * Load local player defence data for given world
   *
   * @param String $World Grepolis world id
   * @return array|bool Local player def data
   */
  public static function getLocalPlayerDefenceData($World)
  {
    return self::loadFileData($World, self::player_def_file);
  }

  /**
   * Save local player defence data for given world
   *
   * @param String $World Grepolis world id
   * @param array $aData Player defence data
   */
  public static function setLocalPlayerDefenceData($World, $aData)
  {
    self::saveFileData($World, self::player_def_file, $aData);
  }

  /**
   * Load local player data for given world
   *
   * @param String $World Grepolis world id
   * @return array|bool Local player data
   */
  public static function getLocalPlayerData($World)
  {
    return self::loadFileData($World, self::player_file);
  }

  /**
   * Save local player data for given world
   *
   * @param String $World Grepolis world id
   * @param array $aData Player data
   */
  public static function setLocalPlayerData($World, $aData)
  {
    self::saveFileData($World, self::player_file, $aData);
  }

  /**
   * Load local alliance data for given world
   *
   * @param String $World Grepolis world id
   * @return array|bool Local alliance data
   */
  public static function getLocalAllianceData($World)
  {
    return self::loadFileData($World, self::alliance_file);
  }

  /**
   * Save local alliance data for given world
   *
   * @param String $World Grepolis world id
   * @param array $aData Alliance data
   */
  public static function setLocalAllianceData($World, $aData)
  {
    self::saveFileData($World, self::alliance_file, $aData);
  }

  /**
   * Save player idle times data for given world
   *
   * @param String $World Grepolis world id
   * @param array $aData Player idle times
   */
  public static function savePlayerIdleTimes($World, $aData)
  {
    self::saveJsonData($World, self::player_idle_file, $aData);
  }

  private static function loadFileData($World, $Endpoint)
  {
    $Filename = self::local_storage_dir . $World . '/' . $Endpoint;

    if (!file_exists($Filename)) {
      Logger::warning("Previous local data does not yet exist: " . $Filename);
      return false;
    }

    switch ($Endpoint) {
      case self::town_file:
        $FnUpdate = function (&$aOutputData, $data) {
          if (count($data) < 4) {
            Logger::warning("Town data row has invalid number of columns!");
          }
          $aOutputData[$data[0]] = array(
            'grep_id'   => $data[0],
            'player_id' => $data[1],
            'name'      => $data[2],
            'island_x'  => $data[3],
            'island_y'  => $data[4],
            'island_i'  => $data[5],
            'points'    => isset($data[6]) ? $data[6] : 0,
          );
        };
        break;
      case self::player_att_file:
      case self::player_def_file:
        $FnUpdate = function (&$aOutputData, $data) {
          if (count($data) < 3) {
            Logger::warning("Attack/def data row has invalid number of columns!");
          }
          $aOutputData[$data[1]] = array(
            'rank' => $data[0],
            'points' => $data[2],
          );
        };
        break;
      case self::player_file:
        $FnUpdate = function (&$aOutputData, $data) {
          if (count($data) < 6) {
            Logger::warning("Player/ally data row has invalid number of columns!");
          }
          $aOutputData[$data[0]] = array(
            'grep_id'     => $data[0],
            'name'        => $data[1],
            'alliance_id' => $data[2],
            'points'      => $data[3],
            'rank'        => $data[4],
            'towns'       => $data[5],
          );
        };
        break;
      case self::alliance_file:
        $FnUpdate = function (&$aOutputData, $data) {
          if (count($data) < 6) {
            Logger::warning("Player/ally data row has invalid number of columns!");
          }
          $aOutputData[$data[0]] = array(
            'grep_id'     => $data[0],
            'name'        => $data[1],
            'points'      => $data[2],
            'towns'       => $data[3],
            'members'     => $data[4],
            'rank'        => $data[5],
          );
        };
        break;
      default:
        Logger::error("Invalid local endpoint: " . $Endpoint);
        return false;
    }

    $aFileData = array();
    try {
      if (($handle = fopen($Filename, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
          $FnUpdate($aFileData, $data);
        }
        fclose($handle);
      } else {
        Logger::warning("Unable to load local data for filename: " . $Filename);
        return false;
      }
    } catch (\Exception $e) {
      Logger::warning("Error loading local data for filename: " . $Filename . " {".$e->getMessage()."} [".$e->getTraceAsString()."]");
      return false;
    }
    return $aFileData;
  }

  private static function saveFileData($World, $Endpoint, $aData)
  {
    $Filename = self::local_storage_dir . $World . '/' . $Endpoint;

    try {
      // Delete old data
      if (file_exists($Filename)) {
        unlink($Filename);
      }

      // Ensure directory
      self::ensureWorldDirectory($World);

      // Write data
      $fp = fopen($Filename, 'w');
      if ($fp !== false) {
        foreach ($aData as $fields) {
          fputcsv($fp, $fields, ",");
        }
        fclose($fp);
        Logger::silly("Previous file saved to: " . $Filename);
      } else {
        Logger::error("Unable to open previous file for writing.");
        return false;
      }
    } catch (\Exception $e) {
      Logger::error("Error saving town data to disk: " . $e->getMessage());
      return false;
    }
    return true;
  }

  /**
   * @param $World
   * @param $Endpoint
   * @param $aData
   * @return bool
   */
  private static function saveJsonData($World, $Endpoint, $aData)
  {
    $Filename = self::local_data_dir . $World . '/' . $Endpoint;

    try {
      // Delete old data
      if (file_exists($Filename)) {
        unlink($Filename);
      }

      // Ensure directory
      self::ensureDataDirectory($World);

      // Write data
      $fp = fopen($Filename, 'w');
      if ($fp !== false) {
        $JsonData = json_encode($aData);
        fwrite($fp, $JsonData);
        fclose($fp);
        Logger::silly("Json file saved to: " . $Filename);
      } else {
        Logger::error("Unable to open json file for writing.");
        return false;
      }
    } catch (\Exception $e) {
      Logger::error("Error saving json data to disk: " . $e->getMessage());
      return false;
    }
    return true;
  }

  public static function saveMap($img, $World, $DateString) {
    // Ensure directory
    self::ensureMapDirectory($World);

    $Filename = self::local_map_dir . $World . '/map_'.$DateString.'.png';

    // Delete old data
    if (file_exists($Filename)) {
      unlink($Filename);
    }

    imagepng($img, $Filename);
    return $Filename;
  }

  private static function ensureDataDirectory($World)
  {
    $Dir = self::local_data_dir . $World;
    if (!is_dir($Dir)) {
      Logger::warning("Created world directory in data folder: " . $Dir);
      mkdir($Dir);
    }
  }

  private static function ensureWorldDirectory($World)
  {
    $Dir = self::local_storage_dir . $World;
    if (!is_dir($Dir)) {
      Logger::warning("Created world directory in temp folder: " . $Dir);
      mkdir($Dir);
    }
  }

  private static function ensureMapDirectory($World)
  {
    $Dir = self::local_map_dir . $World;
    if (!is_dir($Dir)) {
      Logger::warning("Created world directory in map folder: " . $Dir);
      mkdir($Dir);
    }
  }
}

<?php

namespace Grepodata\Library\Controller;

use Grepodata\Library\Model\TownOffset;
use Illuminate\Database\Eloquent\Model;

class Island
{

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Island Island
   */
  public static function first($Id, $World)
  {
    return \Grepodata\Library\Model\Island::where('world', '=', $World, 'and')
      ->where('grep_id', '=', $Id)
      ->first();
  }

  /**
   * @param $X
   * @param $Y
   * @param $World
   * @return \Grepodata\Library\Model\Island Island
   */
  public static function firstByXY($X, $Y, $World)
  {
    return \Grepodata\Library\Model\Island::where('world', '=', $World, 'and')
      ->where('island_x', '=', $X, 'and')
      ->where('island_y', '=', $Y)
      ->first();
  }

  /**
   * @param $World
   * @return \Grepodata\Library\Model\Island Island
   */
  public static function firstByWorld($World)
  {
    return \Grepodata\Library\Model\Island::where('world', '=', $World)
      ->first();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Island Island
   */
  public static function firstOrFail($Id, $World)
  {
    return \Grepodata\Library\Model\Island::where('world', '=', $World, 'and')
      ->where('grep_id', '=', $Id)
      ->firstOrFail();
  }

  /**
   * @param $Id
   * @param $World
   * @return \Grepodata\Library\Model\Island | Model Island
   */
  public static function firstOrNew($Id, $World)
  {
    return \Grepodata\Library\Model\Island::firstOrNew(array(
      'world'   => $World,
      'grep_id' => $Id,
    ));
  }

  /**
   * @param $World
   * @param $x_min
   * @param $x_max
   * @param $y_min
   * @param $y_max
   * @return \Grepodata\Library\Model\Island[]
   */
  public static function allInRange($World, $x_min, $x_max, $y_min, $y_max)
  {
    // TODO: cache in Redis, requests will be duplicated and data does not change frequently
    return \Grepodata\Library\Model\Island::where('world', '=', $World, 'and')
      ->where('island_x','>=', $x_min)
      ->where('island_x','<=', $x_max)
      ->where('island_y','>=', $y_min)
      ->where('island_y','<=', $y_max)
      ->cursor();
  }

  /**
   * Helper function to give the absolute coordinates of a town based on the island and town offset
   * @param \Grepodata\Library\Model\Island $oIsland
   * @param TownOffset $oTownOffset
   * @return array [x, y]
   */
  public static function getAbsoluteTownCoordinates(\Grepodata\Library\Model\Island $oIsland, TownOffset $oTownOffset)
  {
    $IslandAbsX = 128 * $oIsland->island_x;
    $IslandAbsY = 128 * $oIsland->island_y;
    $TownAbsX = $IslandAbsX + $oTownOffset->town_offset_x;
    $TownAbsY = $IslandAbsY + $oTownOffset->town_offset_y;

    return array(
      $TownAbsX, //
      $oIsland->island_x % 2 == 1 ? $TownAbsY+64 : $TownAbsY, // add 64 (= half of ytile size) if islandx is odd
    );
  }
}

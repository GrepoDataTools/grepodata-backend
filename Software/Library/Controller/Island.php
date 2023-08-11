<?php

namespace Grepodata\Library\Controller;

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
}

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
}
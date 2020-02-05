<?php

namespace Grepodata\Library\Controller;

class WorldMap
{
  /**
   * @param $World
   * @param $Date
   * @param $Filename
   * @return \Grepodata\Library\Model\WorldMap
   */
  public static function firstOrNew($World, $Date, $Filename)
  {
    return \Grepodata\Library\Model\WorldMap::firstOrNew(array(
      'world' => $World,
      'date' => $Date,
      'filename' => $Filename
    ));
  }
}
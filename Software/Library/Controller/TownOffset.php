<?php

namespace Grepodata\Library\Controller;

class TownOffset
{

  /**
   * This helper function returns the hashmap key for a town
   * @param \Grepodata\Library\Model\Town $oTown
   * @return string
   */
  public static function getKeyForTown(\Grepodata\Library\Model\Town $oTown) {
    return $oTown->island_type . "_" . $oTown->island_i; // -> $Offset->island_type_idx . "_" . $Offset->town_offset_idx
  }

  /**
   * Get all town offsets as a hashmap
   * @return \Grepodata\Library\Model\TownOffset[]
   */
  public static function getAllAsHasmap()
  {
    $aTownOffsets = \Grepodata\Library\Model\TownOffset::get();
    $aOffsetMap = array();
    /** @var \Grepodata\Library\Model\TownOffset $Offset */
    foreach ($aTownOffsets as $Offset) {
      $aOffsetMap[$Offset->island_type_idx . "_" . $Offset->town_offset_idx] = $Offset;
    }
    return $aOffsetMap;

  }

  /**
   * Helper function to give the absolute coordinates of a town based on the island and town offset
   * @param \Grepodata\Library\Model\Town $oTown
   * @param \Grepodata\Library\Model\TownOffset $oTownOffset
   * @return array [x, y]
   */
  public static function getAbsoluteTownCoordinates(\Grepodata\Library\Model\Town $oTown, \Grepodata\Library\Model\TownOffset $oTownOffset)
  {
    $IslandAbsX = 128 * $oTown->island_x;
    $IslandAbsY = 128 * $oTown->island_y;
    $TownAbsX = $IslandAbsX + $oTownOffset->town_offset_x;
    $TownAbsY = $IslandAbsY + $oTownOffset->town_offset_y;

    return array(
      $TownAbsX, //
      $oTown->island_x % 2 == 1 ? $TownAbsY+64 : $TownAbsY, // add 64 (= half of ytile size) if islandx is odd
    );
  }

}

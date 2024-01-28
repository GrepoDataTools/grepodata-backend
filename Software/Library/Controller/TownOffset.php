<?php

namespace Grepodata\Library\Controller;

class TownOffset
{

  /**
   * This helper function returns the hashmap key for a town
   * @param \Grepodata\Library\Model\Town $oTown
   * @param \Grepodata\Library\Model\Island $oIsland
   * @return string
   */
  public static function getKeyForTown(\Grepodata\Library\Model\Town $oTown, \Grepodata\Library\Model\Island $oIsland) {
    return $oIsland->island_type . "_" . $oTown->island_i; // -> $Offset->island_type_idx . "_" . $Offset->town_offset_idx
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

}

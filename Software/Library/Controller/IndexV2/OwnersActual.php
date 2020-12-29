<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\Indexer\IndexInfo;

class OwnersActual
{

  /**
   * Get all actual owners for an index
   * @param IndexInfo $oIndex
   * @return \Grepodata\Library\Model\IndexV2\OwnersActual[]
   */
  public static function getAllByIndex(IndexInfo $oIndex) {
    return \Grepodata\Library\Model\IndexV2\OwnersActual::where('index_key', '=', $oIndex->key_code)
      ->get();
  }

  /**
   * get a single owner by index and alliance id
   * @param IndexInfo $oIndex
   * @param $alliance_id
   * @return \Grepodata\Library\Model\IndexV2\OwnersActual
   */
  public static function getByIndexByAllianceId(IndexInfo $oIndex, $alliance_id)
  {
    return \Grepodata\Library\Model\IndexV2\OwnersActual::where('index_key', '=', $oIndex->key_code)
      ->where('alliance_id', '=', $alliance_id)
      ->firstOrFail();
  }

}
<?php

namespace Grepodata\Library\Controller\IndexV2;

use Grepodata\Library\Model\Indexer\IndexInfo;

class Settings
{

  /**
   * Get settings for an index
   * @param IndexInfo $oIndex
   * @return \Grepodata\Library\Model\IndexV2\Settings
   */
  public static function getSettingsByIndex(IndexInfo $oIndex) {
    return \Grepodata\Library\Model\IndexV2\Settings::firstOrNew(array(
        'index_key' => $oIndex->key_code
      ));
  }

}
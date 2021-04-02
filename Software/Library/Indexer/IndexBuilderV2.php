<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\World;

class IndexBuilderV2
{

  public function __construct() {
  }

  /**
   * @param $World
   * @param $IndexName
   * @param $UserId
   * @return IndexInfo
   */
  public static function buildNewIndex($World, $IndexName, $UserId) {
    // Find a new key
    $NewIndexKey = self::generateIndexKey(8);
    while (self::indexExists($NewIndexKey)) {
        $NewIndexKey = self::generateIndexKey(8);
    }

    // Insert new index
    $oIndex = new IndexInfo();
    $oIndex->key_code = $NewIndexKey;
    $oIndex->index_name = $IndexName;
    $oIndex->created_by_user = $UserId;
    $oIndex->world = $World;
    $oIndex->mail = '';
    $oIndex->status = 'active';
    $oIndex->share_link = self::generateIndexKey(10);
    $oIndex->script_version = USERSCRIPT_VERSION;
    $oIndex->save();

    return $oIndex;
  }

  public static function generateIndexKey($length = 8) {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i = 0; $i < $length; $i++) {
          $randomString .= $characters[rand(0, $charactersLength - 1)];
      }
      return $randomString;
  }

  private static function indexExists($key) {
    $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::first($key);
    if ($oIndex === null) return false;
    return true;
  }

}




?>

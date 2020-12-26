<?php

namespace Grepodata\Library\Controller\IndexV2;

use Illuminate\Database\Eloquent\Model;

class IndexOwners
{

  /**
   * @param $Key string Index identifier
   * @return \Grepodata\Library\Model\Indexer\IndexOwners
   */
  public static function firstOrFail($Key)
  {
    return \Grepodata\Library\Model\Indexer\IndexOwners::where('key_code', '=', $Key)
      ->firstOrFail();
  }

  /**
   * @param $Key string Index identifier
   * @return \Grepodata\Library\Model\Indexer\IndexOwners | Model
   */
  public static function firstOrNew($Key)
  {
    return \Grepodata\Library\Model\Indexer\IndexOwners::firstOrNew(array(
      'key_code'    => $Key
    ));
  }

}
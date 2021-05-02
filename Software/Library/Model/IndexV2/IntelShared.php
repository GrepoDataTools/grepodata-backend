<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed intel_id
 * @property mixed report_hash
 * @property mixed index_key
 * @property mixed user_id
 * @property mixed player_id
 * @property mixed world
 */
class IntelShared extends Model
{
  protected $table = 'Indexer_intel_shared';

}

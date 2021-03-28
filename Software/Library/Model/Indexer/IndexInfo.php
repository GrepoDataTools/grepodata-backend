<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed key_code
 * @property mixed index_name
 * @property mixed world
 * @property mixed script_version
 * @property mixed index_version
 * @property mixed mail
 * @property mixed created_by_user
 * @property mixed new_report
 * @property mixed status
 * @property mixed csa
 * @property mixed share_link
 * @property mixed delete_old_intel_days
 * @property mixed allow_join_v1_key
 * @property mixed moved_to_index
 */
class IndexInfo extends Model
{
  protected $table = 'Index_info';
}

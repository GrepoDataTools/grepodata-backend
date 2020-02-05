<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed key_code
 * @property mixed world
 * @property mixed mail
 * @property mixed new_report
 * @property mixed status
 * @property mixed csa
 * @property mixed moved_to_index
 */
class IndexInfo extends Model
{
  protected $table = 'Index_info';
}

<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed index_key
 * @property mixed player_id
 * @property mixed report_id
 * @property mixed index_report_id
 * @property mixed index_type
 */
class ReportId extends Model
{
  protected $table = 'Index_report_hash';
}

<?php

namespace Grepodata\Library\Model\Indexer;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed index_code
 * @property mixed type
 * @property mixed report_poster
 * @property mixed fingerprint
 * @property mixed report_json
 * @property mixed report_info
 * @property mixed script_version
 * @property mixed debug_explain
 * @property mixed city_id
 */
class Report extends Model
{
  protected $table = 'Index_report';
}

<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed uid
 * @property mixed action
 * @property mixed team
 * @property mixed num_uploaded
 * @property mixed num_created
 * @property mixed num_updated
 * @property mixed num_deleted
 * @property mixed processing_ms
 * @property mixed created_at
 * @property mixed updated_at
 */
class CommandLog extends Model
{
  protected $table = 'Command_log';
}

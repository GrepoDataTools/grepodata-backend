<?php

namespace Grepodata\Library\Model\IndexV2;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string index_key
 * @property boolean hide_allied_intel
 * @property int delete_old_intel_days
 */
class Settings extends Model
{
  protected $table = 'Indexer_settings';
  protected $fillable = array('index_key');

}

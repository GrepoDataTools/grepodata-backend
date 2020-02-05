<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed date
 * @property mixed world
 * @property mixed filename
 * @property mixed zoom
 * @property mixed colormap
 */
class WorldMap extends Model
{
  protected $table = 'World_map';
  protected $fillable = array('world', 'date', 'filename');
}

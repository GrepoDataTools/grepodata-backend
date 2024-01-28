<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed island_type_idx // FK: Island->island_type
 * @property mixed island_img
 * @property mixed island_width
 * @property mixed island_height
 * @property mixed centering_offset_x
 * @property mixed centering_offset_y
 * @property mixed town_offset_idx // FK: Town->island_i
 * @property mixed town_offset_x
 * @property mixed town_offset_y
 * @property mixed town_offset_fx
 * @property mixed town_offset_fy
 * @property mixed town_dir_offset_x
 * @property mixed town_dir_offset_y
 * @property mixed town_dir
 */
class TownOffset extends Model
{
  protected $table = 'Town_offset';

  // Index: island_type_idx, town_offset_idx
}

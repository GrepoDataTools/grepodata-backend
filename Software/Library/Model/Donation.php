<?php

namespace Grepodata\Library\Model;

use \Illuminate\Database\Eloquent\Model;

/**
 * @property mixed donation_id
 * @property mixed donation
 * @property mixed name
 * @property mixed date
 * @property mixed source
 * @property mixed note
 * @property mixed country
 */
class Donation extends Model
{
  protected $table = 'Donations';
  protected $fillable = array('donation_id', 'donation', 'name', 'date', 'source', 'note', 'country');
}

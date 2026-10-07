<?php

namespace Grepodata\Library\Controller;

use Carbon\Carbon;
use Grepodata\Library\Logger\Logger;

class Donation
{

  /**
   * @param int $Limit
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function GetDonations($Limit = 100)
  {
    return \Grepodata\Library\Model\Donation::orderBy('date', 'desc')
      ->limit($Limit)
      ->get();
  }

  /**
   * Public donation fields only (no donation_id/source/timestamps)
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public static function GetAllPublicDonations()
  {
    return \Grepodata\Library\Model\Donation::orderBy('date', 'desc')
      ->get(array('donation', 'name', 'date', 'country', 'note'));
  }

  /**
   * @return float
   */
  public static function SumCurrentMonth()
  {
    $Sum = \Grepodata\Library\Model\Donation::whereBetween('date', array(
      Carbon::now()->startOfMonth(),
      Carbon::now()->endOfMonth()
    ))->sum('donation');
    return (float)$Sum;
  }

  /**
   * @param int $DonationId BuyMeACoffee support_id, used as unique dedup key
   * @param float $Amount
   * @param string $Name
   * @param \Carbon\Carbon $Date
   * @param string $Source
   * @param string|null $Note
   * @param string|null $Country
   * @return \Grepodata\Library\Model\Donation|bool Saved model when a new donation was inserted, false when it was a duplicate or failed
   */
  public static function AddDonation($DonationId, $Amount, $Name, $Date, $Source, $Note = null, $Country = null)
  {
    $oDonation = new \Grepodata\Library\Model\Donation();
    $oDonation->donation_id = $DonationId;
    $oDonation->donation    = $Amount;
    $oDonation->name        = $Name;
    $oDonation->date        = $Date;
    $oDonation->source      = $Source;
    $oDonation->note        = $Note;
    $oDonation->country     = $Country;

    try {
      $oDonation->save();
      return $oDonation;
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        // Donation was already recorded on a previous run
        Logger::debugInfo('Duplicate donation ignored: donation_id=' . $DonationId);
      } else {
        Logger::error('Error saving donation entry: ' . $e->getMessage());
      }
      return false;
    }
  }

  /**
   * Creates or updates the single row representing a month's AdSense estimated earnings
   * @param string $MonthKey Format: YYYY-MM
   * @param float $Amount
   * @return \Grepodata\Library\Model\Donation|bool
   */
  public static function UpsertAdsenseEarnings($MonthKey, $Amount)
  {
    try {
      $Date = Carbon::createFromFormat('Y-m', $MonthKey)->startOfMonth();
      return \Grepodata\Library\Model\Donation::updateOrCreate(
        array('donation_id' => 'adsense-' . $MonthKey),
        array(
          'donation' => $Amount,
          'name'     => 'Google AdSense',
          'date'     => $Date,
          'source'   => 'adsense',
          'note'     => 'Ad revenue ' . $Date->format('F Y'),
        )
      );
    } catch (\Exception $e) {
      Logger::error('Error saving adsense earnings entry (' . $MonthKey . '): ' . $e->getMessage());
      return false;
    }
  }

}

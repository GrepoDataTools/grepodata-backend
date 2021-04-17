<?php

namespace Grepodata\Application\debugger;

use Grepodata\Library\Cron\Common;
use Grepodata\Library\Model\IndexV2\Intel;

require('./../../config.php');

error_reporting(0);

try {

  $ReportId = $_POST['id'];
  /** @var Intel $Report */
  $Report = Intel::where('id', '=', $ReportId)->first();

  if ($Report->report_json === null || $Report->report_json === '') {
    die('Report JSON unavailable');
  }

  try {
    if ($_POST['reparse'] === 'true') {
      $oCity = Common::debugIndexer($Report);
    } else {
      $oCity = $Report;
    }
  } catch (\Exception $e) {
    die('<table>'.$e->xdebug_message.'</table>');
  }

  /** @var Intel $oCity */
  if (!is_null($oCity) && $oCity != false) {
    try {
      $Table = '<table class="city-table" style="width: 100%; color: #84b593; background: #2b2b2b;"><tr>';
      foreach ($oCity->getPublicFields() as $key => $value) {
        $Table .= "<th>$key</th>";
      }
      $Table .= '</tr><tr>';
      foreach ($oCity->getPublicFields() as $key => $value) {
        if ($key === 'player_id') {
          $value = '<a class="link" href="https://grepodata.com/intel/player/'.$oCity->world.'/'.$value.'" target="_blank">'.$value.'</a>';
        }
        else if ($key === 'town_id') {
          $value = '<a class="link" href="https://grepodata.com/intel/town/'.$oCity->world.'/'.$value.'" target="_blank">'.$value.'</a>';
        }
        else if (is_array($value)) {
          $value = str_replace(',', ' ', json_encode($value));
        }
        $Table .= "<td>$value</td>";
      }
      $Table .= '</tr></table>';
      $aParsed = $Table;

      if (!is_null($oCity->conquest_details)) {
        $aParsed .= "<p>".str_replace(',', ', ', $oCity->conquest_details)."</p>";
      }
    } catch (\Exception $e) {
      $aParsed .= ' - Unable to load city: ' . $e->getMessage();
    }
  }

  echo $aParsed;

} catch (\Exception $e) {
  echo $e->getMessage();
}

?>

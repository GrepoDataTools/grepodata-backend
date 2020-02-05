<?php

namespace Grepodata\Application\debugger;

use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\Report;

require('./../../config.php');

error_reporting(0);

try {

  $ReportId = $_POST['id'];
  /** @var Report $Report */
  $Report = Report::where('id', '=', $ReportId)->first();
  /** @var \Grepodata\Library\Model\Indexer\IndexInfo $Index */
  $Index = IndexInfo::firstOrFail($Report->index_code);

  if ($Report->report_json === null || $Report->report_json === '') {
    die('Report JSON unavailable');
  }

  if ($Report->city_id <= 0 || $_POST['reparse'] === 'true') {
    $aParsed = Common::debugIndexer($Report, $Index, $_POST['reparse']==='true', $_POST['rebuild']==='true');
  } else {
    $aParsed = $Report->city_id;
  }

  if (is_numeric($aParsed) && $aParsed != -1) {
    try {
      /** @var City $oCity */
      $oCity = City::where('id', '=' , $aParsed)->firstOrFail();
      $aParsed = json_encode($oCity);
      $Table = '<table class="city-table" style="width: 100%; color: #84b593; background: #2b2b2b;"><tr>';
      foreach ($oCity->getPublicFields() as $key => $value) {
        $Table .= "<th>$key</th>";
      }
      $Table .= '</tr><tr>';
      foreach ($oCity->getPublicFields() as $key => $value) {
        if ($key === 'player_id') {
          $value = '<a class="link" href="https://grepodata.com/indexer/player/'.$Index->key_code.'/'.$Index->world.'/'.$value.'" target="_blank">'.$value.'</a>';
        }
        else if ($key === 'town_id') {
          $value = '<a class="link" href="https://grepodata.com/indexer/town/'.$Index->key_code.'/'.$Index->world.'/'.$value.'" target="_blank">'.$value.'</a>';
        }
        else if (is_array($value)) {
          $value = str_replace(',', ' ', json_encode($value));
        }
        $Table .= "<td>$value</td>";
      }
      $Table .= '</tr></table>';
      $aParsed = $Table;
    } catch (\Exception $e) {
      $aParsed .= ' - Unable to load city: ' . $e->getMessage();
    }
  }

  echo $aParsed;

} catch (\Exception $e) {
  echo $e->getMessage();
}

?>
<?php

namespace Grepodata\Application\debugger;

use Grepodata\Library\Indexer\Helper;
use Grepodata\Library\Model\IndexV2\Intel;
use Grepodata\Library\Model\IndexV2\IntelShared;

$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$ReportId = array_shift($request);

require('./../../config.php');


/** @var Intel $Report */
$Report = Intel::where('id', '=', $ReportId)->first();
/** @var IntelShared[] $IntelShared */
$IntelShared = IntelShared::where('intel_id', '=', $ReportId)->get();
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="/game.css">

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
</head>
<body>

<style>
    .link {
        color: #859fb9;
        text-decoration: none;
        cursor: hand;
    }
    body {
        background: #1f1e1e;
    }
    .city-table {
        border: 1px solid #ccc;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        width: 100%;
        table-layout: auto;
    }

    .city-table caption {
        font-size: 1.5em;
        margin: .5em 0 .75em;
    }

    .city-table tr {
        /*background-color: #f8f8f8;*/
        background-color: #191e1d;;
        border: 1px solid #384146;
        /*border: 1px solid #ddd;*/
        padding: .35em;
    }
    .city-table tr.error {
        border: 2px solid #f00;
        background: #6c2128;
    }
    .city-table tr.warning {
        border: 2px solid #8a821e;
        background: #252217;
    }

    .city-table th,
    .city-table td {
        padding: .225em;
        text-align: center;
    }

    .city-table th {
        font-size: .85em;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
</style>



<div style="color: #78D965; font-size: 22px;">
    <a class="link" href="/debugger.php/<?php echo ($ReportId-1); ?>">Previous report</a>
    &nbsp;&nbsp;---&nbsp;&nbsp;
    <a class="link" href="/debugger.php/<?php echo ($ReportId+1); ?>">Next report</a>

    <h3>Indexer_intel_shared data:</h3>
    <p id="report">
      <?php
      $Table = '<table class="city-table" style="width: 100%; color: #84b593; background: #2b2b2b;"><tr>';
      foreach ($IntelShared[0]->attributesToArray() as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<th>$key</th>";
      }
      $Table .= '</tr>';
      foreach ($IntelShared as $oIntelShared) {
        $Table .= '<tr>';
        foreach ($oIntelShared->attributesToArray() as $key => $value) {
          if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<td>$value</td>";
        }
        $Table .= '</tr>';
      }
      $Table .= '</table>';
      echo $Table;
      ?>
    </p>

    <h3>Indexer_intel data:</h3>
    <p id="report">
      <?php
      $Table = '<table class="city-table" style="width: 100%; color: #84b593; background: #2b2b2b;"><tr>';
      foreach (array_slice($Report->attributesToArray(), 0, 10) as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<th>$key</th>";
      }
      $Table .= '</tr><tr>';
      foreach (array_slice($Report->attributesToArray(), 0, 10) as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<td>$value</td>";
      }
      $Table .= '</tr></table>';
      echo $Table;

      $Table = '<table class="city-table" style="width: 100%; color: #84b593; background: #2b2b2b;"><tr>';
      foreach (array_slice($Report->attributesToArray(), 10, 10) as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<th>$key</th>";
      }
      $Table .= '</tr><tr>';
      foreach (array_slice($Report->attributesToArray(), 10, 10) as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<td>$value</td>";
      }
      $Table .= '</tr></table>';
      echo $Table;

      $Table = '<table class="city-table" style="width: 100%; color: #84b593; background: #2b2b2b;"><tr>';
      foreach (array_slice($Report->attributesToArray(), 20) as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<th>$key</th>";
      }
      $Table .= '</tr><tr>';
      foreach (array_slice($Report->attributesToArray(), 20) as $key => $value) {
        if (!in_array($key, ['report_json', 'report_info'])) $Table .= "<td>$value</td>";
      }
      $Table .= '</tr></table>';
      echo $Table;
      ?>
    </p>

    <h3>Parsed output:
        <button onclick="parse(true)">Force reparse</button>&nbsp;&nbsp;
<!--        <button onclick="parse(true, true)">Parse & rebuild</button>-->
    </h3>
    <p id="output">loading...</p>
</div>


<?php

try {

	$html = Helper::JsonToHtml($Report);
  // Fix domain
  $html = str_replace('https://gpnl.innogamescdn.com/images/game/', 'http://api-grepodata-com.debugger:8080/images/', $html);
  $html = str_replace('https://gpnl.innogamescdn.com/', 'http://api-grepodata-com.debugger:8080/', $html);
  echo $html;

} catch (\Exception $e) {
  echo $e->getMessage();
}

?>




<script>
  function parse(reparse = false, rebuild = false) {
    document.getElementById('output').innerHTML = 'Parsing...';
    $.ajax({
      url: "/start_debugger.php",
      type: "post",
      data: {id: <?php echo $ReportId; ?>, reparse: reparse, rebuild: rebuild},
      success:function(result){
        console.log(result);
        document.getElementById('output').innerHTML = result;
      }
    });
  }
  parse()
</script>

</body>
</html>

<?php

namespace Grepodata\Application\debugger;

use Grepodata\Library\Model\Indexer\Report;

$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$pass = array_shift($request);

if ($pass !== PRIVATE_DEBUG_AUTH) {
  die('401: Unauthorized');
}

function isLocalhost($whitelist = ['127.0.0.1', '::1']) {
  return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
}
if (!isLocalhost()) {
  die('401: Unauthorized');
}

require('./../../config.php');

error_reporting(0);
?>

<html>
<head></head>
<body>

<style>
    .link {
        color: #859fb9;
        text-decoration: none;
        cursor: hand;
    }
    body {
        font-family: "Open Sans", sans-serif;
        line-height: 0.9;
        background: #1f1e1e;
        color: #b9b9b9;
    }
    button {
        background: #3d778a;
        color: #fff;
        border: 1px solid #b2c9c4;
        padding: 4px 20px;
        cursor: hand;
    }
    input, select {
        background: #1f1e1e;
        color: #fff;
        border: 1px solid #568d8e;
        padding: 4px;
    }
    table {
        border: 1px solid #ccc;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        width: 100%;
        table-layout: fixed;
    }

    table caption {
        font-size: 1.5em;
        margin: .5em 0 .75em;
    }

    table tr {
        /*background-color: #f8f8f8;*/
        background-color: #191e1d;;
        border: 1px solid #384146;
        /*border: 1px solid #ddd;*/
        padding: .35em;
    }
    table tr.error {
        border: 2px solid #f00;
        background: #6c2128;
    }
    table tr.warning {
        border: 2px solid #8a821e;
        background: #252217;
    }

    table th,
    table td {
        padding: .225em;
        text-align: center;
    }

    table th {
        font-size: .85em;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
</style>

<script>
  function clearFilters()
  {
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url = url.substring(0,url.indexOf("?"));
      window.location.href = url;
    }
  }
  function search()
  {
    var query = document.getElementById('query').value;
    var level = document.getElementById('level').value;
    var fingerprint = document.getElementById('fingerprint').value;
    var reporthash = document.getElementById('reporthash').value;
    var failed = document.getElementById('failed').checked;
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url += "&";
    } else {
      url += "?";
    }
    url += "query="+query+"&level="+level+"&fingerprint="+fingerprint+"&reporthash="+reporthash+"&failed="+failed;
    window.location.href = url;
  }
</script>

<input id="failed" type="checkbox" name="failed" <?php echo ($_GET['failed']=='true'?'checked':'') ?>> Only show failed reports
<input placeholder="by index key" id="query" type="text" name="text" onsubmit="search()" value="<?php echo $_GET['query'];?>"/>
<input placeholder="by fingerprint" id="fingerprint" type="text" name="fingerprint" autocomplete="off" onsubmit="search()" value="<?php echo $_GET['fingerprint'];?>"/>
<input placeholder="by report hash" id="reporthash" type="text" name="reporthash" autocomplete="off" onsubmit="search()" value="<?php echo $_GET['reporthash'];?>"/>
<select id="level" name="level">
    <option value="all" <?php echo isset($_GET['level']) && $_GET['level'] == 'all' ? 'selected':''?>>All</option>
    <option value="default" <?php echo isset($_GET['level']) && $_GET['level'] == 'default' ? 'selected':''?>>Forum</option>
    <option value="inbox" <?php echo isset($_GET['level']) && $_GET['level'] == 'inbox' ? 'selected':''?>>Inbox</option>
</select>
<button onclick="search()">Go</button>
<a onclick="clearFilters()" class="link">Clear filters</a>
<br/><br/>


<div id="debug-container" style="position: fixed; top:20px; left: 20px; min-width: calc(100% - 40px); min-height: calc(100% - 40px); display: none; border: 3px solid green; background: #1f1e1e;">
    <button onclick="document.getElementById('debug-container').style.display = 'none'" style="position: fixed; z-index: 100000; right: 20px;">Close</button>
    <button onclick="document.getElementById('iframe').contentWindow.location.reload();" style="position: fixed; z-index: 100000;">Reload</button>
    <iframe src="" height="100%" width="100%" frameborder=0 id="iframe" style="position: absolute;"></iframe>
</div>


<script>
  var input = document.getElementById("query");
  input.addEventListener("keyup", function(event) {
    event.preventDefault();
    if (event.keyCode === 13) {
      search();
    }
  });

  function debug(id) {
    document.getElementById('iframe').src = '/debugger.php/'+id;
    document.getElementById('debug-container').style.display = 'block';
  }
</script>

<?php

try {

  // Reports
  $Reports = Report::orderBy('Index_report.created_at', 'desc');
  if (isset($_GET['level']) && $_GET['level'] != '' && $_GET['level'] != 'all') {
    $Reports->where('Index_report.type', $_GET['level']);
  }

  if (isset($_GET['reporthash']) && $_GET['reporthash'] != '') {
    $Reports->join('Index_report_hash', 'Index_report_hash.index_report_id', '=', 'Index_report.id');
    $Reports->where('Index_report_hash.report_id', '=', $_GET['reporthash']);
  } else {
    if (isset($_GET['query']) && $_GET['query'] != '') {
      $Reports->where('Index_report.index_code', '=', $_GET['query']);
    }
    if (isset($_GET['fingerprint']) && $_GET['fingerprint'] != '') {
      $Reports->where('Index_report.fingerprint', '=', $_GET['fingerprint']);
    }
    if (isset($_GET['failed']) && $_GET['failed'] == 'true') {
      $Reports->where('Index_report.city_id', '=', 0);
    }
  }

  $Reports = $Reports->limit(500)->get();
  if ($Reports !== false) {
    echo '<table style="width: 100%;">
        <tr>
            <th>Date</th>
            <th>Index</th>
            <th>City id</th>
            <th>Type</th>
            <th style="width: 40%;">Debug explain</th>
            <th style="width: 30%;">Report info</th>
            <th>JSON</th>
            <th>Action</th>
        </tr>';
    /** @var Report $Report */
    foreach ($Reports as $Report) {
      echo '<tr class="' . ($Report->city_id === 0 && $_GET['failed'] !== 'true' ? 'error' : '') . '">
<td>' . $Report->created_at . '</td>
<td><a class="link" href="https://grepodata.com/indexer/' . $Report->index_code . '">' . $Report->index_code . '<a/></td>
<td>' . $Report->city_id . '</td>
<td>' . $Report->type . '</td>
<td style="width: 40%; text-align: left;">' . $Report->debug_explain . '</td>
<td style="width: 30%; text-align: left;">' . $Report->report_info . '</td>
<td><pre style="overflow: hidden;">'.$Report->report_json.'</pre></td>
<td><a class="link" onclick="debug('.(isset($Report->index_report_id)?$Report->index_report_id:$Report->id).')" target="_blank">Debug ></a></td>
</tr>';
      //      echo $Log->created_at . ' --- ' . $Log->level . ' --- <a class="link" onclick="pid(' . $Log->pid . ')">' . $Log->pid . '<a/> --- ' . $Log->message . '<br/>';
    }
    echo '</table>';
  } else {
    echo 'No reports found.';
  }
} catch (\Exception $e) {
  echo $e->getMessage();
}
//<td><a class="link" href="/debugger.php/'.(isset($Report->index_report_id)?$Report->index_report_id:$Report->id).'" target="_blank">Debug ></a></td>

?>

</body>
</html>
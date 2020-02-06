<?php

namespace Grepodata\Application\API\Http;

use Carbon\Carbon;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Elasticsearch\Client;
use Grepodata\Library\Model\CronStatus;
use Grepodata\Library\Model\Operation_log;

$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$pass = array_shift($request);

require('./../../../config.php');
require('./../config.api.php');

error_reporting(0);

session_start();

if (isset($_GET['flush'])) {
  session_unset();
  session_abort();
  session_destroy();
}
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

	.status.open:before {
		background-color: #94E185;
		border-color: #78D965;
		box-shadow: 0px 0px 4px 1px #94E185;
	}
	.status.in-progress:before {
		background-color: #FFC182;
		border-color: #FFB161;
		box-shadow: 0px 0px 4px 1px #FFC182;
	}
	.status.dead:before {
		background-color: #C9404D;
		border-color: #C42C3B;
		box-shadow: 0px 0px 4px 1px #C9404D;
	}
	.status:before {
		content: ' ';
		display: inline-block;
		width: 7px;
		height: 7px;
		margin-right: 10px;
		border: 1px solid #000;
		border-radius: 7px;
	}

	.server-health {
		min-width: 20%;
		height: 25px;
		margin: 10px;
		color: #1f1e1e;
		padding: 3px;
	}
	.server-health.green {
		background: #94e185;
	}
	.server-health.red {
		background: #c9404d;
	}
	.job:hover {
		background: #283130;
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
  function enableRefresh()
  {
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url += "&refresh=5";
    } else {
      url += "?refresh=5";
    }
    window.location.href = url;
  }
  function flush_session()
  {
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url = url.substring(0,url.indexOf("?"));
    }
    url += "?flush=1";
    window.location.href = url;
  }
  function search()
  {
    var query = document.getElementById('query').value;
    var level = document.getElementById('level').value;
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url += "&query="+query+"&level="+level;
    } else {
      url += "?query="+query+"&level="+level;
    }
    window.location.href = url;
  }
  function searchJob(query)
  {
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url = url.substring(0,url.indexOf("?"));
    }
    url += "?query="+query;
    window.location.href = url;
  }
  function pid(pid)
  {
    var url = window.location.href;
    if(url.indexOf("?") > 0) {
      url = url.substring(0,url.indexOf("?"));
      url += "?pid="+pid;
      //url += "&pid="+pid;
    } else {
      url += "?pid="+pid;
    }
    window.location.href = url;
  }
</script>

<?php

// Jobs
try {
  $Jobs = CronStatus::query();
  $Jobs->orderBy('path', 'asc');
  $aJobs = $Jobs->get();
} catch (\Exception $e) {
  $aJobs = array();
}

// Server health
echo '<p>VPS2: ';
if (!stristr(PHP_OS, "win")) {
  $path = realpath($_SERVER['DOCUMENT_ROOT']);
  $version = substr($path, strlen('/home/vps/gd-stats-api/dist_'), strpos($path, '/Software/Application/') - strlen('/home/vps/gd-stats-api/dist_'));
  $load = shell_exec("uptime");
  echo $version . ' ' . $load . '   ';
} else {
  echo Carbon::now().'   ';
}
if (!isset($_SESSION['es_html'])) {
  try {
    $client = Client::GetInstance(5);
    $es_health = $client->cat()->health();
  } catch (\Exception $e) {
    $es_health = false;
  }

  if (isset($es_health[0]) && isset($es_health[0]['status']) && $es_health[0]['status'] == 'green') {
    $es_html = '<span class="server-health green">Elasticsearch: OK</span>';
    $_SESSION['es_html'] = $es_html;
    echo $es_html;
  } else {
    $es_html = '<span class="server-health red">Elasticsearch: Offline</span>';
    $_SESSION['es_html'] = $es_html;
    echo $es_html;
  }
} else {
  echo $_SESSION['es_html'];
}
if (!isset($_SESSION['sql_html'])) {

  if (sizeof($aJobs) > 0) {
    $sql_html = '<span class="server-health green">SQL server: OK</span>';
    $_SESSION['sql_html'] = $sql_html;
    echo $sql_html;
  } else {
    $sql_html = '<span class="server-health red">SQL server: Offline</span>';
    $_SESSION['sql_html'] = $sql_html;
    $bAbort = true;
    echo $sql_html;
  }
} else {
  echo $_SESSION['sql_html'];
}
if (!isset($_SESSION['inno_html'])) {
  if (InnoData::testEndpoint('nl73')) {
    $inno_html = '<span class="server-health green">INNO API: OK</span>';
    $_SESSION['inno_html'] = $inno_html;
    echo $inno_html;
  } else {
    $inno_html = '<span class="server-health red">INNO API: Unreachable</span>';
    $_SESSION['inno_html'] = $inno_html;
    echo $inno_html;
  }
} else {
  echo $_SESSION['inno_html'];
}
echo '<a class="link" onclick="flush_session()">Refresh</a></p>';

if (isset($bAbort) && $bAbort == true) {
  echo '</br></br><table><tr class="error"><th>CRITICAL: SQL server is down</th></tr></table>';
  die();
}

if ($aJobs !== false) {
  echo '<table style="width: 100%;">
    <tr>
        <th>Path</th>
        <th>Running</th>
        <th>Active</th>
        <th>Duration</th>
        <th>Last run start</th>
        <th>Last run end</th>
    </tr>';
  /** @var CronStatus $Job */
  foreach ($aJobs as $Job) {
    if ($Job->running!=1) {
      $to = Carbon::createFromFormat('Y-m-d H:i:s', $Job->last_run_ended);
      $from = Carbon::createFromFormat('Y-m-d H:i:s', $Job->last_run_started);
      $diff_in_minutes = $to->diffInSeconds($from);
      $Difftime = floor($diff_in_minutes/60) . 'm' . $diff_in_minutes%60 . 's';
    } else {
      $from = Carbon::createFromFormat('Y-m-d H:i:s', $Job->last_run_started);
      $diff_in_minutes = Carbon::now()->diffInSeconds($from);
      $Difftime = ''.floor($diff_in_minutes/60) . 'm' . $diff_in_minutes%60 . 's +';
    }
    $path = strrev($Job->path);
    $pathname = substr($path, 4, strpos($path, '_')-4);
    $pathname = strrev($pathname);
    $rowHtml = '<tr class="job"><td style="width: 30%; text-align: left;" class="link" onclick="searchJob(';
    $rowHtml .= "'".$pathname."'";
    $rowHtml .= ')">' . $Job->path . '</td>' . ($Job->running==1? '<td class="status open"></td>' : '<td class="status dead"></td>') . ($Job->active==1? '<td class="status open"></td>' : '<td class="status dead"></td>') . '<td>' . $Difftime . '</td><td>' . $Job->last_run_started . '</td><td>' . $Job->last_run_ended . '</td></tr>';
    echo $rowHtml;
  }
  echo '</table><br/>';
}

?>


<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
  google.charts.load("current", {packages:['controls', 'corechart', "timeline"]});
  google.charts.setOnLoadCallback(drawChart);
  function drawChart() {
    var container = document.getElementById('dashboard_div');
    var dashboard = new google.visualization.Dashboard(container);
    var dataTable = new google.visualization.DataTable();

    var dateRangeSlider = new google.visualization.ControlWrapper({
      'controlType': 'NumberRangeFilter',
      'containerId': 'filter_div',
      'options': {
        'filterColumnLabel': 'Filter',
        // 'ui': {
        //   // format: 'dd-MM-yyyy hh:mm',
        //   // ticks: 10
        // }
      }
    });

    var chart = new google.visualization.ChartWrapper({
      'chartType': 'Timeline',
      'containerId': 'chart_div',
      options: {
        hAxis: {
          format: 'HH:mm'
        }
      }
    });

    dataTable.addColumn({ type: 'string', id: 'Script' });
    dataTable.addColumn({ type: 'string', role: 'annotation' });
    dataTable.addColumn({ type: 'number', role: 'annotation', label: 'Filter' });
    dataTable.addColumn({ type: 'date', id: 'Start', label: 'Start' });
    dataTable.addColumn({ type: 'date', id: 'End' });

    dataTable.addRows([
      <?php
      try {
        $Scripts = \Grepodata\Library\Model\Operation_scriptlog::query();
        $Scripts->where('script', 'not like', '%builder.php%', 'and');
        $Scripts->where('script', 'not like', '%mailer.php%');
        $Scripts->orderBy('id', 'desc');
        $Scripts->limit(500);
        $Scripts = $Scripts->get();
        $Data = "";
        foreach ($Scripts as $script) {
          $Data .= "[ '".substr($script->script, strrpos($script->script, '/'))."',
            '".$script->pid."',
            ".$script->id.",
            new Date(".(Carbon::parse($script->start)->timestamp*1000)."),
            new Date(".(Carbon::parse($script->end)->timestamp*1000).")
            ],";
        }
        echo $Data;
      } catch (\Exception $e) {
        echo "[ '1', 'George Washington', new Date(1789, 3, 30), new Date(1797, 2, 4) ],[ '2', 'John Adams',        new Date(1797, 2, 4),  new Date(1801, 2, 4) ],[ '3', 'Thomas Jefferson',  new Date(1801, 2, 4),  new Date(1809, 2, 4) ]";
      }
      ?>
    ]);

    // chart.draw(dataTable);

    dashboard.bind(dateRangeSlider, chart);
    dashboard.draw(dataTable);

    google.visualization.events.addListener(chart, 'select', selectHandler);

    function selectHandler() {
      var selection = dashboard.getSelection();
      if (selection.length > 0) {
        pid(dataTable.getValue(selection[0].row, 1));
      }
    }
  }
</script>

<div id="dashboard_div">
	<!--Divs that will hold each control and chart-->
	<div id="filter_div"></div>
	<div id="chart_div" style="height: 350px;"></div>
</div>


<input id="query" type="text" name="text" autocomplete="off" onsubmit="search()" value="<?php echo $_GET['query'];?>"/>
<!--<button onclick="search()">Go</button><br/><br/>-->

<select id="level" name="level">
	<option value="0" <?php echo isset($_GET['level']) && $_GET['level'] == 0 ? 'selected':''?>>All</option>
	<option value="1" <?php echo isset($_GET['level']) && $_GET['level'] == 1 ? 'selected':''?>>1 - Error</option>
	<option value="2" <?php echo isset($_GET['level']) && $_GET['level'] == 2 ? 'selected':''?>>2 - Warning</option>
	<option value="3" <?php echo isset($_GET['level']) && $_GET['level'] == 3 ? 'selected':''?>>3 - Info</option>
	<option value="4" <?php echo isset($_GET['level']) && $_GET['level'] == 4 ? 'selected':''?>>4 - Silly</option>
</select>
<button onclick="search()">Go</button>

&nbsp;&nbsp;&nbsp;<a class="link" onclick="enableRefresh()">Enable auto refresh</a>
&nbsp;&nbsp;&nbsp;<a onclick="clearFilters()" class="link">Clear filters</a>
&nbsp;&nbsp;&nbsp;?date=<?php echo $_GET['date'];?>;
<br/><br/>

<script>
  var input = document.getElementById("query");
  input.addEventListener("keyup", function(event) {
    event.preventDefault();
    if (event.keyCode === 13) {
      search();
    }
  });
</script>

<?php

// Logs
$Logs = Operation_log::orderBy('created_at', 'desc')->orderBy('microtime', 'desc');
if (isset($_GET['level']) && $_GET['level'] != '' && $_GET['level'] != 0) {
  $Logs->where('level', $_GET['level']);
}
if (isset($_GET['pid']) && $_GET['pid'] != '') {
  $Logs->where('pid', $_GET['pid']);
}
if (isset($_GET['query']) && $_GET['query'] != '') {
  $Logs->where('message', 'LIKE', '%'.$_GET['query'].'%');
}
if (isset($_GET['date']) && $_GET['date'] != '') {
  $Logs->whereDate('created_at', '=', date('Y-m-d', strtotime($_GET['date'])));
}

$Logs = $Logs->limit(500)->get();
if ($Logs !== false) {
  echo '<table style="width: 100%;">
    <tr>
        <th style="width: 15%;">Date</th>
        <th>Level</th>
        <th>Pid</th>
        <th style="width: 70%;">Message</th>
    </tr>';
  /** @var Operation_log $Log */
  foreach ($Logs as $Log) {
    $msg = preg_replace(
      '#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i',
      "'<a style='color:whitesmoke;' href=\"$1\" target=\"_blank\">$3</a>$4'",
      $Log->message
    );
    echo '<tr class="'.($Log->level <= 2 && (!isset($_GET['level']) || ($_GET['level'] != 1 && $_GET['level'] != 2)) ? ($Log->level == 1 ? 'error' : 'warning') : '').'"><td style="width: 15%;">' . $Log->created_at . '</td><td>' . $Log->level . '</td><td><a class="link" href="?pid=' . $Log->pid . '">' . $Log->pid . '<a/></td><td style="width: 70%; text-align: left;">' . $msg . '</td></tr>';
//      echo $Log->created_at . ' --- ' . $Log->level . ' --- <a class="link" onclick="pid(' . $Log->pid . ')">' . $Log->pid . '<a/> --- ' . $Log->message . '<br/>';
  }
  echo '</table>';
}

if (isset($_GET['refresh']) && $_GET['refresh'] >= 1) {
  echo "
    <script>
        setTimeout(function() { location.reload(); }, " . ($_GET['refresh'] * 1000) . ");
    </script>";
}

?>

</body>
</html>
<?php

namespace Grepodata\Application\API\Http;

use Carbon\Carbon;
use Grepodata\Library\Controller\Indexer\IndexInfo;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Elasticsearch\Client;
use Grepodata\Library\Model\CronStatus;
use Grepodata\Library\Model\Operation_log;
use Grepodata\Library\Model\Operation_scriptlog;
use Grepodata\Library\Redis\RedisHelper;
use Redis;

require('./../../../config.php');
require('./../config.api.php');

error_reporting(0);

// Fail fast on unreachable services (default is 60s per call)
ini_set('default_socket_timeout', 3);

$PageStart = microtime(true);

const MON_HEALTH_TTL = 30;
const MON_LOG_LIMIT = 500;
const MON_SCRIPT_LIMIT = 1000;
const MON_CMD_KEY_LIMIT = 200;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function h($v)
{
  return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a link to this page, based on the current query string. */
function mon_url(array $set = array(), array $drop = array(), $reset = false)
{
  $q = $reset ? array_intersect_key($_GET, array('refresh' => 1)) : $_GET;
  foreach ($drop as $k) {
    unset($q[$k]);
  }
  foreach ($set as $k => $v) {
    $q[$k] = $v;
  }
  $q = array_filter($q, function ($v) {
    return $v !== '' && $v !== null;
  });
  $path = strtok($_SERVER['REQUEST_URI'], '?');
  return $path . ($q ? '?' . http_build_query($q) : '');
}

function mon_param($key)
{
  return (isset($_GET[$key]) && is_string($_GET[$key])) ? trim($_GET[$key]) : '';
}

function mon_ago($ts)
{
  if (!$ts) return '-';
  $d = max(0, time() - $ts);
  if ($d < 60) return $d . 's ago';
  if ($d < 3600) return floor($d / 60) . 'm ago';
  if ($d < 86400) return floor($d / 3600) . 'h ago';
  return floor($d / 86400) . 'd ago';
}

function mon_duration($seconds)
{
  $s = max(0, (int)$seconds);
  if ($s >= 3600) return floor($s / 3600) . 'h ' . floor(($s % 3600) / 60) . 'm';
  return floor($s / 60) . 'm ' . ($s % 60) . 's';
}

function mon_ms($t)
{
  return (int)round((microtime(true) - $t) * 1000);
}

/** Escape a log message and turn http(s) urls into links. */
function mon_format_message($msg)
{
  $msg = str_replace('\n', "\n", (string)$msg);
  $parts = preg_split('#(https?://[^\s<>"\'()\[\]{}]+)#i', $msg, -1, PREG_SPLIT_DELIM_CAPTURE);
  $out = '';
  foreach ($parts as $i => $part) {
    if ($i % 2 === 1) {
      $trail = '';
      while ($part !== '' && strpos('.,;:', substr($part, -1)) !== false) {
        $trail = substr($part, -1) . $trail;
        $part = substr($part, 0, -1);
      }
      $out .= '<a href="' . h($part) . '" target="_blank" rel="noopener noreferrer">'
        . h(preg_replace('#^https?://#i', '', $part)) . '</a>' . h($trail);
    } else {
      $out .= h($part);
    }
  }
  return $out;
}

function mon_level($level)
{
  switch ((int)$level) {
    case 1: return array('Error', 'down');
    case 2: return array('Warning', 'warn');
    case 3: return array('Info', 'info');
    case 4: return array('Silly', 'muted');
    case 10: return array('Indexer', 'purple');
    case 100: return array('V2 migration', 'muted');
    default: return array((string)$level, 'muted');
  }
}

/** Run all external service checks. Slow, so the result is cached in the session. */
function mon_collect_health()
{
  $out = array('ts' => time(), 'checks' => array(), 'redis_info' => null, 'cmd_keys' => array(), 'cmd_keys_capped' => false);

  // Elasticsearch
  $t = microtime(true);
  try {
    $es = Client::GetInstance(1)->cat()->health();
    $status = isset($es[0]['status']) ? $es[0]['status'] : null;
    $state = $status === 'green' ? 'ok' : ($status === 'yellow' ? 'warn' : 'down');
    $detail = $status ? 'Cluster ' . $status : 'No response';
  } catch (\Throwable $e) {
    $state = 'down';
    $detail = 'Unreachable';
  }
  $out['checks']['es'] = array('label' => 'Elasticsearch', 'state' => $state, 'detail' => $detail, 'ms' => mon_ms($t));

  // Redis (+ info and operation state keys)
  $t = microtime(true);
  try {
    $redis = new Redis();
    $redis->connect(REDIS_HOST, REDIS_PORT, 2);
    $redis->ping();
    $out['checks']['redis'] = array('label' => 'Redis', 'state' => 'ok', 'detail' => REDIS_HOST . ':' . REDIS_PORT, 'ms' => mon_ms($t));

    $out['redis_info'] = $redis->info();

    // SCAN instead of KEYS: KEYS blocks the whole Redis server
    $iter = null;
    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
    while (($keys = $redis->scan($iter, 'cmd_state_*', 500)) !== false) {
      foreach ($keys as $key) {
        if (count($out['cmd_keys']) >= MON_CMD_KEY_LIMIT) {
          $out['cmd_keys_capped'] = true;
          break 2;
        }
        $parts = explode('_', $key);
        $team = end($parts);
        $name = $team;
        try {
          $IndexInfo = IndexInfo::first($team);
          if ($IndexInfo) $name = $IndexInfo->index_name . ' (' . $IndexInfo->world . ')';
        } catch (\Throwable $e) {
        }
        $out['cmd_keys'][] = array('key' => $key, 'index' => $name, 'state' => (string)$redis->get($key));
      }
    }
  } catch (\Throwable $e) {
    $out['checks']['redis'] = array('label' => 'Redis', 'state' => 'down', 'detail' => 'Offline', 'ms' => mon_ms($t));
  }

  // WebSocket (listens on the redis backbone)
  $t = microtime(true);
  try {
    $Num = RedisHelper::SendBackboneHeartbeat();
    $out['checks']['wss'] = array('label' => 'WebSocket', 'state' => $Num > 0 ? 'ok' : 'down', 'detail' => $Num > 0 ? $Num . ' listener(s)' : 'No listeners', 'ms' => mon_ms($t));
  } catch (\Throwable $e) {
    $out['checks']['wss'] = array('label' => 'WebSocket', 'state' => 'down', 'detail' => 'Offline', 'ms' => mon_ms($t));
  }

  // INNO api
  $t = microtime(true);
  $bInno = InnoData::testEndpoint('nl16');
  $out['checks']['inno'] = array('label' => 'INNO API', 'state' => $bInno ? 'ok' : 'down', 'detail' => $bInno ? 'Reachable' : 'Unreachable', 'ms' => mon_ms($t));

  return $out;
}

// ---------------------------------------------------------------------------
// Request handling
// ---------------------------------------------------------------------------

session_start();

if (isset($_GET['flush'])) {
  $_SESSION = array();
  session_write_close();
  header('Location: ' . mon_url(array(), array('flush')));
  exit;
}

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $flash = array('down', 'Invalid request token');
  if (isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    try {
      $op = isset($_POST['op']) ? $_POST['op'] : '';
      if ($op === 'heartbeat') {
        $Num = RedisHelper::SendBackboneHeartbeat();
        $flash = array($Num > 0 ? 'ok' : 'warn', 'Heartbeat received by ' . $Num . ' listener(s)');
      } else {
        if ($op === 'test') {
          $Form = array(
            'type' => 'notify_team',
            'team' => '1z7ay7s5',
            'msg' => 'JohnDoe uploaded 10 commands to team 1z7ay7s5',
            'world' => 'nl108',
            'action' => 'ops_event'
          );
        } else {
          $Form = array();
          foreach (array('type', 'team', 'msg', 'world', 'action') as $f) {
            $Form[$f] = isset($_POST['pub_' . $f]) && is_string($_POST['pub_' . $f]) ? trim($_POST['pub_' . $f]) : '';
          }
        }
        $_SESSION['bb_form'] = $Form;
        if (in_array('', $Form, true) || !in_array($Form['type'], array('notify_team', 'notify_user'), true) || $Form['action'] !== 'ops_event') {
          $flash = array('down', 'All fields are required');
        } else {
          $Num = RedisHelper::SendBackboneMessage(json_encode($Form));
          $flash = array($Num > 0 ? 'ok' : 'warn', 'Message delivered to ' . $Num . ' receiver(s)');
        }
      }
    } catch (\Throwable $e) {
      $flash = array('down', 'Publish failed: ' . $e->getMessage());
    }
  }
  $_SESSION['flash'] = $flash;
  session_write_close();
  header('Location: ' . mon_url() . '#redis');
  exit;
}

$Flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
unset($_SESSION['flash']);
$BbForm = isset($_SESSION['bb_form']) ? $_SESSION['bb_form'] : array('type' => 'notify_team', 'team' => '', 'msg' => '', 'world' => '', 'action' => 'ops_event');
$Csrf = $_SESSION['csrf'];

// Health checks are cached for a short time so that auto refresh stays cheap
if (!isset($_SESSION['health']['ts']) || (time() - $_SESSION['health']['ts']) >= MON_HEALTH_TTL) {
  $_SESSION['health'] = mon_collect_health();
}
$Health = $_SESSION['health'];

// Release the session lock so parallel requests are not blocked
session_write_close();

// ---------------------------------------------------------------------------
// Data
// ---------------------------------------------------------------------------

// SQL + cron jobs
$t = microtime(true);
$SqlOk = true;
$aJobs = array();
try {
  $aJobs = CronStatus::query()->orderBy('path', 'asc')->get();
} catch (\Throwable $e) {
  $SqlOk = false;
}
$Checks = array('sql' => array('label' => 'SQL server', 'state' => $SqlOk ? 'ok' : 'down', 'detail' => $SqlOk ? count($aJobs) . ' cron jobs' : 'Offline', 'ms' => mon_ms($t)));
$Checks += $Health['checks'];

$NumDown = 0;
$NumWarn = 0;
$aAffected = array();
foreach ($Checks as $c) {
  if ($c['state'] === 'down') {
    $NumDown++;
    $aAffected[] = $c['label'];
  } elseif ($c['state'] === 'warn') {
    $NumWarn++;
    $aAffected[] = $c['label'];
  }
}

$Jobs = array();
$NumRunning = 0;
$NumInactive = 0;
$Now = time();
foreach ($aJobs as $Job) {
  $Start = $Job->last_run_started ? strtotime($Job->last_run_started) : false;
  $End = $Job->last_run_ended ? strtotime($Job->last_run_ended) : false;
  $bRunning = $Job->running == 1;
  $bActive = $Job->active == 1;
  if ($bRunning) {
    $NumRunning++;
    $Duration = $Start ? mon_duration($Now - $Start) . ' +' : '-';
  } else {
    $Duration = ($Start && $End) ? mon_duration($End - $Start) : '-';
  }
  if (!$bActive) $NumInactive++;

  // Short name used to filter the logs: the last "_" segment of the file name
  $File = basename(str_replace('\\', '/', $Job->path));
  $Name = preg_replace('#\.php$#', '', $File);
  $Pos = strrpos($Name, '_');
  if ($Pos !== false) $Name = substr($Name, $Pos + 1);

  $Jobs[] = array(
    'path' => $Job->path,
    'file' => $File,
    'name' => $Name,
    'running' => $bRunning,
    'active' => $bActive,
    'duration' => $Duration,
    'started' => $Job->last_run_started,
    'started_ago' => mon_ago($Start),
    'ended' => $Job->last_run_ended,
  );
}

// Logs
$Query = mon_param('query');
$Level = (int)mon_param('level');
$Pid = mon_param('pid');
$DateRaw = mon_param('date');
$Day = ($DateRaw !== '' && strtotime($DateRaw) !== false) ? date('Y-m-d', strtotime($DateRaw)) : '';

$Logs = array();
$LogError = false;
$LevelCounts = array();
if ($SqlOk) {
  try {
    // Order by the auto-increment PK (same order as created_at/microtime) so no filesort over the whole table is needed
    $Q = Operation_log::orderBy('id', 'desc');
    if ($Level != 0) {
      $Q->where('level', $Level);
    } else {
      $Q->where('level', '!=', 10);
    }
    if ($Pid !== '' && ctype_digit($Pid)) {
      $Q->where('pid', $Pid);
    }
    if ($Query !== '') {
      $Q->where('message', 'LIKE', '%' . $Query . '%');
    }
    if ($Day !== '') {
      // Range instead of whereDate() so an index on created_at can be used
      $Q->whereBetween('created_at', array($Day . ' 00:00:00', $Day . ' 23:59:59'));
    }
    $Logs = $Q->limit(MON_LOG_LIMIT)->get();
    foreach ($Logs as $Log) {
      $LevelCounts[(int)$Log->level] = (isset($LevelCounts[(int)$Log->level]) ? $LevelCounts[(int)$Log->level] : 0) + 1;
    }
  } catch (\Throwable $e) {
    $LogError = true;
  }
}

// Script timeline
$Runs = array();
if ($SqlOk) {
  try {
    $Scripts = Operation_scriptlog::query()
      ->where('script', 'not like', '%builder.php%')
      ->where('script', 'not like', '%mailer.php%')
      ->orderBy('id', 'desc')
      ->limit(MON_SCRIPT_LIMIT)
      ->get();
    foreach ($Scripts as $s) {
      $Start = $s->start ? strtotime($s->start) : false;
      if (!$Start) continue;
      $End = $s->end ? strtotime($s->end) : $Now;
      $Runs[] = array(basename(str_replace('\\', '/', $s->script)), (string)$s->pid, $Start * 1000, max($End, $Start) * 1000);
    }
  } catch (\Throwable $e) {
  }
}

// Redis summary
$RedisInfo = $Health['redis_info'];
$RedisStats = array();
if (is_array($RedisInfo)) {
  $Hits = isset($RedisInfo['keyspace_hits']) ? (int)$RedisInfo['keyspace_hits'] : 0;
  $Misses = isset($RedisInfo['keyspace_misses']) ? (int)$RedisInfo['keyspace_misses'] : 0;
  $RedisStats = array(
    'Version' => isset($RedisInfo['redis_version']) ? $RedisInfo['redis_version'] : '-',
    'Uptime' => (isset($RedisInfo['uptime_in_days']) ? $RedisInfo['uptime_in_days'] : '-') . ' days',
    'Clients' => isset($RedisInfo['connected_clients']) ? $RedisInfo['connected_clients'] : '-',
    'Memory' => isset($RedisInfo['used_memory_human']) ? $RedisInfo['used_memory_human'] : '-',
    'Peak memory' => isset($RedisInfo['used_memory_peak_human']) ? $RedisInfo['used_memory_peak_human'] : '-',
    'Ops/sec' => isset($RedisInfo['instantaneous_ops_per_sec']) ? $RedisInfo['instantaneous_ops_per_sec'] : '-',
    'Commands' => isset($RedisInfo['total_commands_processed']) ? number_format((int)$RedisInfo['total_commands_processed']) : '-',
    'Hit rate' => ($Hits + $Misses) > 0 ? round($Hits / ($Hits + $Misses) * 100, 1) . '%' : '-',
  );
}

// Server info
$Host = gethostname();
$Version = '';
$DocRoot = realpath($_SERVER['DOCUMENT_ROOT']);
if ($DocRoot && preg_match('#dist_([^/\\\\]+)#', $DocRoot, $m)) {
  $Version = $m[1];
}
$Load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;

$Refresh = (int)mon_param('refresh');
if ($Refresh > 0) $Refresh = max(3, $Refresh);

$JsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $NumDown > 0 ? '(' . $NumDown . ' down) ' : '' ?>Grepodata monitor</title>
<style>
  :root {
    --bg: #0b0f17;
    --panel: #121927;
    --panel-2: #172033;
    --border: #232f47;
    --text: #d7e0ee;
    --muted: #8393ad;
    --accent: #7c9cff;
    --ok: #34d399;
    --warn: #fbbf24;
    --down: #f87171;
    --info: #60a5fa;
    --purple: #c084fc;
    --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
  }
  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; scroll-padding-top: 70px; }
  body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  }
  a { color: var(--accent); text-decoration: none; }
  a:hover { text-decoration: underline; }

  /* top bar */
  .topbar {
    position: sticky; top: 0; z-index: 10;
    display: flex; flex-wrap: wrap; align-items: center; gap: 12px 24px;
    padding: 10px 24px;
    background: rgba(11, 15, 23, .92);
    backdrop-filter: blur(8px);
    border-bottom: 1px solid var(--border);
  }
  .brand { font-weight: 700; font-size: 16px; letter-spacing: .02em; color: var(--text); }
  .brand span { color: var(--accent); }
  .nav { display: flex; gap: 4px; flex: 1; }
  .nav a { color: var(--muted); padding: 4px 10px; border-radius: 6px; }
  .nav a:hover { color: var(--text); background: var(--panel); text-decoration: none; }
  .tools { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 12px; }

  main { max-width: 1500px; margin: 0 auto; padding: 24px; display: flex; flex-direction: column; gap: 28px; }
  section > h2 {
    margin: 0 0 12px; font-size: 12px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .1em; color: var(--muted);
    display: flex; align-items: center; gap: 12px;
  }
  section > h2 .grow { flex: 1; }
  .card {
    background: var(--panel); border: 1px solid var(--border); border-radius: 10px;
  }
  .card-pad { padding: 16px; }
  .empty { padding: 28px; text-align: center; color: var(--muted); }

  /* controls */
  input, select, button, .btn {
    font: inherit; color: var(--text);
    background: var(--panel-2); border: 1px solid var(--border);
    border-radius: 6px; padding: 6px 10px;
  }
  input:focus, select:focus, button:focus-visible, .btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 1px; }
  button, .btn { cursor: pointer; display: inline-block; }
  button:hover, .btn:hover { border-color: var(--accent); text-decoration: none; }
  button.primary { background: var(--accent); border-color: var(--accent); color: #0b0f17; font-weight: 600; }
  .seg { display: inline-flex; }
  .seg button { border-radius: 0; margin-left: -1px; padding: 3px 10px; font-size: 12px; }
  .seg button:first-child { border-radius: 6px 0 0 6px; margin-left: 0; }
  .seg button:last-child { border-radius: 0 6px 6px 0; }
  .seg button.on { background: var(--accent); border-color: var(--accent); color: #0b0f17; font-weight: 600; }

  /* status */
  .banner {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; border-radius: 10px; border: 1px solid; font-weight: 600;
  }
  .banner.ok { background: rgba(52, 211, 153, .08); border-color: rgba(52, 211, 153, .35); color: var(--ok); }
  .banner.warn { background: rgba(251, 191, 36, .08); border-color: rgba(251, 191, 36, .35); color: var(--warn); }
  .banner.down { background: rgba(248, 113, 113, .1); border-color: rgba(248, 113, 113, .4); color: var(--down); }
  .banner small { font-weight: 400; color: var(--muted); margin-left: auto; }
  .dot { width: 10px; height: 10px; border-radius: 50%; background: currentColor; box-shadow: 0 0 8px currentColor; flex: none; }
  .ok .dot, .dot.ok { color: var(--ok); }
  .warn .dot, .dot.warn { color: var(--warn); }
  .down .dot, .dot.down { color: var(--down); }
  .dot.pulse { animation: pulse 1.4s ease-in-out infinite; }
  @keyframes pulse { 50% { opacity: .35; } }

  .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
  .health { padding: 14px 16px; display: flex; flex-direction: column; gap: 6px; }
  .health .row { display: flex; align-items: center; gap: 10px; }
  .health .name { font-weight: 600; flex: 1; }
  .health .detail { color: var(--muted); font-size: 12px; }
  .health.state-warn { border-color: rgba(251, 191, 36, .5); }
  .health.state-down { border-color: rgba(248, 113, 113, .6); }

  .stat { padding: 14px 16px; }
  .stat .v { font-size: 24px; font-weight: 700; line-height: 1.2; }
  .stat .l { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
  .stat.red .v { color: var(--down); }
  .stat.amber .v { color: var(--warn); }

  .pill {
    display: inline-block; padding: 1px 9px; border-radius: 999px; font-size: 11px; font-weight: 600;
    border: 1px solid; white-space: nowrap;
  }
  .pill.ok { color: var(--ok); border-color: rgba(52, 211, 153, .4); background: rgba(52, 211, 153, .1); }
  .pill.warn { color: var(--warn); border-color: rgba(251, 191, 36, .4); background: rgba(251, 191, 36, .1); }
  .pill.down { color: var(--down); border-color: rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .1); }
  .pill.info { color: var(--info); border-color: rgba(96, 165, 250, .4); background: rgba(96, 165, 250, .1); }
  .pill.purple { color: var(--purple); border-color: rgba(192, 132, 252, .4); background: rgba(192, 132, 252, .1); }
  .pill.muted { color: var(--muted); border-color: var(--border); background: transparent; }

  /* tables */
  .table-wrap { overflow: auto; max-height: 70vh; border-radius: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 8px 14px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--border); }
  thead th {
    position: sticky; top: 0; z-index: 1; background: var(--panel-2);
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--muted);
  }
  tbody tr:last-child td { border-bottom: 0; }
  tbody tr:hover { background: rgba(124, 156, 255, .05); }
  tr.row-error { background: rgba(248, 113, 113, .09); box-shadow: inset 3px 0 0 var(--down); }
  tr.row-warn { background: rgba(251, 191, 36, .07); box-shadow: inset 3px 0 0 var(--warn); }
  td.mono, .mono { font-family: var(--mono); font-size: 12px; }
  td.msg { font-family: var(--mono); font-size: 12px; white-space: pre-wrap; word-break: break-word; width: 100%; }
  td.msg a { color: #a9bcff; }
  td.nowrap, th.nowrap { white-space: nowrap; }
  .muted { color: var(--muted); }
  .job-file { font-weight: 600; }

  /* filter form */
  .filters { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px; border-bottom: 1px solid var(--border); }
  .filters input[type=search] { flex: 1; min-width: 220px; }
  .filters input[name=pid] { width: 110px; }

  #timeline { width: 100%; }
  .timeline-note { padding: 8px 16px; font-size: 12px; color: var(--muted); border-top: 1px solid var(--border); }

  .split { display: grid; gap: 12px; grid-template-columns: minmax(280px, 1fr) minmax(0, 2fr); align-items: start; }
  @media (max-width: 900px) { .split { grid-template-columns: 1fr; } }
  .form { display: flex; flex-direction: column; gap: 10px; }
  .form label { display: flex; flex-direction: column; gap: 3px; font-size: 12px; color: var(--muted); }
  .form .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
  .stats { display: grid; gap: 1px; background: var(--border); grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); border-radius: 10px; overflow: hidden; }
  .stats > div { background: var(--panel); padding: 12px 14px; }
  .stats .l { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
  details { padding: 12px 16px; }
  summary { cursor: pointer; color: var(--muted); }
  pre { margin: 10px 0 0; max-height: 400px; overflow: auto; font: 12px/1.5 var(--mono); color: var(--muted); }

  .toast { padding: 10px 16px; border-radius: 8px; border: 1px solid; font-weight: 600; }
  .toast.ok { color: var(--ok); border-color: rgba(52, 211, 153, .4); background: rgba(52, 211, 153, .08); }
  .toast.warn { color: var(--warn); border-color: rgba(251, 191, 36, .4); background: rgba(251, 191, 36, .08); }
  .toast.down { color: var(--down); border-color: rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .08); }
  footer { text-align: center; color: var(--muted); font-size: 12px; padding: 0 0 24px; }
</style>
</head>
<body>

<header class="topbar">
  <div class="brand">grepodata <span>monitor</span></div>
  <nav class="nav">
    <a href="#overview">Overview</a>
    <a href="#jobs">Cron jobs</a>
    <a href="#timeline-section">Timeline</a>
    <a href="#logs">Logs</a>
    <a href="#redis">Redis</a>
  </nav>
  <div class="tools">
    <span><?= h($Host) ?><?= $Version !== '' ? ' &middot; ' . h($Version) : '' ?></span>
    <span title="<?= h(Carbon::now()) ?>"><?= h(date('H:i:s')) ?></span>
    <label>Auto refresh
      <select id="refresh" aria-label="Auto refresh interval">
        <?php foreach (array(0 => 'Off', 5 => '5s', 10 => '10s', 30 => '30s', 60 => '1m') as $sec => $label): ?>
          <option value="<?= $sec ?>" <?= $Refresh == $sec ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <a class="btn" href="<?= h(mon_url(array('flush' => 1))) ?>" title="Services are re-checked at most every <?= MON_HEALTH_TTL ?>s">Re-check services</a>
  </div>
</header>

<main>

<?php if ($Flash): ?>
  <div class="toast <?= h($Flash[0]) ?>"><?= h($Flash[1]) ?></div>
<?php endif; ?>

<section id="overview">
  <h2>Overview <span class="grow"></span><span style="text-transform:none;letter-spacing:0;font-weight:400">services checked <?= h(mon_ago($Health['ts'])) ?></span></h2>

  <?php if ($NumDown > 0): ?>
    <div class="banner down"><span class="dot pulse"></span><?= $NumDown ?> service<?= $NumDown > 1 ? 's' : '' ?> down<small><?= h(implode(', ', $aAffected)) ?></small></div>
  <?php elseif ($NumWarn > 0): ?>
    <div class="banner warn"><span class="dot"></span>Degraded<small><?= h(implode(', ', $aAffected)) ?></small></div>
  <?php else: ?>
    <div class="banner ok"><span class="dot"></span>All systems operational</div>
  <?php endif; ?>

  <?php if (!$SqlOk): ?>
    <div class="banner down" style="margin-top:12px">CRITICAL: SQL server is down. Cron jobs and logs are unavailable.</div>
  <?php endif; ?>

  <div class="grid" style="margin-top:12px">
    <?php foreach ($Checks as $c): ?>
      <div class="card health state-<?= h($c['state']) ?>">
        <div class="row">
          <span class="dot <?= h($c['state']) ?>"></span>
          <span class="name"><?= h($c['label']) ?></span>
          <span class="pill <?= h($c['state']) ?>"><?= $c['state'] === 'ok' ? 'Operational' : ($c['state'] === 'warn' ? 'Degraded' : 'Down') ?></span>
        </div>
        <div class="detail"><?= h($c['detail']) ?> &middot; <?= (int)$c['ms'] ?> ms</div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid" style="margin-top:12px">
    <div class="card stat"><div class="v"><?= $NumRunning ?><span class="muted" style="font-size:14px"> / <?= count($Jobs) ?></span></div><div class="l">Cron jobs running</div></div>
    <div class="card stat <?= $NumInactive > 0 ? 'amber' : '' ?>"><div class="v"><?= $NumInactive ?></div><div class="l">Cron jobs inactive</div></div>
    <div class="card stat <?= !empty($LevelCounts[1]) ? 'red' : '' ?>"><div class="v"><?= isset($LevelCounts[1]) ? $LevelCounts[1] : 0 ?></div><div class="l">Errors in view</div></div>
    <div class="card stat <?= !empty($LevelCounts[2]) ? 'amber' : '' ?>"><div class="v"><?= isset($LevelCounts[2]) ? $LevelCounts[2] : 0 ?></div><div class="l">Warnings in view</div></div>
    <?php if ($Load): ?>
      <div class="card stat"><div class="v"><?= h(number_format($Load[0], 2)) ?></div><div class="l">Load (<?= h(number_format($Load[1], 2)) ?> / <?= h(number_format($Load[2], 2)) ?>)</div></div>
    <?php endif; ?>
  </div>
</section>

<section id="jobs">
  <h2>Cron jobs <span class="grow"></span>
    <input type="search" id="job-filter" placeholder="Filter jobs" autocomplete="off" aria-label="Filter jobs" style="text-transform:none;letter-spacing:0">
  </h2>
  <div class="card">
    <?php if (count($Jobs) === 0): ?>
      <div class="empty">No cron jobs found</div>
    <?php else: ?>
      <div class="table-wrap" style="max-height:60vh">
        <table id="job-table">
          <thead>
            <tr><th>Job</th><th class="nowrap">Status</th><th class="nowrap">Active</th><th class="nowrap">Duration</th><th class="nowrap">Last started</th><th class="nowrap">Last finished</th></tr>
          </thead>
          <tbody>
          <?php foreach ($Jobs as $j): ?>
            <tr>
              <td>
                <a class="job-file" href="<?= h(mon_url(array('query' => $j['name']), array(), true)) ?>#logs" title="Show logs matching &quot;<?= h($j['name']) ?>&quot;"><?= h($j['file']) ?></a>
              </td>
              <td class="nowrap"><?= $j['running'] ? '<span class="pill info"><span class="dot pulse" style="display:inline-block;width:7px;height:7px;margin-right:6px;color:var(--info)"></span>Running</span>' : '<span class="pill muted">Idle</span>' ?></td>
              <td class="nowrap"><?= $j['active'] ? '<span class="pill ok">Active</span>' : '<span class="pill warn">Inactive</span>' ?></td>
              <td class="nowrap mono"><?= h($j['duration']) ?></td>
              <td class="nowrap mono" title="<?= h($j['started']) ?>"><?= h($j['started']) ?> <span class="muted">(<?= h($j['started_ago']) ?>)</span></td>
              <td class="nowrap mono"><?= h($j['ended']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="timeline-section">
  <h2>Script timeline <span class="grow"></span>
    <span class="seg" id="range" style="text-transform:none;letter-spacing:0">
      <button type="button" data-h="1">1h</button>
      <button type="button" data-h="6">6h</button>
      <button type="button" data-h="24">24h</button>
      <button type="button" data-h="0">All</button>
    </span>
  </h2>
  <div class="card">
    <div id="timeline"></div>
    <div class="timeline-note" id="timeline-note">Last <?= MON_SCRIPT_LIMIT ?> script runs. Click a bar to show the logs of that process.</div>
  </div>
</section>

<section id="logs">
  <h2>Logs <span class="grow"></span><span style="text-transform:none;letter-spacing:0;font-weight:400"><?= count($Logs) ?> <?= count($Logs) >= MON_LOG_LIMIT ? '(latest, limit reached)' : 'entries' ?></span></h2>
  <div class="card">
    <form class="filters" method="get" action="<?= h(strtok($_SERVER['REQUEST_URI'], '?')) ?>#logs">
      <?php if ($Refresh > 0): ?><input type="hidden" name="refresh" value="<?= $Refresh ?>"><?php endif; ?>
      <input type="search" name="query" placeholder="Search messages" value="<?= h($Query) ?>" autocomplete="off" aria-label="Search messages">
      <select name="level" aria-label="Level">
        <option value="0" <?= $Level == 0 ? 'selected' : '' ?>>All (excl. indexer)</option>
        <option value="1" <?= $Level == 1 ? 'selected' : '' ?>>1 - Error</option>
        <option value="2" <?= $Level == 2 ? 'selected' : '' ?>>2 - Warning</option>
        <option value="3" <?= $Level == 3 ? 'selected' : '' ?>>3 - Info</option>
        <option value="4" <?= $Level == 4 ? 'selected' : '' ?>>4 - Silly</option>
        <option value="10" <?= $Level == 10 ? 'selected' : '' ?>>10 - Indexer</option>
        <option value="100" <?= $Level == 100 ? 'selected' : '' ?>>100 - V2 migration</option>
      </select>
      <input type="text" name="pid" inputmode="numeric" pattern="[0-9]*" placeholder="PID" value="<?= h($Pid) ?>" aria-label="Process id">
      <input type="date" name="date" value="<?= h($Day) ?>" aria-label="Date">
      <button class="primary" type="submit">Search</button>
      <a class="btn" href="<?= h(mon_url(array(), array(), true)) ?>#logs">Clear</a>
    </form>
    <?php if ($LogError): ?>
      <div class="empty">Failed to load logs</div>
    <?php elseif (count($Logs) === 0): ?>
      <div class="empty">No log entries match the current filters</div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th class="nowrap">Time</th><th class="nowrap">Level</th><th class="nowrap">PID</th><th>Message</th></tr>
          </thead>
          <tbody>
          <?php foreach ($Logs as $Log):
            list($LevelName, $LevelClass) = mon_level($Log->level);
            $Tint = ($Log->level <= 2 && !in_array($Level, array(1, 2))) ? ($Log->level == 1 ? 'row-error' : 'row-warn') : '';
            $Time = explode(' ', (string)$Log->created_at);
            ?>
            <tr class="<?= $Tint ?>">
              <td class="nowrap mono"><span class="muted"><?= h($Time[0]) ?></span> <?= h(isset($Time[1]) ? $Time[1] : '') ?>.<?= h(substr($Log->microtime, 0, 3)) ?></td>
              <td class="nowrap"><span class="pill <?= $LevelClass ?>"><?= h($LevelName) ?></span></td>
              <td class="nowrap mono"><a href="<?= h(mon_url(array('pid' => $Log->pid), array(), true)) ?>#logs"><?= h($Log->pid) ?></a></td>
              <td class="msg"><?= mon_format_message($Log->message) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="redis">
  <h2>Redis &amp; websocket backbone</h2>
  <div class="split">
    <div class="card card-pad">
      <form class="form" method="post" action="<?= h(mon_url()) ?>">
        <input type="hidden" name="csrf" value="<?= h($Csrf) ?>">
        <label>Type
          <select name="pub_type">
            <option value="notify_team" <?= $BbForm['type'] === 'notify_team' ? 'selected' : '' ?>>notify_team</option>
            <option value="notify_user" <?= $BbForm['type'] === 'notify_user' ? 'selected' : '' ?>>notify_user</option>
          </select>
        </label>
        <label>Action
          <select name="pub_action"><option value="ops_event" selected>ops_event</option></select>
        </label>
        <label>Team (index key)<input type="text" name="pub_team" value="<?= h($BbForm['team']) ?>" required></label>
        <label>World<input type="text" name="pub_world" value="<?= h($BbForm['world']) ?>" placeholder="nl108" required></label>
        <label>Message<input type="text" name="pub_msg" value="<?= h($BbForm['msg']) ?>" required></label>
        <div class="actions">
          <button class="primary" type="submit" name="op" value="publish">Publish</button>
          <button type="submit" name="op" value="test" formnovalidate>Send test message</button>
          <button type="submit" name="op" value="heartbeat" formnovalidate>Heartbeat</button>
        </div>
      </form>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;min-width:0">
      <?php if ($RedisStats): ?>
        <div class="stats card">
          <?php foreach ($RedisStats as $label => $value): ?>
            <div><div class="l"><?= h($label) ?></div><div style="font-weight:600"><?= h($value) ?></div></div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="card empty">Redis is offline</div>
      <?php endif; ?>

      <div class="card">
        <div style="padding:12px 16px;font-weight:600">Operation states <span class="muted" style="font-weight:400">(<?= count($Health['cmd_keys']) ?><?= $Health['cmd_keys_capped'] ? '+' : '' ?>)</span></div>
        <?php if (count($Health['cmd_keys']) === 0): ?>
          <div class="empty" style="padding-top:8px">No active operations</div>
        <?php else: ?>
          <div class="table-wrap" style="max-height:320px">
            <table>
              <thead><tr><th>Index</th><th>Key</th><th>State</th></tr></thead>
              <tbody>
              <?php foreach ($Health['cmd_keys'] as $k): ?>
                <tr><td><?= h($k['index']) ?></td><td class="mono"><?= h($k['key']) ?></td><td class="msg" style="width:auto"><?= h($k['state']) ?></td></tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <?php if (is_array($RedisInfo)): ?>
        <details class="card">
          <summary>Raw INFO</summary>
          <pre><?= h(json_encode($RedisInfo, JSON_PRETTY_PRINT)) ?></pre>
        </details>
      <?php endif; ?>
    </div>
  </div>
</section>

<footer>Generated in <?= mon_ms($PageStart) ?> ms</footer>

</main>

<script>
(function () {
  'use strict';
  var runs = <?= json_encode($Runs, $JsonFlags) ?>;
  var refresh = <?= (int)$Refresh ?>;

  // Navigate to this page with only the given params (plus the refresh interval)
  function navTo(params) {
    var u = new URL(location.href);
    var keep = u.searchParams.get('refresh');
    u.search = '';
    u.hash = '';
    if (keep) u.searchParams.set('refresh', keep);
    Object.keys(params).forEach(function (k) { u.searchParams.set(k, params[k]); });
    location.href = u.toString() + '#logs';
  }

  // Auto refresh: skip while the tab is hidden or a field is being edited
  document.getElementById('refresh').addEventListener('change', function () {
    var u = new URL(location.href);
    if (this.value === '0') u.searchParams.delete('refresh'); else u.searchParams.set('refresh', this.value);
    location.href = u.toString();
  });
  if (refresh > 0) {
    setInterval(function () {
      var a = document.activeElement;
      if (!document.hidden && !(a && /^(INPUT|SELECT|TEXTAREA)$/.test(a.tagName))) location.reload();
    }, refresh * 1000);
  }

  // Job filter
  var jobFilter = document.getElementById('job-filter');
  jobFilter.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#job-table tbody tr').forEach(function (tr) {
      tr.style.display = tr.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
    });
  });

  // Timeline
  var timelineEl = document.getElementById('timeline');
  var note = document.getElementById('timeline-note');
  var buttons = document.querySelectorAll('#range button');
  var rangeHours = 6;
  var chartReady = false;

  function countSince(hours) {
    var cutoff = hours ? Date.now() - hours * 3600000 : 0;
    return runs.filter(function (r) { return r[3] >= cutoff; }).length;
  }

  function drawTimeline() {
    buttons.forEach(function (b) { b.classList.toggle('on', +b.dataset.h === rangeHours); });
    var cutoff = rangeHours ? Date.now() - rangeHours * 3600000 : 0;
    var shown = runs.filter(function (r) { return r[3] >= cutoff; });
    if (!shown.length) {
      timelineEl.innerHTML = '<div class="empty">No script runs in this period</div>';
      return;
    }
    var data = new google.visualization.DataTable();
    data.addColumn({ type: 'string', id: 'Script' });
    data.addColumn({ type: 'string', id: 'PID' });
    data.addColumn({ type: 'date', id: 'Start' });
    data.addColumn({ type: 'date', id: 'End' });
    var labels = {};
    shown.forEach(function (r) {
      labels[r[0]] = 1;
      data.addRow([r[0], r[1], new Date(r[2]), new Date(Math.max(r[3], r[2] + 1000))]);
    });
    var height = Math.min(Math.max(Object.keys(labels).length * 36 + 60, 200), 1000);
    var chart = new google.visualization.Timeline(timelineEl);
    google.visualization.events.addListener(chart, 'select', function () {
      var sel = chart.getSelection();
      if (sel.length) navTo({ pid: data.getValue(sel[0].row, 1) });
    });
    chart.draw(data, {
      height: height,
      backgroundColor: '#121927',
      colors: ['#7c9cff', '#34d399', '#fbbf24', '#c084fc', '#60a5fa', '#f472b6', '#2dd4bf'],
      timeline: {
        rowLabelStyle: { color: '#d7e0ee', fontSize: 12 },
        barLabelStyle: { color: '#0b0f17', fontSize: 11 }
      },
      hAxis: { format: 'HH:mm', textStyle: { color: '#8393ad' } },
      alternatingRowStyle: false
    });
  }

  buttons.forEach(function (b) {
    b.addEventListener('click', function () {
      rangeHours = +b.dataset.h;
      if (chartReady) drawTimeline();
    });
  });

  window.__monInitTimeline = function () {
    chartReady = true;
    if (!runs.length) {
      timelineEl.innerHTML = '<div class="empty">No script runs recorded</div>';
      return;
    }
    if (countSince(rangeHours) === 0) rangeHours = 0;
    drawTimeline();
    var t;
    window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(drawTimeline, 200); });
  };

  window.__monTimelineFailed = function () {
    timelineEl.innerHTML = '<div class="empty">Could not load Google Charts</div>';
  };
})();
</script>
<script src="https://www.gstatic.com/charts/loader.js" async
        onload="google.charts.load('current', {packages: ['timeline']}); google.charts.setOnLoadCallback(window.__monInitTimeline);"
        onerror="window.__monTimelineFailed()"></script>
</body>
</html>

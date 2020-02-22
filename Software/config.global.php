<?php

namespace Grepodata\stats;

// import data endpoints
define('WORLD_DATA_PREFIX',       'http://grepolis.maxtrix.net/world/');
define('WORLD_DATA_STATS',        '/stats');

// https://en.forum.grepolis.com/index.php?threads/changes-to-world-data.5589/
define('INNO_DATA_PREFIX',        'grepolis.com/data/');
define('INNO_DATA_PLAYER',        'players.txt.gz');
define('INNO_DATA_ALLIANCE',      'alliances.txt.gz');
define('INNO_DATA_TOWN',          'towns.txt.gz');
define('INNO_DATA_ISLAND',        'islands.txt.gz');
define('INNO_DATA_PLAYER_ALL',    'player_kills_all.txt.gz');
define('INNO_DATA_PLAYER_ATT',    'player_kills_att.txt.gz');
define('INNO_DATA_PLAYER_DEF',    'player_kills_def.txt.gz');
define('INNO_DATA_ALLIANCE_ALL',  'alliance_kills_all.txt.gz');
define('INNO_DATA_ALLIANCE_ATT',  'alliance_kills_att.txt.gz');
define('INNO_DATA_ALLIANCE_DEF',  'alliance_kills_def.txt.gz');
define('INNO_DATA_CONQUERS',      'conquers.txt.gz');

define('USERSCRIPT_VERSION',      '3.8.0');
define('USERSCRIPT_UPDATE_INFO',  'Dynamic userscript loading: you will now always have the latest version');

define('DEFAULT_SERVER',  'nl');
define('DEFAULT_WORLD',   'nl75');
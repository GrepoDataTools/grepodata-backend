<?php

namespace Grepodata\stats;

use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;

// Config
$env = php_uname('n');
if ($env === PRIVATE_DEV_MACHINE_NAME) {
  // LOCAL:
  define('bDevelopmentMode', true);
  $g_aConfiguration = array(
    'mysql' => array(
      'driver'    => 'mysql',
      'host'      => PRIVATE_IP.':'.PRIVATE_SQL_PORT,
      'database'  => PRIVATE_SQL_DATABASE,
      'username'  => PRIVATE_SQL_USERNAME,
      'password'  => PRIVATE_SQL_PASSWORD,
      'charset'   => 'utf8',
      'collation' => 'utf8_unicode_ci',
      'prefix'    => '',
    ),
    'pushbullet' => array(
      'token' => PRIVATE_PUSHBULLET_TOKEN,
      'url'   => 'https://api.pushbullet.com/v2/pushes'
    ),
    'elasticsearch' => array(
      'hosts'  => array(
        PRIVATE_IP.':9200'
      )
    )
  );

  define('TEMP_DIRECTORY', 'X:/dev/grepodata/grepodata-backend/Temp/');
  define('MAP_DIRECTORY', 'X:/dev/grepodata/grepodata-backend/Temp/');
  define('CAPTCHA_SECRET', PRIVATE_CAPTCHA_KEY);
  define('JWT_SECRET',     PRIVATE_JWT_SECRET);

  // Smarty dirs
  $SourceDir = 'X:/dev/grepodata/grepodata-backend/';
  define('SMARTY_TEMPLATE_DIR', "$SourceDir/Software/Templates");
  define('SMARTY_COMPILE_DIR', "$SourceDir/Software/Templates/compiled");
  define('SMARTY_CACHE_DIR', "$SourceDir/Software/Templates/cache");

  define('USERSCRIPT_DIRECTORY', "$SourceDir/Software/Templates/compiled");
} else {
  // PRODUCTION:
  define('bDevelopmentMode', false);
  $g_aConfiguration = array(
    'mysql' => array(
      'driver'    => 'mysql',
      'host'      => 'localhost',
      'database'  => PRIVATE_SQL_DATABASE,
      'username'  => PRIVATE_SQL_USERNAME,
      'password'  => PRIVATE_SQL_PASSWORD,
      'charset'   => 'utf8',
      'collation' => 'utf8_unicode_ci',
      'prefix'    => '',
    ),
    'pushbullet' => array(
      'token' => PRIVATE_PUSHBULLET_TOKEN,
      'url'   => 'https://api.pushbullet.com/v2/pushes'
    ),
    'elasticsearch' => array(
      'hosts'  => array(
        'localhost:9200'
      )
    )
  );
  define('TEMP_DIRECTORY', '/home/vps/gd-stats-api/Temp/');
  define('MAP_DIRECTORY',  '/home/vps/gd-frontend/maps/');
  define('CAPTCHA_SECRET', PRIVATE_CAPTCHA_KEY);
  define('JWT_SECRET',     PRIVATE_JWT_SECRET);

  // Smarty dirs
  $SourceDir = '/home/vps/gd-stats-api/active/';
  $UserscriptDir = '/home/vps/gd-stats-api/Userscript';
  define('SMARTY_TEMPLATE_DIR', "$SourceDir/Software/Templates");
  define('SMARTY_COMPILE_DIR', "$UserscriptDir/smarty/compiled");
  define('SMARTY_CACHE_DIR', "$UserscriptDir/smarty/cache");

  define('USERSCRIPT_DIRECTORY', $UserscriptDir . '/v1');
}

define('MAIL_TRANSPORT_HOST', PRIVATE_MAIL_TRANSPORT_HOST);
define('MAIL_TRANSPORT_NAME', PRIVATE_MAIL_TRANSPORT_NAME);
define('MAIL_TRANSPORT_KEY',  PRIVATE_MAIL_TRANSPORT_KEY);

// Timezone!!
date_default_timezone_set('UTC');

// Mysql
$SqlCapsule = new Capsule;
$SqlCapsule->addConnection($g_aConfiguration['mysql']);
$SqlCapsule->setAsGlobal();
$SqlCapsule->bootEloquent();
$SqlCapsule->setFetchMode(PDO::FETCH_ASSOC);

// Pushbullet
\Grepodata\Library\Logger\Pushbullet::SetConfiguration($g_aConfiguration['pushbullet']);

// Elasticsearch
\Grepodata\Library\Elasticsearch\Client::SetConfiguration($g_aConfiguration['elasticsearch']);

unset($g_aConfiguration);

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

  define('TEMP_DIRECTORY',   'C:/dev/grepodata-backend/Temp/');
  define('MAP_DIRECTORY',    'C:/dev/grepodata-backend/Temp/');
  define('DATA_DIRECTORY',    'C:/dev/grepodata-backend/Temp/');
  define('REPORT_DIRECTORY', 'C:/dev/grepodata-backend/Temp/');
  define('CAPTCHA_SECRET', PRIVATE_CAPTCHA_KEY);
  define('JWT_SECRET',     PRIVATE_JWT_SECRET);
  define('REFRESH_SECRET', PRIVATE_JWT_REFRESH);

  // Smarty dirs
  $SourceDir = 'C:/dev/grepodata-backend/';
  define('SMARTY_TEMPLATE_DIR', "$SourceDir/Software/Templates/templates");
  define('SMARTY_COMPILE_DIR', "$SourceDir/Software/Templates/compiled");
  define('SMARTY_CACHE_DIR', "$SourceDir/Software/Templates/cache");

  // Image directories
  define('IMG_UPLOAD_DIR_REPORT', 'C:/dev/grepodata-backend/Temp/');

  define('USERSCRIPT_INDEXER', $SourceDir."Software/Templates/compiled");
  define('USERSCRIPT_TEMP', $SourceDir."Software/Templates/compiled");
  define('REPORT2IMG_CONTAINER', $SourceDir."Software/Application/debugger");
  define('HASH2IMG_DIRECTORY', REPORT2IMG_CONTAINER."/temp");

  define('FRONTEND_URL', 'http://localhost:4200');
} else if ('ACCEPTANCE' === PRIVATE_DEV_MACHINE_NAME) {
  // ACCEPTANCE:
//  define('bDevelopmentMode', true);
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
  define('TEMP_DIRECTORY',   '/home/vps/grepodata/acceptance/grepodata-backend/Temp/');
  define('MAP_DIRECTORY',    '/home/vps/grepodata/acceptance/grepodata-frontend/maps/');
  define('DATA_DIRECTORY',   '/home/vps/grepodata/acceptance/grepodata-backend/Data/');
  define('REPORT_DIRECTORY', '/home/vps/grepodata/acceptance/grepodata-frontend/reports/');

  define('CAPTCHA_SECRET', PRIVATE_CAPTCHA_KEY);
  define('JWT_SECRET',     PRIVATE_JWT_SECRET);
  define('REFRESH_SECRET', PRIVATE_JWT_REFRESH);

  // Smarty dirs
  $HomeDir = '/home/vps/grepodata/acceptance/grepodata-backend';
  $SourceDir = $HomeDir . '/active/';
  $UserscriptDir = $HomeDir . '/Userscript';
  define('SMARTY_TEMPLATE_DIR', "$UserscriptDir/smarty/templates");
  define('SMARTY_COMPILE_DIR', "$UserscriptDir/smarty/compiled");
  define('SMARTY_CACHE_DIR', "$UserscriptDir/smarty/cache");

  // Image directories
  define('IMG_UPLOAD_DIR_REPORT', $HomeDir."/Temp/bugreports/");

  define('USERSCRIPT_INDEXER', $UserscriptDir . '/v1');
  define('USERSCRIPT_TEMP', $UserscriptDir . '/v2');
  define('REPORT2IMG_CONTAINER', $HomeDir."/Temp/report2img");
  define('HASH2IMG_DIRECTORY', REPORT2IMG_CONTAINER."/temp");

  define('FRONTEND_URL', 'https://test.grepodata.com');
} else if ('PRODUCTION' === PRIVATE_DEV_MACHINE_NAME) {
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
  define('TEMP_DIRECTORY',   '/home/vps/grepodata/production/grepodata-backend/Temp/');
  define('MAP_DIRECTORY',    '/home/vps/grepodata/production/grepodata-frontend/maps/');
  define('DATA_DIRECTORY',   '/home/vps/grepodata/production/grepodata-backend/Data/');
  define('REPORT_DIRECTORY', '/home/vps/grepodata/production/grepodata-frontend/reports/');

  define('CAPTCHA_SECRET', PRIVATE_CAPTCHA_KEY);
  define('JWT_SECRET',     PRIVATE_JWT_SECRET);
  define('REFRESH_SECRET', PRIVATE_JWT_REFRESH);

  // Smarty dirs
  $HomeDir = '/home/vps/grepodata/production/grepodata-backend';
  $SourceDir = $HomeDir . '/active/';
  $UserscriptDir = $HomeDir . '/Userscript';
  define('SMARTY_TEMPLATE_DIR', "$UserscriptDir/smarty/templates");
  define('SMARTY_COMPILE_DIR', "$UserscriptDir/smarty/compiled");
  define('SMARTY_CACHE_DIR', "$UserscriptDir/smarty/cache");

  // Image directories
  define('IMG_UPLOAD_DIR_REPORT', $HomeDir."/Temp/bugreports/");

  define('USERSCRIPT_INDEXER', $UserscriptDir . '/v1');
  define('USERSCRIPT_TEMP', $UserscriptDir . '/v2');
  define('REPORT2IMG_CONTAINER', $HomeDir."/Temp/report2img");
  define('HASH2IMG_DIRECTORY', REPORT2IMG_CONTAINER."/temp");

  define('FRONTEND_URL', 'https://grepodata.com');
} else {
  die("Unknown environment '$env'. make sure your config.private.php file is configured correctly");
}

// Mail
define('MAIL_TRANSPORT_HOST', PRIVATE_MAIL_TRANSPORT_HOST);
define('MAIL_TRANSPORT_NAME', PRIVATE_MAIL_TRANSPORT_NAME);
define('MAIL_TRANSPORT_KEY',  PRIVATE_MAIL_TRANSPORT_KEY);
define('MAIL_WEB_API_KEY',  PRIVATE_MAIL_WEBAPI_KEY);

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

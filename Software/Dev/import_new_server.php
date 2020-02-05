<?php

namespace Grepodata\Cron;

use Carbon\Carbon;
use DOMDocument;
use Grepodata\Library\Controller\World;
use Grepodata\Library\Cron\Common;
use Grepodata\Library\Cron\InnoData;
use Grepodata\Library\Cron\WorldData;
use Grepodata\Library\Import\Daily;
use Grepodata\Library\Import\Elasticsearch;
use Grepodata\Library\Import\Hourly;
use Grepodata\Library\Import\Towns;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Message;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

$Server = 'tr';
$timezone = 'Europe/Istanbul';

Logger::enableDebug();
Logger::debugInfo("Started '.$Server.' import");

//Get worlds
$aRows = WorldData::loadServerWorlds($Server);

/** @var \DOMElement[] $aColumns */
foreach ($aRows as $aColumns) {
  if ($aColumns->length !== 11) {
    continue;
  }

  /** @var \DOMElement $World */
  $World = $aColumns[1]->getElementsByTagName('a')[0];
  if (is_null($World)) continue;
  $WorldUrl = $World->getAttribute('href');
  $Name = $World->textContent;
  $Code = substr($WorldUrl, strrpos($WorldUrl, '/')+1);
  $Open = $aColumns[2]->getElementsByTagName('span')[0];
  if (is_null($Open)) continue;
  $Text = $Open->textContent;

  if ($Text !== 'Yes' || $Name === '' || !is_numeric($Code)) {
    continue;
  }

  $WorldCode = $Server . $Code;

  if (isset($argv[1]) && $argv[1]!=null && $argv[1]!='' && $argv[1]!=$WorldCode) {
    continue;
  }

  /** @var \Grepodata\Library\Model\World $oWorld */
  Logger::debugInfo("Saving new world with id: " . $WorldCode . " - Name: " . $Name);
  $oWorld = World::firstOrNew($WorldCode);
  $oWorld->grep_id = $WorldCode;
  $oWorld->php_timezone = $timezone;
  $oWorld->name = $Name;
  $oWorld->stopped = 1;
  $oWorld->feature_level = 0;
  $oWorld->grep_server_time = $oWorld->getServerTime();
  $oWorld->last_reset_time = $oWorld->getLastUtcResetTime();
  $oWorld->etag = "A";
  $oWorld->save();

  Logger::debugInfo("Checking new world with id: " . $oWorld->grep_id);

  // Test endpoint
  if (InnoData::testEndpoint($oWorld->grep_id)) {
    // new world!
    Logger::warning("New world detected: " . $oWorld->grep_id);

    // Handle imports for new world
    try {
      Logger::debugInfo('Starting daily import for new world.');
      if (Daily::DataImportDaily($oWorld) !== true) {
        throw new \Exception("Daily import failed for new world.");
      }
      Logger::debugInfo('Daily import completed. Starting hourly import for new world.');
      if (Hourly::DataImportHourly($oWorld) !== true) {
        throw new \Exception("Hourly import failed for new world.");
      }
      Logger::debugInfo('Hourly import completed. Starting towns import for new world.');
      if (Towns::DataImportTowns($oWorld) !== true) {
        throw new \Exception("Towns import failed for new world.");
      }
      Logger::debugInfo('Towns import completed. Starting islands import for new world.');
      if (Towns::DataImportIslands($oWorld) !== true) {
        throw new \Exception("Islands import failed for new world.");
      }
      Logger::debugInfo('Towns import completed. Starting elasticsearch import for new world.');
      if (Elasticsearch::DataImportElasticsearch($oWorld) !== true) {
        throw new \Exception("Elasticsearch import failed for new world.");
      }

      // Done
      Logger::warning('Successful import of new world. Opening world status.');
      $oWorld->stopped = 0;
      $oWorld->save();
    } catch (\Exception $e) {
      Logger::warning('New world import failed with status: '. $e->getMessage());
    }
  }

}

Logger::debugInfo("Finished successful execution of '.$Server.' import.");
<?php

use Grepodata\Library\Indexer\IndexBuilderV2;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\Indexer\IndexInfoV1;

use Grepodata\Library\Model\IndexV2\OwnersActual;
use Illuminate\Database\Capsule\Manager as DB;

if (PHP_SAPI !== 'cli') {
  die('not allowed');
}

require(__DIR__ . '/../config.php');

Logger::enableDebug();

/**
=== This script will migrate several index components to their V2 version

Reused tables: (will stay in use without changes)
Index_overview
Index_owners
Index_stats
Index_daily_report

Migrated v1 tables: (will be migrated to V2 tables)
Index_info => Indexer_info (script_version='1')
Index_notes => Indexer_notes
Index_city => Indexer_intel
Index_report => Indexer_intel
Index_report_hash => Indexer_intel_shared + Indexer_intel
Index_conquest => Indexer_conquest + Indexer_conquest_overview

Deleted tables: (all functionality related to these tables goes offline)
Index_auth
Index_banned
Index_bot_detect
Index_unit_info
 */

/** Migrate indexes */
// 'Index_info' becomes 'Indexer_info'
//$bMigrateIndexes = true;
$bMigrateIndexes = false;
if ($bMigrateIndexes) {
  Logger::warning("Migrating index info");
  $aOldIndexes = IndexInfoV1::get();
  //$aOldIndexes = IndexInfoV1::where('key_code', '=', 'khuq8bb9')->get();
  Logger::warning("Found ".sizeof($aOldIndexes)." indexes to be migrated");
  $i = 0;
  $Total = sizeof($aOldIndexes);
  $Start = time();
  /** @var IndexInfoV1 $oIndexV1 */
  foreach ($aOldIndexes as $oIndexV1) {
    $i++;
    if ($i % 250 == 0) {
      $Elapsed = time() - $Start;
      Logger::debugInfo("Index migration progress: " . $i . " / " . $Total . ". Elapsed minutes: " . round($Elapsed / 60, 0) . ". Remaining minutes (est.): " . round((($Elapsed/60) / $i) * $Total, 0) );
    }
    try {
      // Insert new index
      $oIndexV2 = new IndexInfo();
      $oIndexV2->key_code = $oIndexV1->key_code;
      $oIndexV2->index_name = $oIndexV1->key_code;
      $oIndexV2->world = $oIndexV1->world;
      $oIndexV2->script_version = $oIndexV1->script_version;
      $oIndexV2->index_version = '1';
      $oIndexV2->mail = $oIndexV1->mail;
      $oIndexV2->created_by_user = 0;
      $oIndexV2->new_report = $oIndexV1->new_report;
      $oIndexV2->csa = null;
      $oIndexV2->delete_old_intel_days = 0;
      $oIndexV2->allow_join_v1_key = true;
      $oIndexV2->share_link = IndexBuilderV2::generateIndexKey(10);
      $oIndexV2->status = $oIndexV1->status;
      $oIndexV2->moved_to_index = $oIndexV1->moved_to_index;
      $oIndexV2->created_at = $oIndexV1->created_at;
      $oIndexV2->updated_at = $oIndexV1->updated_at;
      $oIndexV2->save();
    } catch (Exception $e) {
      Logger::warning("Unable to migrate index ".$oIndexV1->key_code. " : ".$e->getMessage());
    }
  }
}

/** Migrate intel */
// Import each index individually
//$bMigrateIntel = true;
$bMigrateIntel = false;
if ($bMigrateIntel) {
  $aOldIndexes = IndexInfoV1::where('csa', '!=', 'v1migration_complete')->orWhere('csa', '=', null)->get();
  Logger::warning("Found ".sizeof($aOldIndexes)." indexes to migrate intel for.");

  $i = 0;
  $Total = sizeof($aOldIndexes);
  $Start = time();
  /** @var IndexInfoV1 $oIndexV1 */
  foreach ($aOldIndexes as $oIndexV1) {
    $i++;
    if ($i % 25 == 0) {
      $Elapsed = time() - $Start;
      Logger::debugInfo("Intel migration progress: " . $i . " / " . $Total . ". Elapsed minutes: " . round($Elapsed / 60, 0) . ". Remaining minutes (est.): " . round((($Elapsed/60) / $i) * $Total, 0) );
    }

    try {

      // V1 intel from 'Index_report_hash', 'Index_report' and 'Index_city' is merged into 'Indexer_intel'
      $SQL = "
    INSERT IGNORE INTO Indexer_intel (indexed_by_user_id, hash, v1_index, world, source_type, report_type, script_version, town_id, town_name, player_id, player_name, alliance_id, poster_player_name, poster_player_id, poster_alliance_id, conquest_id, conquest_details, report_date, parsed_date, hero, god, silver, buildings, land_units, sea_units, fireships, mythical_units, created_at, updated_at, soft_deleted, report_json, report_info, parsing_failed, debug_explain)
    SELECT 
      0 as indexed_by_user_id,
      reporthash.report_id as hash,
      '" . $oIndexV1->key_code . "' as v1_index,
      '" . $oIndexV1->world . "' as world,
      intel.type as source_type,
      intel.report_type as report_type,
      report.script_version as script_version,
      intel.town_id,
      intel.town_name,
      intel.player_id,
      intel.player_name,
      intel.alliance_id,
      player.name as poster_player_name,
      intel.poster_player_id,
      intel.poster_alliance_id,
      intel.conquest_id,
      intel.conquest_details,
      intel.report_date,
      intel.parsed_date,
      intel.hero,
      intel.god,
      intel.silver,
      intel.buildings,
      intel.land_units,
      intel.sea_units,
      intel.fireships,
      intel.mythical_units,
      intel.created_at,
      intel.updated_at,
      intel.soft_deleted,
      report.report_json,
      report.report_info,
      0 as parsing_failed,
      null as debug_explain
    FROM Index_city as intel
    LEFT JOIN Index_report as report ON report.city_id = intel.id
    LEFT JOIN Index_report_hash as reporthash ON reporthash.index_report_id = report.id
    LEFT JOIN Player as player ON player.grep_id = intel.poster_player_id AND player.world = '" . $oIndexV1->world . "'
    WHERE intel.index_key = '" . $oIndexV1->key_code . "'
    ";
      $Execute = DB::select(DB::raw($SQL));

      // V1 intel links are added to the 'Indexer_intel_shared' table to link the intel to the V1 index
      $SQL = "
    INSERT IGNORE INTO Indexer_intel_shared (intel_id, report_hash, index_key, user_id, world, created_at, updated_at)
    SELECT 
       id as intel_id,
       intel.hash as report_hash,
       '" . $oIndexV1->key_code . "' as index_key,
       NULL as user_id,
       world,
       created_at,
       updated_at
    FROM Indexer_intel as intel
    WHERE intel.v1_index = '" . $oIndexV1->key_code . "'
    ";

      $Execute = DB::select(DB::raw($SQL));

      $oIndexV1->csa = 'v1migration_complete';
      $oIndexV1->save();
    } catch (Exception $e) {
      Logger::warning("Intel migration error for index " . $oIndexV1->key_code . ": " . $e->getMessage());
    }

  }
}

/** Migrate conquests */
$bMigrateConquests = true;
//$bMigrateConquests = false;
if ($bMigrateConquests) {
  Logger::warning("Migrating conquests.");
  // 'Index_conquest' gets split into 'Indexer_conquest' and 'Indexer_conquest_overview'
  $SQL = "
    INSERT IGNORE INTO Indexer_conquest (id, town_id, world, town_name, player_id, player_name, alliance_id, alliance_name, belligerent_player_id, belligerent_player_name, belligerent_alliance_id, belligerent_alliance_name, first_attack_date, created_at, updated_at, cs_killed, new_owner_player_id)
    SELECT 
           conquest.id,
           conquest.town_id,
           indexinfo.world,
           conquest.town_name,
           conquest.player_id,
           conquest.player_name,
           conquest.alliance_id,
           conquest.alliance_name,
           conquest.belligerent_player_id,
           conquest.belligerent_player_name,
           conquest.belligerent_alliance_id,
           conquest.belligerent_alliance_name,
           conquest.first_attack_date,
           conquest.created_at,
           conquest.updated_at,
           conquest.cs_killed,
           conquest.new_owner_player_id
    FROM Index_conquest as conquest
    LEFT JOIN Index_info as indexinfo ON indexinfo.key_code = conquest.index_key
    ";
  $Execute = DB::select(DB::raw($SQL));

  $SQL = "
    INSERT IGNORE INTO Indexer_conquest_overview (conquest_id, uid, index_key, num_attacks_counted, belligerent_all, total_losses_att, total_losses_def, created_at, updated_at)
    SELECT 
           conquest.id,
           conquest.uid,
           conquest.index_key,
           conquest.num_attacks_counted,
           conquest.belligerent_all,
           conquest.total_losses_att,
           conquest.total_losses_def,
           conquest.created_at,
           conquest.updated_at
    FROM Index_conquest as conquest
    ";
  $Execute = DB::select(DB::raw($SQL));
}


/** Migrate notes */
//$bMigrateNotes = true;
$bMigrateNotes = false;
if ($bMigrateNotes) {
  Logger::warning("Migrating notes.");
  // 'Index_notes' becomes 'Indexer_notes'
  $SQL = "
    INSERT IGNORE INTO Indexer_notes (town_id, user_id, index_key, world, message, note_id, poster_id, poster_name, created_at, updated_at)
    SELECT 
           notes.town_id,
           0 as user_id,
           notes.index_key,
           info.world,
           notes.message,
           notes.note_id,
           notes.poster_id,
           notes.poster_name,
           notes.created_at,
           notes.updated_at
    FROM Index_notes as notes
    LEFT JOIN Index_info as info ON notes.index_key = info.key_code
    ";

  $Execute = DB::select(DB::raw($SQL));
}

/** Migrate owners */
// Parse and extract owners from 'Index_overview' into 'Indexer_owners_actual' records.
//$bMigrateOwners = true;
$bMigrateOwners = false;
if ($bMigrateOwners) {
  $aIndexOverviews = \Grepodata\Library\Model\Indexer\IndexOverview::get();

  $i = 0;
  $Total = sizeof($aIndexOverviews);
  Logger::warning("Migrating owners for ".$Total." indexes.");
  $Start = time();
  /** @var \Grepodata\Library\Model\Indexer\IndexOverview $oIndexOverview */
  foreach ($aIndexOverviews as $oIndexOverview) {
    $i++;
    if ($i % 250 == 0) {
      $Elapsed = time() - $Start;
      Logger::debugInfo("Index owners migration progress: " . $i . " / " . $Total . ". Elapsed minutes: " . round($Elapsed / 60, 0) . ". Remaining minutes (est.): " . round((($Elapsed/60) / $i) * $Total, 0) );
    }

    $aOwners = json_decode($oIndexOverview->owners, true);
    foreach ($aOwners as $aOwner) {
      try {
        $oOwnerActual = new OwnersActual();
        $oOwnerActual->index_key = $oIndexOverview->key_code;
        $oOwnerActual->alliance_id = $aOwner['alliance_id'];
        $oOwnerActual->alliance_name = $aOwner['alliance_name']??'';
        $oOwnerActual->hide_intel = true; // Default = true
        $oOwnerActual->share = (int) round($aOwner['contributions'], 0) ?? 0;
        $oOwnerActual->save();
      } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry')!==false) {
          continue;
        } else {
          Logger::warning("Error adding new actual owner: ".$e->getMessage());
        }

      }
    }
  }

}


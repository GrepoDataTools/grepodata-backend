
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `grepodata_dev`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Alliance`
--

CREATE TABLE `Alliance` (
  `id` int(11) NOT NULL,
  `grep_id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `points` int(11) NOT NULL,
  `rank` mediumint(9) NOT NULL,
  `towns` mediumint(9) NOT NULL,
  `members` mediumint(9) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `att` int(11) DEFAULT NULL,
  `def` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Alliance_changes`
--

CREATE TABLE `Alliance_changes` (
  `id` int(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `player_grep_id` int(11) NOT NULL,
  `player_points` int(11) NOT NULL,
  `player_rank` int(11) NOT NULL,
  `player_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `old_alliance_grep_id` int(11) DEFAULT NULL,
  `old_alliance_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `old_alliance_points` int(11) DEFAULT NULL,
  `old_alliance_rank` int(11) DEFAULT NULL,
  `new_alliance_grep_id` int(11) DEFAULT NULL,
  `new_alliance_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `new_alliance_points` int(11) DEFAULT NULL,
  `new_alliance_rank` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Alliance_history`
--

CREATE TABLE `Alliance_history` (
  `id` int(11) NOT NULL,
  `grep_id` int(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `points` int(11) NOT NULL,
  `rank` mediumint(9) NOT NULL,
  `att` int(11) NOT NULL,
  `def` int(11) NOT NULL,
  `towns` smallint(6) NOT NULL,
  `members` smallint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Alliance_scoreboard`
--

CREATE TABLE `Alliance_scoreboard` (
  `id` int(11) NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `server_time` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `att` text COLLATE utf8_unicode_ci NOT NULL,
  `def` text COLLATE utf8_unicode_ci NOT NULL,
  `con` text COLLATE utf8_unicode_ci NOT NULL,
  `los` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Conquest`
--

CREATE TABLE `Conquest` (
  `id` int(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `town_id` int(11) NOT NULL,
  `time` timestamp NULL DEFAULT NULL,
  `n_p_id` int(11) NOT NULL,
  `o_p_id` int(11) DEFAULT NULL,
  `n_a_id` int(11) DEFAULT NULL,
  `o_a_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Cron_status`
--

CREATE TABLE `Cron_status` (
  `id` int(11) NOT NULL,
  `path` text COLLATE utf8_unicode_ci NOT NULL,
  `running` tinyint(1) DEFAULT '0',
  `active` tinyint(1) DEFAULT '1',
  `last_run_started` timestamp NULL DEFAULT NULL,
  `last_run_ended` timestamp NULL DEFAULT NULL,
  `last_error_check` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Discord_guild`
--

CREATE TABLE `Discord_guild` (
  `id` int(11) NOT NULL,
  `guild_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `server` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Domination_scoreboard`
--

CREATE TABLE `Domination_scoreboard` (
  `id` int(11) NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `domination_json` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_conquest`
--

CREATE TABLE `Indexer_conquest` (
  `id` int(11) NOT NULL,
  `town_id` int(11) DEFAULT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `town_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `player_id` int(11) DEFAULT NULL,
  `player_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alliance_id` int(11) DEFAULT NULL,
  `alliance_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `belligerent_player_id` int(11) DEFAULT NULL,
  `belligerent_player_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `belligerent_alliance_id` int(11) DEFAULT NULL,
  `belligerent_alliance_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_attack_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cs_killed` tinyint(1) DEFAULT NULL,
  `new_owner_player_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_conquest_overview`
--

CREATE TABLE `Indexer_conquest_overview` (
  `id` int(11) NOT NULL,
  `uid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `conquest_id` int(11) NOT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `num_attacks_counted` int(11) NOT NULL,
  `belligerent_all` text COLLATE utf8_unicode_ci,
  `total_losses_att` text COLLATE utf8_unicode_ci,
  `total_losses_def` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_event`
--

CREATE TABLE `Indexer_event` (
  `id` int(11) NOT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `admin_only` tinyint(1) NOT NULL,
  `json` text COLLATE utf8_unicode_ci NOT NULL,
  `local_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_info`
--

CREATE TABLE `Indexer_info` (
  `id` int(11) NOT NULL,
  `key_code` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `index_name` varchar(24) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `script_version` varchar(16) DEFAULT NULL,
  `index_version` varchar(26) DEFAULT '2',
  `mail` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_by_user` int(11) NOT NULL DEFAULT '0',
  `new_report` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `csa` varchar(32) DEFAULT NULL,
  `delete_old_intel_days` int(11) NOT NULL DEFAULT '0',
  `allow_join_v1_key` tinyint(1) DEFAULT '1',
  `share_link` varchar(32) DEFAULT NULL,
  `status` varchar(32) NOT NULL,
  `moved_to_index` varchar(8) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_intel`
--

CREATE TABLE `Indexer_intel` (
  `id` int(11) NOT NULL,
  `indexed_by_user_id` int(11) NOT NULL,
  `hash` int(11) NOT NULL,
  `luck` int(11) NOT NULL DEFAULT '0',
  `v1_index` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `world` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `source_type` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `report_type` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `script_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `town_id` int(11) DEFAULT NULL,
  `town_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `player_id` int(11) DEFAULT NULL,
  `player_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alliance_id` int(11) DEFAULT NULL,
  `poster_player_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `poster_player_id` int(11) DEFAULT NULL,
  `poster_alliance_id` int(11) DEFAULT NULL,
  `conquest_id` int(11) DEFAULT NULL,
  `conquest_details` text COLLATE utf8_unicode_ci,
  `report_date` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parsed_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hero` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `god` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `silver` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `buildings` text COLLATE utf8_unicode_ci,
  `land_units` text COLLATE utf8_unicode_ci,
  `sea_units` text COLLATE utf8_unicode_ci,
  `fireships` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mythical_units` text COLLATE utf8_unicode_ci,
  `is_previous_owner_intel` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `soft_deleted` timestamp NULL DEFAULT NULL,
  `report_json` text COLLATE utf8_unicode_ci,
  `report_info` text COLLATE utf8_unicode_ci,
  `parsing_failed` tinyint(1) DEFAULT '0',
  `parsing_error` tinyint(1) DEFAULT '0',
  `debug_explain` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_intel_shared`
--

CREATE TABLE `Indexer_intel_shared` (
  `id` int(11) NOT NULL,
  `intel_id` int(11) NOT NULL,
  `report_hash` int(11) NOT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `player_id` int(11) DEFAULT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_linked`
--

CREATE TABLE `Indexer_linked` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `player_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `server` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `confirmed` tinyint(1) DEFAULT '0',
  `town_token` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_notes`
--

CREATE TABLE `Indexer_notes` (
  `id` int(11) NOT NULL,
  `town_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `note_id` int(11) DEFAULT NULL,
  `poster_id` int(11) NOT NULL,
  `poster_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_owners_actual`
--

CREATE TABLE `Indexer_owners_actual` (
  `id` int(11) NOT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `alliance_id` int(11) NOT NULL,
  `alliance_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hide_intel` tinyint(1) NOT NULL DEFAULT '1',
  `share` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_roles`
--

CREATE TABLE `Indexer_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `index_key` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `role` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `contribute` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Indexer_script_token`
--

CREATE TABLE `Indexer_script_token` (
  `id` int(11) NOT NULL,
  `token` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `client` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Index_daily_report`
--

CREATE TABLE `Index_daily_report` (
  `id` int(11) NOT NULL,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Index_overview`
--

CREATE TABLE `Index_overview` (
  `id` int(11) NOT NULL,
  `key_code` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `owners` text COLLATE utf8_unicode_ci NOT NULL,
  `contributors` text COLLATE utf8_unicode_ci,
  `latest_report` timestamp NULL DEFAULT NULL,
  `max_version` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alliances_indexed` text COLLATE utf8_unicode_ci NOT NULL,
  `players_indexed` text COLLATE utf8_unicode_ci NOT NULL,
  `latest_intel` text COLLATE utf8_unicode_ci,
  `total_reports` int(11) NOT NULL,
  `spy_reports` int(11) NOT NULL,
  `enemy_attacks` int(11) NOT NULL,
  `friendly_attacks` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Index_owners`
--

CREATE TABLE `Index_owners` (
  `id` int(11) NOT NULL,
  `key_code` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `owners_generated` text COLLATE utf8_unicode_ci,
  `owners_excluded` text COLLATE utf8_unicode_ci,
  `owners_included` text COLLATE utf8_unicode_ci,
  `owners_computed` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Index_stats`
--

CREATE TABLE `Index_stats` (
  `id` int(11) NOT NULL,
  `reports` int(11) NOT NULL,
  `town_count` int(11) NOT NULL,
  `user_count` int(11) NOT NULL,
  `shared_count` int(11) NOT NULL,
  `index_count` int(11) NOT NULL,
  `users_today` int(11) NOT NULL,
  `users_week` int(11) NOT NULL,
  `users_month` int(11) NOT NULL,
  `teams_today` int(11) NOT NULL,
  `teams_week` int(11) NOT NULL,
  `teams_month` int(11) NOT NULL,
  `reports_today` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Island`
--

CREATE TABLE `Island` (
  `id` int(11) NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `grep_id` int(11) NOT NULL,
  `island_x` smallint(6) NOT NULL,
  `island_y` smallint(6) NOT NULL,
  `island_type` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Mail_jobs`
--

CREATE TABLE `Mail_jobs` (
  `id` int(11) NOT NULL,
  `to_mail` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `processing` tinyint(1) NOT NULL,
  `processed` tinyint(1) NOT NULL,
  `attempts` smallint(6) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Message`
--

CREATE TABLE `Message` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `mail` varchar(128) NOT NULL,
  `message` text NOT NULL,
  `files` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Operation_log`
--

CREATE TABLE `Operation_log` (
  `id` int(11) NOT NULL,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `level` tinyint(4) NOT NULL,
  `pid` int(11) NOT NULL,
  `microtime` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Operation_scriptlog`
--

CREATE TABLE `Operation_scriptlog` (
  `id` int(11) NOT NULL,
  `script` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `start` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `pid` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Player`
--

CREATE TABLE `Player` (
  `id` int(11) NOT NULL,
  `grep_id` int(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `alliance_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `rank_max` smallint(6) DEFAULT NULL,
  `rank_date` timestamp NULL DEFAULT NULL,
  `towns` mediumint(9) NOT NULL,
  `towns_max` mediumint(9) DEFAULT NULL,
  `towns_date` timestamp NULL DEFAULT NULL,
  `att` int(11) DEFAULT NULL,
  `def` int(11) DEFAULT NULL,
  `att_old` int(11) DEFAULT NULL,
  `def_old` int(11) DEFAULT NULL,
  `att_rank` int(11) DEFAULT NULL,
  `def_rank` int(11) DEFAULT NULL,
  `fight_rank` int(11) DEFAULT NULL,
  `att_rank_max` smallint(6) DEFAULT NULL,
  `def_rank_max` smallint(6) DEFAULT NULL,
  `fight_rank_max` smallint(6) DEFAULT NULL,
  `att_rank_date` timestamp NULL DEFAULT NULL,
  `def_rank_date` timestamp NULL DEFAULT NULL,
  `fight_rank_date` timestamp NULL DEFAULT NULL,
  `is_ghost` tinyint(1) DEFAULT NULL,
  `ghost_time` timestamp NULL DEFAULT NULL,
  `ghost_alliance` int(11) DEFAULT NULL,
  `att_point_date` timestamp NULL DEFAULT NULL,
  `town_point_date` timestamp NULL DEFAULT NULL,
  `heatmap` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `data_update` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Player_history`
--

CREATE TABLE `Player_history` (
  `id` int(11) NOT NULL,
  `grep_id` int(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `alliance_id` int(11) DEFAULT NULL,
  `alliance_name` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `points` int(11) NOT NULL,
  `rank` mediumint(9) NOT NULL,
  `att` int(11) NOT NULL,
  `def` int(11) NOT NULL,
  `towns` smallint(6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Player_scoreboard`
--

CREATE TABLE `Player_scoreboard` (
  `id` int(11) NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `server_time` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `overview` text COLLATE utf8_unicode_ci,
  `att` text COLLATE utf8_unicode_ci NOT NULL,
  `def` text COLLATE utf8_unicode_ci NOT NULL,
  `con` text COLLATE utf8_unicode_ci NOT NULL,
  `los` text COLLATE utf8_unicode_ci NOT NULL,
  `ghosts` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Town`
--

CREATE TABLE `Town` (
  `id` int(11) NOT NULL,
  `grep_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `island_x` smallint(6) DEFAULT NULL,
  `island_y` smallint(6) DEFAULT NULL,
  `island_i` tinyint(4) DEFAULT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `points` smallint(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `Town_ghost`
--

CREATE TABLE `Town_ghost` (
  `id` int(11) NOT NULL,
  `grep_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `island_x` smallint(6) DEFAULT NULL,
  `island_y` smallint(6) DEFAULT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `points` smallint(11) NOT NULL,
  `world` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `User`
--

CREATE TABLE `User` (
  `id` int(11) NOT NULL,
  `username` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(254) COLLATE utf8_unicode_ci NOT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `is_linked` tinyint(1) DEFAULT '0',
  `passphrase` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `role` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USER',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `World`
--

CREATE TABLE `World` (
  `id` int(11) NOT NULL,
  `grep_id` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `uid` varchar(32) DEFAULT NULL,
  `php_timezone` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UTC',
  `name` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKOWN',
  `stopped` tinyint(1) NOT NULL,
  `feature_level` tinyint(4) NOT NULL DEFAULT '0',
  `colormap` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `mapsettings` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `cleaned` tinyint(4) NOT NULL DEFAULT '0',
  `last_reset_time` timestamp NULL DEFAULT NULL,
  `grep_server_time` timestamp NULL DEFAULT NULL,
  `etag` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `World_domination`
--

CREATE TABLE `World_domination` (
  `id` int(11) NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `domination_json` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `World_map`
--

CREATE TABLE `World_map` (
  `id` int(11) NOT NULL,
  `world` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `filename` varchar(512) COLLATE utf8_unicode_ci NOT NULL,
  `zoom` smallint(6) DEFAULT NULL,
  `colormap` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `Alliance`
--
ALTER TABLE `Alliance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`grep_id`,`world`),
  ADD KEY `name` (`name`);

--
-- Indexen voor tabel `Alliance_changes`
--
ALTER TABLE `Alliance_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `world` (`world`);

--
-- Indexen voor tabel `Alliance_history`
--
ALTER TABLE `Alliance_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`grep_id`,`world`,`date`),
  ADD KEY `created_at` (`created_at`,`rank`);

--
-- Indexen voor tabel `Alliance_scoreboard`
--
ALTER TABLE `Alliance_scoreboard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`world`,`date`);

--
-- Indexen voor tabel `Conquest`
--
ALTER TABLE `Conquest`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ucCodes` (`world`,`town_id`,`time`),
  ADD KEY `time` (`time`),
  ADD KEY `player` (`world`,`n_p_id`) USING BTREE,
  ADD KEY `oldPlayer` (`world`,`o_p_id`),
  ADD KEY `alliance` (`world`,`n_a_id`) USING BTREE,
  ADD KEY `oldAlliance` (`world`,`o_a_id`);

--
-- Indexen voor tabel `Cron_status`
--
ALTER TABLE `Cron_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Discord_guild`
--
ALTER TABLE `Discord_guild`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Domination_scoreboard`
--
ALTER TABLE `Domination_scoreboard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `world` (`world`,`date`);

--
-- Indexen voor tabel `Indexer_conquest`
--
ALTER TABLE `Indexer_conquest`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Indexer_conquest_overview`
--
ALTER TABLE `Indexer_conquest_overview`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `index and conquest` (`index_key`,`conquest_id`);

--
-- Indexen voor tabel `Indexer_event`
--
ALTER TABLE `Indexer_event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team` (`index_key`,`admin_only`);

--
-- Indexen voor tabel `Indexer_info`
--
ALTER TABLE `Indexer_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_code` (`key_code`),
  ADD KEY `world` (`world`),
  ADD KEY `share` (`key_code`,`share_link`) USING BTREE;

--
-- Indexen voor tabel `Indexer_intel`
--
ALTER TABLE `Indexer_intel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique` (`town_id`,`world`,`parsed_date`,`report_type`,`v1_index`,`luck`) USING BTREE,
  ADD KEY `v1index` (`v1_index`),
  ADD KEY `palyer_search` (`player_name`),
  ADD KEY `town_search` (`town_name`),
  ADD KEY `player_intel` (`player_id`,`world`),
  ADD KEY `alliance_intel` (`alliance_id`,`world`),
  ADD KEY `conquest_id` (`conquest_id`),
  ADD KEY `hashlist` (`hash`),
  ADD KEY `world` (`world`);

--
-- Indexen voor tabel `Indexer_intel_shared`
--
ALTER TABLE `Indexer_intel_shared`
  ADD PRIMARY KEY (`id`),
  ADD KEY `index_key` (`index_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `intel_id` (`intel_id`),
  ADD KEY `hashlist` (`report_hash`),
  ADD KEY `search` (`world`,`user_id`) USING BTREE;

--
-- Indexen voor tabel `Indexer_linked`
--
ALTER TABLE `Indexer_linked`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `player` (`user_id`,`player_id`,`server`);

--
-- Indexen voor tabel `Indexer_notes`
--
ALTER TABLE `Indexer_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Indexer_owners_actual`
--
ALTER TABLE `Indexer_owners_actual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique` (`index_key`,`alliance_id`);

--
-- Indexen voor tabel `Indexer_roles`
--
ALTER TABLE `Indexer_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indexkey` (`index_key`),
  ADD KEY `user` (`user_id`);

--
-- Indexen voor tabel `Indexer_script_token`
--
ALTER TABLE `Indexer_script_token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexen voor tabel `Index_daily_report`
--
ALTER TABLE `Index_daily_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Index_overview`
--
ALTER TABLE `Index_overview`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indexkey` (`key_code`);

--
-- Indexen voor tabel `Index_owners`
--
ALTER TABLE `Index_owners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_code` (`key_code`) USING BTREE;

--
-- Indexen voor tabel `Index_stats`
--
ALTER TABLE `Index_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Island`
--
ALTER TABLE `Island`
  ADD PRIMARY KEY (`id`),
  ADD KEY `world` (`world`,`grep_id`),
  ADD KEY `world_2` (`world`,`island_x`,`island_y`);

--
-- Indexen voor tabel `Mail_jobs`
--
ALTER TABLE `Mail_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Message`
--
ALTER TABLE `Message`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Operation_log`
--
ALTER TABLE `Operation_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Operation_scriptlog`
--
ALTER TABLE `Operation_scriptlog`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `Player`
--
ALTER TABLE `Player`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ix_uq` (`grep_id`,`world`),
  ADD KEY `name` (`name`),
  ADD KEY `points` (`points`),
  ADD KEY `alliance_id` (`alliance_id`,`world`),
  ADD KEY `world` (`world`);

--
-- Indexen voor tabel `Player_history`
--
ALTER TABLE `Player_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`grep_id`,`world`,`date`),
  ADD KEY `created_at` (`created_at`,`rank`) USING BTREE;

--
-- Indexen voor tabel `Player_scoreboard`
--
ALTER TABLE `Player_scoreboard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_index` (`world`,`date`);

--
-- Indexen voor tabel `Town`
--
ALTER TABLE `Town`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ix_uq` (`grep_id`,`world`),
  ADD KEY `name` (`name`),
  ADD KEY `island_x` (`world`,`island_x`,`island_y`) USING BTREE;

--
-- Indexen voor tabel `Town_ghost`
--
ALTER TABLE `Town_ghost`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique` (`grep_id`,`world`,`player_id`) USING BTREE,
  ADD KEY `player` (`world`,`player_id`) USING BTREE;

--
-- Indexen voor tabel `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`) USING BTREE;

--
-- Indexen voor tabel `World`
--
ALTER TABLE `World`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grep_id` (`grep_id`);

--
-- Indexen voor tabel `World_domination`
--
ALTER TABLE `World_domination`
  ADD PRIMARY KEY (`id`);

--
-- Indexen voor tabel `World_map`
--
ALTER TABLE `World_map`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT voor geëxporteerde tabellen
--

--
-- AUTO_INCREMENT voor een tabel `Alliance`
--
ALTER TABLE `Alliance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=352790;
--
-- AUTO_INCREMENT voor een tabel `Alliance_changes`
--
ALTER TABLE `Alliance_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4041970;
--
-- AUTO_INCREMENT voor een tabel `Alliance_history`
--
ALTER TABLE `Alliance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37582990;
--
-- AUTO_INCREMENT voor een tabel `Alliance_scoreboard`
--
ALTER TABLE `Alliance_scoreboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162862;
--
-- AUTO_INCREMENT voor een tabel `Conquest`
--
ALTER TABLE `Conquest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84165710;
--
-- AUTO_INCREMENT voor een tabel `Cron_status`
--
ALTER TABLE `Cron_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT voor een tabel `Discord_guild`
--
ALTER TABLE `Discord_guild`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2537;
--
-- AUTO_INCREMENT voor een tabel `Domination_scoreboard`
--
ALTER TABLE `Domination_scoreboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11847;
--
-- AUTO_INCREMENT voor een tabel `Indexer_conquest`
--
ALTER TABLE `Indexer_conquest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53210;
--
-- AUTO_INCREMENT voor een tabel `Indexer_conquest_overview`
--
ALTER TABLE `Indexer_conquest_overview`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77590;
--
-- AUTO_INCREMENT voor een tabel `Indexer_event`
--
ALTER TABLE `Indexer_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24568;
--
-- AUTO_INCREMENT voor een tabel `Indexer_info`
--
ALTER TABLE `Indexer_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9055;
--
-- AUTO_INCREMENT voor een tabel `Indexer_intel`
--
ALTER TABLE `Indexer_intel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2595129;
--
-- AUTO_INCREMENT voor een tabel `Indexer_intel_shared`
--
ALTER TABLE `Indexer_intel_shared`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3691922;
--
-- AUTO_INCREMENT voor een tabel `Indexer_linked`
--
ALTER TABLE `Indexer_linked`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT voor een tabel `Indexer_notes`
--
ALTER TABLE `Indexer_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39346;
--
-- AUTO_INCREMENT voor een tabel `Indexer_owners_actual`
--
ALTER TABLE `Indexer_owners_actual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11623;
--
-- AUTO_INCREMENT voor een tabel `Indexer_roles`
--
ALTER TABLE `Indexer_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11711;
--
-- AUTO_INCREMENT voor een tabel `Indexer_script_token`
--
ALTER TABLE `Indexer_script_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28793;
--
-- AUTO_INCREMENT voor een tabel `Index_daily_report`
--
ALTER TABLE `Index_daily_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
--
-- AUTO_INCREMENT voor een tabel `Index_overview`
--
ALTER TABLE `Index_overview`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8914;
--
-- AUTO_INCREMENT voor een tabel `Index_owners`
--
ALTER TABLE `Index_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=771;
--
-- AUTO_INCREMENT voor een tabel `Index_stats`
--
ALTER TABLE `Index_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26282;
--
-- AUTO_INCREMENT voor een tabel `Island`
--
ALTER TABLE `Island`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53790001;
--
-- AUTO_INCREMENT voor een tabel `Mail_jobs`
--
ALTER TABLE `Mail_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=616;
--
-- AUTO_INCREMENT voor een tabel `Message`
--
ALTER TABLE `Message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=579;
--
-- AUTO_INCREMENT voor een tabel `Operation_log`
--
ALTER TABLE `Operation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136455510;
--
-- AUTO_INCREMENT voor een tabel `Operation_scriptlog`
--
ALTER TABLE `Operation_scriptlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1366117;
--
-- AUTO_INCREMENT voor een tabel `Player`
--
ALTER TABLE `Player`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33917350;
--
-- AUTO_INCREMENT voor een tabel `Player_history`
--
ALTER TABLE `Player_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=659009415;
--
-- AUTO_INCREMENT voor een tabel `Player_scoreboard`
--
ALTER TABLE `Player_scoreboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162885;
--
-- AUTO_INCREMENT voor een tabel `Town`
--
ALTER TABLE `Town`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=711577244;
--
-- AUTO_INCREMENT voor een tabel `Town_ghost`
--
ALTER TABLE `Town_ghost`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278085;
--
-- AUTO_INCREMENT voor een tabel `User`
--
ALTER TABLE `User`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5756;
--
-- AUTO_INCREMENT voor een tabel `World`
--
ALTER TABLE `World`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=680;
--
-- AUTO_INCREMENT voor een tabel `World_domination`
--
ALTER TABLE `World_domination`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;
--
-- AUTO_INCREMENT voor een tabel `World_map`
--
ALTER TABLE `World_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111948;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

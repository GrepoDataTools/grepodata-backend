<?php

use Symfony\Component\Routing\Route;

$oRouter = \Grepodata\Library\Router\Service::GetInstance();

// Define rate limits
$limit1PerMin = array(
  'limit' => 3,
  'window' => 3*60
);
$limit3PerMin = array(
  'limit' => 9,
  'window' => 3*60
);
$limit10PerMin = array(
  'limit' => 30,
  'window' => 3*60
);
$limit50PerMin = array(
  'limit' => 150,
  'window' => 3*60
);
$limit100PerMin = array(
  'limit' => 300,
  'window' => 3*60
);

// DEMO
//$oRouter->Add('sharecommandsupload', new Route('/commands/upload', array(
//  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Commands',
//  '_method'     => 'UploadCommands'
//)));
//$oRouter->Add('sharecommandsget', new Route('/commands/get', array(
//  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Commands',
//  '_method'     => 'GetCommands'
//)));

// === AUTH
// Register
$oRouter->Add('register', new Route('/auth/register', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'Register',
  '_ratelimit'  => $limit50PerMin
)));
$oRouter->Add('confirmMail', new Route('/confirm', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'ConfirmMail',
  '_ratelimit'  => $limit10PerMin
)));
$oRouter->Add('newConfirmMail', new Route('/auth/newconfirm', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'RequestNewConfirmMail',
  '_ratelimit'  => $limit3PerMin
)));
// Verify
$oRouter->Add('verifytoken', new Route('/auth/token', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'Verify',
  '_ratelimit'  => $limit100PerMin
)));
// Refresh token
$oRouter->Add('refreshtoken', new Route('/auth/refresh', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'Refresh',
  '_ratelimit'  => $limit100PerMin
)));
// Login
$oRouter->Add('login', new Route('/auth/login', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'Login',
  '_ratelimit'  => $limit50PerMin
)));
// Forgot
$oRouter->Add('forgot', new Route('/auth/reset', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'Forgot',
  '_ratelimit'  => $limit10PerMin
)));
// Reset password
$oRouter->Add('changepassword', new Route('/auth/changepassword', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'ChangePassword',
  '_ratelimit'  => $limit10PerMin
)));
// ===

// News
$oRouter->Add('getnews', new Route('/news', array(
  '_controller' => '\Grepodata\Application\API\Route\News',
  '_method'     => 'News'
)));

// Events
//$oRouter->Add('getusereventsindexer', new Route('/events/user', array(
//  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Event',
//  '_method'     => 'GetAllByUser'
//)));
$oRouter->Add('getteameventsindexer', new Route('/events/team', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Event',
  '_method'     => 'GetAllByTeam'
)));

// Indexer V2: userscript linking
$oRouter->Add('newscriptlink', new Route('/auth/newscriptlink', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'NewScriptLink',
  '_ratelimit'  => $limit50PerMin
)));
$oRouter->Add('verifyscriptlink', new Route('/auth/verifyscriptlink', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'VerifyScriptLink'
)));
$oRouter->Add('authenticatescriptlink', new Route('/auth/authenticatescriptlink', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'AuthenticateScriptLink',
  '_ratelimit'  => $limit100PerMin
)));

// Account removal
$oRouter->Add('accountdeletemail', new Route('/auth/deleteaccount', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'DeleteAccount',
  '_ratelimit'  => $limit3PerMin
)));
$oRouter->Add('accountdeleteconfirm', new Route('/auth/deleteaccountconfirm', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'DeleteAccountConfirmed',
  '_ratelimit'  => $limit3PerMin
)));


// PROFILE
//indexes
$oRouter->Add('profileIndexes', new Route('/profile/indexes', array(
  '_controller' => '\Grepodata\Application\API\Route\Profile',
  '_method'     => 'Indexes',
  '_ratelimit'  => $limit100PerMin
)));
//linked accounts
$oRouter->Add('profileLinkedAccounts', new Route('/profile/linked', array(
  '_controller' => '\Grepodata\Application\API\Route\Profile',
  '_method'     => 'LinkedAccounts',
  '_ratelimit'  => $limit100PerMin
)));
$oRouter->Add('profileAddLinkedAccounts', new Route('/profile/addlinked', array(
  '_controller' => '\Grepodata\Application\API\Route\Profile',
  '_method'     => 'AddLinkedAccount',
  '_ratelimit'  => $limit50PerMin
)));
$oRouter->Add('profileRemoveLinkedAccounts', new Route('/profile/removelinked', array(
  '_controller' => '\Grepodata\Application\API\Route\Profile',
  '_method'     => 'RemoveLinkedAccount',
  '_ratelimit'  => $limit3PerMin
)));

// Scoreboard
$oRouter->Add('playerScoreboard', new Route('/scoreboard/player', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'PlayerScoreboard'
)));
$oRouter->Add('playerDiffs', new Route('/player/diffs', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'PlayerDiffs'
)));
$oRouter->Add('playerDiffsDay', new Route('/player/daydiffs', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'PlayerDiffsByDay'
)));
$oRouter->Add('allianceDiffsDay', new Route('/alliance/daydiffs', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'AllianceDiffsByDay'
)));
$oRouter->Add('playerDiffsHour', new Route('/scoreboard/hourdiffs', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'PlayerDiffsByHour'
)));
$oRouter->Add('allianceScoreboard', new Route('/scoreboard/alliance', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'AllianceScoreboard'
)));
$oRouter->Add('ghostsScoreboard', new Route('/scoreboard/ghosts', array(
  '_controller' => '\Grepodata\Application\API\Route\Scoreboard',
  '_method'     => 'GhostsToday'
)));

// Active worlds
$oRouter->Add('activeWorlds', new Route('/world/active', array(
  '_controller' => '\Grepodata\Application\API\Route\World',
  '_method'     => 'ActiveWorlds'
)));
$oRouter->Add('worldAllianceChanges', new Route('/world/changes', array(
  '_controller' => '\Grepodata\Application\API\Route\World',
  '_method'     => 'AllianceChanges'
)));

// Player
$oRouter->Add('playerInfo', new Route('/player/info', array(
  '_controller' => '\Grepodata\Application\API\Route\Player',
  '_method'     => 'PlayerInfo'
)));
$oRouter->Add('playerHistory', new Route('/player/history', array(
  '_controller' => '\Grepodata\Application\API\Route\Player',
  '_method'     => 'PlayerHistory'
)));
$oRouter->Add('playerGhostTowns', new Route('/player/ghosttowns', array(
  '_controller' => '\Grepodata\Application\API\Route\Player',
  '_method'     => 'GhostTowns'
)));
$oRouter->Add('playerRangeHistory', new Route('/player/rangehistory', array(
  '_controller' => '\Grepodata\Application\API\Route\Player',
  '_method'     => 'PlayerHistoryRange'
)));
$oRouter->Add('playerSearch', new Route('/player/search', array(
  '_controller' => '\Grepodata\Application\API\Route\Player',
  '_method'     => 'Search'
)));
$oRouter->Add('playerChanges', new Route('/player/changes', array(
  '_controller' => '\Grepodata\Application\API\Route\Player',
  '_method'     => 'AllianceChanges'
)));

// Alliance
$oRouter->Add('allianceInfo', new Route('/alliance/info', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'AllianceInfo'
)));
$oRouter->Add('allianceHistory', new Route('/alliance/history', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'AllianceHistory'
)));
$oRouter->Add('allianceRangeHistory', new Route('/alliance/rangehistory', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'AllianceHistoryRange'
)));
$oRouter->Add('allianceSearch', new Route('/alliance/search', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'Search'
)));
$oRouter->Add('allianceMembers', new Route('/alliance/members', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'AllianceMemberHistory'
)));
$oRouter->Add('allianceChanges', new Route('/alliance/changes', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'AllianceChanges'
)));
$oRouter->Add('allianceWars', new Route('/alliance/wars', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'Wars'
)));
$oRouter->Add('allianceMailList', new Route('/alliance/maillist', array(
  '_controller' => '\Grepodata\Application\API\Route\Alliance',
  '_method'     => 'MailList'
)));

// Conquest
$oRouter->Add('allConquests', new Route('/conquest', array(
  '_controller' => '\Grepodata\Application\API\Route\Conquest',
  '_method'     => 'AllConquests'
)));

// Message
$oRouter->Add('messageNew', new Route('/message/add', array(
  '_controller' => '\Grepodata\Application\API\Route\Message',
  '_method'     => 'AddMessage'
)));

// Discord
$oRouter->Add('setServerDiscord', new Route('/discord/set_server', array(
  '_controller' => '\Grepodata\Application\API\Route\Discord',
  '_method'     => 'SetServer'
)));
$oRouter->Add('setIndexDiscord', new Route('/discord/set_index', array(
  '_controller' => '\Grepodata\Application\API\Route\Discord',
  '_method'     => 'SetIndex'
)));
$oRouter->Add('getIndexDiscord', new Route('/discord/get_index', array(
  '_controller' => '\Grepodata\Application\API\Route\Discord',
  '_method'     => 'GetIndex'
)));
$oRouter->Add('getSettingsDiscord', new Route('/discord/guild_settings', array(
  '_controller' => '\Grepodata\Application\API\Route\Discord',
  '_method'     => 'GetSettings'
)));
$oRouter->Add('getReportByHash', new Route('/discord/hash', array(
  '_controller' => '\Grepodata\Application\API\Route\Discord',
  '_method'     => 'GetReportByHash'
)));

// Ranking
$oRouter->Add('playerRanking', new Route('/ranking/player', array(
  '_controller' => '\Grepodata\Application\API\Route\Ranking',
  '_method'     => 'PlayerRanking'
)));
$oRouter->Add('allianceRanking', new Route('/ranking/alliance', array(
  '_controller' => '\Grepodata\Application\API\Route\Ranking',
  '_method'     => 'AllianceRanking'
)));

// Towns
$oRouter->Add('playerTowns', new Route('/town/player', array(
  '_controller' => '\Grepodata\Application\API\Route\Town',
  '_method'     => 'PlayerTowns'
)));
$oRouter->Add('townSearch', new Route('/town/search', array(
  '_controller' => '\Grepodata\Application\API\Route\Town',
  '_method'     => 'Search'
)));

// Indexer routes (V2)
$oRouter->Add('getWorlds', new Route('/indexer/worlds', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'GetWorlds'
)));
$oRouter->Add('indexReportV2', new Route('/indexer/v2/indexreport', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Report',
  '_method'     => 'indexReport'
)));
$oRouter->Add('newIndexV2', new Route('/indexer/v2/newindex', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'NewIndex',
  '_ratelimit'  => $limit100PerMin
)));
$oRouter->Add('newShareLink', new Route('/indexer/v2/newlink', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'NewShareLink',
  '_ratelimit'  => $limit50PerMin
)));
$oRouter->Add('getIndexV2', new Route('/indexer/v2/getindex', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'GetIndex'
)));
$oRouter->Add('getUserIntel', new Route('/indexer/v2/userintel', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Intel',
  '_method'     => 'GetIntelForUser'
)));
$oRouter->Add('reportHashListV2', new Route('/indexer/v2/getlatest', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Report',
  '_method'     => 'LatestReportHashes'
)));
$oRouter->Add('exportindexintelV2', new Route('/indexer/v2/export', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Intel',
  '_method'     => 'GetAllForIndex',
  '_ratelimit'  => $limit3PerMin
)));
$oRouter->Add('gettownintelV2', new Route('/indexer/v2/town', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Intel',
  '_method'     => 'GetTown'
)));
$oRouter->Add('getplayerintelV2', new Route('/indexer/v2/player', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Browse',
  '_method'     => 'GetPlayer'
)));
$oRouter->Add('getallianceintelV2', new Route('/indexer/v2/alliance', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Browse',
  '_method'     => 'GetAlliance'
)));
$oRouter->Add('v2conquestreports', new Route('/indexer/v2/conquest', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Intel',
  '_method'     => 'GetConquestReports'
)));
$oRouter->Add('v2conquestsiegelist', new Route('/indexer/v2/siegelist', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Intel',
  '_method'     => 'GetSiegelist'
)));
$oRouter->Add('indexaddnotev2', new Route('/indexer/v2/addnote', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Notes',
  '_method'     => 'AddNote'
)));
$oRouter->Add('indexdelnotev2', new Route('/indexer/v2/delnote', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Notes',
  '_method'     => 'DeleteNote'
)));

// Indexer settings (V2)
$oRouter->Add('indexsettingsgetusers', new Route('/indexer/settings/users', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\IndexUsers',
  '_method'     => 'IndexUsers'
)));
$oRouter->Add('indexsettingsupdatename', new Route('/indexer/settings/name', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'UpdateName'
)));
$oRouter->Add('indexsettingsgetowners', new Route('/indexer/settings/owners', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\IndexOwners',
  '_method'     => 'IndexOwners'
)));
$oRouter->Add('setdeleteinteldays', new Route('/indexer/settings/deletedays', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'SetDeleteIntelDays'
)));
$oRouter->Add('setindexcontribute', new Route('/indexer/settings/contribute', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'IndexContribute'
)));
$oRouter->Add('setindexjoinv1', new Route('/indexer/settings/joinv1', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'SetIndexJoinV1'
)));
$oRouter->Add('leaveindex', new Route('/indexer/settings/leave', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'LeaveIndex'
)));

// Intel search (V2)
$oRouter->Add('searchindexplayers', new Route('/indexer/search/player', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Search',
  '_method'     => 'SearchPlayers'
)));
$oRouter->Add('searchindextowns', new Route('/indexer/search/town', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Search',
  '_method'     => 'SearchTowns'
)));
$oRouter->Add('searchindexislands', new Route('/indexer/search/island', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Search',
  '_method'     => 'SearchIslands'
)));
$oRouter->Add('searchindexusers', new Route('/indexer/search/user', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Search',
  '_method'     => 'SearchUsers'
)));

// Import uncommitted intel
$oRouter->Add('commitprevintel', new Route('/indexer/commitprevintel', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\IndexUsers',
  '_method'     => 'CommitPreviousIntel',
  '_ratelimit'  => $limit3PerMin
)));

// Import V1 index keys
$oRouter->Add('importv1keys', new Route('/migrate/importv1keys', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\IndexUsers',
  '_method'     => 'ImportV1Keys',
  '_ratelimit'  => $limit50PerMin
)));

// Join via invite link
$oRouter->Add('verifyInviteLink', new Route('/indexer/invite', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\IndexUsers',
  '_method'     => 'VerifyInviteLink',
  '_ratelimit'  => $limit100PerMin
)));

// Discord link
$oRouter->Add('discordlink', new Route('/user/discord-link', array(
  '_controller' => '\Grepodata\Application\API\Route\Discord',
  '_method'     => 'DiscordLink'
)));

// Reporting
$oRouter->Add('indexerbugreportv2', new Route('/indexer/v2/scripterror', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Reporting',
  '_method'     => 'BugReport'
)));

// google catpcha proxy
$oRouter->Add('captcha', new Route('/captcha', array(
  '_controller' => '\Grepodata\Application\API\Route\Captcha',
  '_method'     => 'Verify'
)));

// Town
$oRouter->Add('towninfo', new Route('/town', array(
  '_controller' => '\Grepodata\Application\API\Route\Town',
  '_method'     => 'GetTownInfo'
)));

// status
$oRouter->Add('analyticsindexer', new Route('/analytics/indexer', array(
  '_controller' => '\Grepodata\Application\API\Route\Status',
  '_method'     => 'IndexerUsage'
)));
$oRouter->Add('stats', new Route('/indexer/stats', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Index',
  '_method'     => 'Stats'
)));

// forum reactions
$oRouter->Add('reactionslist', new Route('/reactions/thread', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Reaction',
  '_method'     => 'ThreadReactions'
)));
$oRouter->Add('reactionsnew', new Route('/reactions/new', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Reaction',
  '_method'     => 'NewReaction'
)));
$oRouter->Add('reactionsdelete', new Route('/reactions/delete', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\Reaction',
  '_method'     => 'DeleteReaction'
)));



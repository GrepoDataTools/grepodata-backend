<?php

use Symfony\Component\Routing\Route;

$oRouter = \Grepodata\Library\Router\Service::GetInstance();

// Define rate limits
$limit3PerMin = array(
  'limit' => 3,
  'window' => 60
);
$limit10PerMin = array(
  'limit' => 10,
  'window' => 60
);
$limit50PerMin = array(
  'limit' => 50,
  'window' => 60
);
$limit100PerMin = array(
  'limit' => 100,
  'window' => 60
);

// === AUTH
// Register
$oRouter->Add('register', new Route('/auth/register', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'Register',
  '_ratelimit'  => $limit10PerMin
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
  '_ratelimit'  => $limit3PerMin
)));
// Reset password
$oRouter->Add('changepassword', new Route('/auth/changepassword', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'ChangePassword',
  '_ratelimit'  => $limit10PerMin
)));
// ===

// Indexer V2: userscript linking
$oRouter->Add('newscriptlink', new Route('/auth/newscriptlink', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'NewScriptLink',
  '_ratelimit'  => $limit50PerMin
)));
$oRouter->Add('verifyscriptlink', new Route('/auth/verifyscriptlink', array(
  '_controller' => '\Grepodata\Application\API\Route\Authentication',
  '_method'     => 'VerifyScriptLink',
  '_ratelimit'  => $limit100PerMin
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
  '_ratelimit'  => $limit10PerMin
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
  '_ratelimit'  => $limit10PerMin
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

// Import V1 index keys
$oRouter->Add('importv1keys', new Route('/migrate/importv1keys', array(
  '_controller' => '\Grepodata\Application\API\Route\IndexV2\IndexUsers',
  '_method'     => 'ImportV1Keys',
  '_ratelimit'  => $limit100PerMin
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



// === Indexer V1 (backwards compatible routes)
$oRouter->Add('indexapitownv1', new Route('/indexer/api/town', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\IndexApiV1',
  '_method'     => 'GetTown'
)));
// ===



// === Indexer V1 (deprecated)
$oRouter->Add('v1deprecatedconquestreports', new Route('/indexer/conquest', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'GetConquestReports'
)));
$oRouter->Add('v1deprecatedconquestsiegelist', new Route('/indexer/siegelist', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'GetSiegelist'
)));
$oRouter->Add('v1deprecatedisValid', new Route('/indexer/isvalid', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'IsValid'
)));
$oRouter->Add('v1deprecatedgetIndex', new Route('/indexer/getindex', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'GetIndexV1'
)));
$oRouter->Add('v1deprecatedindexownersinclude', new Route('/indexer/owner/include', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'IncludeAlliance'
)));
$oRouter->Add('v1deprecatedindexownersexclude', new Route('/indexer/owner/exclude', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'ExcludeAlliance'
)));
$oRouter->Add('v1deprecatedindexownersreset', new Route('/indexer/owner/reset', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'ResetOwners'
)));
$oRouter->Add('v1deprecatedindexerbugreportv1', new Route('/indexer/scripterror', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'BugReportDeprecated'
)));
$oRouter->Add('v1deprecatednewIndex', new Route('/indexer/newindex', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'NewIndexV1'
)));
$oRouter->Add('v1deprecatednewKey', new Route('/indexer/newkey', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'NewKeyRequest'
)));
$oRouter->Add('v1deprecatedgettownintel', new Route('/indexer/town', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'GetTown'
)));
$oRouter->Add('v1deprecatedgetplayerintel', new Route('/indexer/player', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'GetPlayer'
)));
$oRouter->Add('v1deprecatedgetallianceintel', new Route('/indexer/alliance', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'GetAlliance'
)));
$oRouter->Add('v1deprecatedcleanupRequest', new Route('/indexer/cleanup', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'CleanupRequest'
)));
$oRouter->Add('v1deprecatedforgotKey', new Route('/indexer/forgotkeys', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'ForgotKeysRequest'
)));
$oRouter->Add('v1deprecatedconfirmAction', new Route('/indexer/confirmaction', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'ConfirmAction'
)));
$oRouter->Add('v1deprecatedreportHashList', new Route('/indexer/getlatest', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'LatestReportHashes'
)));
$oRouter->Add('v1deprecatedaddReportForum', new Route('/indexer/addreport', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'AddReportFromForum'
)));
$oRouter->Add('v1deprecatedaddReportInbox', new Route('/indexer/inboxreport', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'AddReportFromInbox'
)));
$oRouter->Add('v1deprecatedindexapidelete', new Route('/indexer/delete', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'Delete'
)));
$oRouter->Add('v1deprecatedindexapiundo', new Route('/indexer/undodelete', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'DeleteUndo'
)));
$oRouter->Add('v1deprecatedindexaddnote', new Route('/indexer/addnote', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'AddNote'
)));
$oRouter->Add('v1deprecatedindexdelnote', new Route('/indexer/delnote', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'DeleteNote'
)));
$oRouter->Add('v1deprecatedindexunitinfo', new Route('/indexer/movementspeed', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'CalculateRuntime'
)));
$oRouter->Add('v1deprecatedresetOwners', new Route('/indexer/resetowners', array(
  '_controller' => '\Grepodata\Application\API\Route\Indexer\DeprecatedRoutesV1',
  '_method'     => 'ResetIndexOwners'
)));
// ===

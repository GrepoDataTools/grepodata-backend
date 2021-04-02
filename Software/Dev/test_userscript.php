<?php
require(__DIR__ . '/../config.php');

$oWorld = \Grepodata\Library\Controller\World::getWorldById('nl84');
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript('', $oWorld, false, false);

$index = '97wet2s2';

\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld);
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld, false, true);
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld, true, true);
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld);

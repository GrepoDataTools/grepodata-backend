<?php
require(__DIR__ . '/../config.php');

$oWorld = \Grepodata\Library\Controller\World::getWorldById('nl78');
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript('', $oWorld, false, false);

$index = '';

\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld, false, false);
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld, false, true);
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld, true, true);
//\Grepodata\Library\Indexer\IndexBuilder::createUserscript($index, $oWorld);
<?php
require(__DIR__ . '/../config.php');

$oWorld = \Grepodata\Library\Controller\World::getWorldById('nl78');
\Grepodata\Library\Indexer\IndexBuilder::createUserscript('', $oWorld);
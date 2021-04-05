<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\World;

class IndexBuilder
{

  public function __construct() {
  }

  public static function createUserscript($key, World $oWorld, $version = USERSCRIPT_VERSION) {
    try {
      $Encrypted = md5($key);

      // Script
      $oSmarty = new \Grepodata\Library\Helper\Smarty();
      $oSmarty->assign('world', $oWorld->grep_id);
      $oSmarty->assign('key', $key);
      $oSmarty->assign('encrypted', $Encrypted);
      $oSmarty->assign('version', $version);
      $Script = $oSmarty->fetch('cityindexer_v2.tpl');
      $Result = file_put_contents(USERSCRIPT_INDEXER . '/cityindexer_'.$Encrypted.'.user.js', $Script);
      if (!$Result) throw new \Exception("Unable to save cityindexer to disk");

      return true;
    } catch (\Exception $e) {
      Logger::error("Error creating indexer userscript for key " . $key . ". Message: " . $e->getMessage());
      return false;
    }
  }
}




?>

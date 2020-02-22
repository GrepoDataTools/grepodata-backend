<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;

class IndexBuilder
{

    public function __construct() {
    }

    public static function buildNewIndex($World, $Mail) {
      // Find a new key
      $NewIndexKey = self::generateIndexKey(8);
      while (self::indexExists($NewIndexKey)) {
          $NewIndexKey = self::generateIndexKey(8);
      }

      // Insert new index
      $oIndex = new IndexInfo();
      $oIndex->key_code = $NewIndexKey;
      $oIndex->world = $World;
      $oIndex->mail = $Mail;
      $oIndex->status = 'active';
      $Result = $oIndex->save();

      if ($Result) {
        try {
          self::createUserscript($oIndex->key_code, $oIndex->world);
        } catch (\Exception $e) {
          Logger::error("Critical error while creating new userscript for index " . $NewIndexKey);
        }
        return $oIndex;
      }
      return false;
    }

    public static function generateIndexKey($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private static function indexExists($key) {
      $oIndex = \Grepodata\Library\Controller\Indexer\IndexInfo::first($key);
      if ($oIndex === null) return false;
      return true;
    }

    public static function createUserscript($key, $world, $version = USERSCRIPT_VERSION) {
      try {
        $Encrypted = md5($key);

        // Script indexer
        $oSmarty = new \Grepodata\Library\Helper\Smarty();
        $oSmarty->assign('world', $world);
        $oSmarty->assign('key', $key);
        $oSmarty->assign('encrypted', $Encrypted);
        $oSmarty->assign('version', $version);
        $Script = $oSmarty->fetch('cityindexer.tpl');
        $Result = file_put_contents(USERSCRIPT_INDEXER . '/cityindexer_'.$Encrypted.'.js', $Script);
        if (!$Result) throw new \Exception();

        // Script loader
        $oSmartyLoader = new \Grepodata\Library\Helper\Smarty();
        $oSmartyLoader->assign('world', $world);
        $oSmartyLoader->assign('key', $key);
        $oSmartyLoader->assign('encrypted', $Encrypted);
        $oSmartyLoader->assign('version', $version);
        $ScriptLoader = $oSmartyLoader->fetch('loadscript.tpl');
        $ResultLoader = file_put_contents(USERSCRIPT_LOADER . '/cityindexer_'.$Encrypted.'.user.js', $ScriptLoader);
        if (!$ResultLoader) throw new \Exception();

        return true;
      } catch (\Exception $e) {
        Logger::error("Error creating indexer userscript for key " . $key . ". Message: " . $e->getMessage());
        return false;
      }
    }
}




?>
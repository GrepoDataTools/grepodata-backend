<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\IndexInfo;
use Grepodata\Library\Model\World;

class IndexBuilderV2
{

  public function __construct() {
  }

  public static function buildNewIndex($World, $IndexName, $UserId) {
    // Find a new key
    $NewIndexKey = self::generateIndexKey(8);
    while (self::indexExists($NewIndexKey)) {
        $NewIndexKey = self::generateIndexKey(8);
    }

    // Insert new index
    $oIndex = new IndexInfo();
    $oIndex->key_code = $NewIndexKey;
    $oIndex->index_name = $IndexName;
    $oIndex->created_by_user = $UserId;
    $oIndex->world = $World;
    $oIndex->mail = '';
    $oIndex->status = 'active';
    $oIndex->share_link = self::generateIndexKey(10);
    $oIndex->script_version = USERSCRIPT_VERSION;
    $oIndex->save();

    return $oIndex;
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

  /**
   * @deprecated one script to rule them all: userscripts are no longer created on a per-index basis
   * @param $key
   * @param World $oWorld
   * @param bool $bObfuscate
   * @param bool $bMask
   * @param string $version
   * @return bool
   */
  public static function createUserscript($key, World $oWorld, $bObfuscate = true, $bMask = true, $version = USERSCRIPT_VERSION) {

    try {
      $Encrypted = md5($key);
      $Mask = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, rand(1,4)).substr(md5(IndexBuilder::generateIndexKey(32).time()), 0, rand(4,12));
      if (empty($oWorld->uid)) {
        $oWorld->uid = $Mask;
        $oWorld->save();
      }

      // Source
      $oSource = new \Grepodata\Library\Helper\Smarty();
      $oSource->assign('world', $oWorld->grep_id);
      $oSource->assign('globals', $oWorld->uid);
      $oSource->assign('key', $key);
      $oSource->assign('encrypted', $Encrypted);
      $oSource->assign('version', $version);
      $Source = $oSource->fetch('source.tpl');

      if ($bMask) {
        $Source = str_replace('globals_', $oWorld->uid, $Source);
        $Source = str_replace('gd_', $Mask, $Source);
        $Source = str_replace($Mask . 'city_indexer_', 'gd_city_indexer_', $Source);
      }

      if ($bObfuscate == true) {
        $SourceFilename = USERSCRIPT_TEMP . '/source_'.$Encrypted.'.js';
        $SourceFile = file_put_contents($SourceFilename, $Source);
        if (!$SourceFile || !file_exists($SourceFilename)) throw new \Exception("Unable to save source to disk");

        $ObfuscatorExec = OBFUSCATOR_EXEC;
        $ObsFilename = USERSCRIPT_TEMP . '/obs_'.$Encrypted.'.js';
        $Options = '';
        if (!bDevelopmentMode) shell_exec(". /home/vps/.nvm/nvm.sh");
        $result = shell_exec("$ObfuscatorExec $SourceFilename --output $ObsFilename $Options 2>&1");
        if (!file_exists($ObsFilename)) {
          throw new \Exception("Unable to obfuscate source [".$SourceFilename."]. result: " . json_encode($result));
        }
        unlink($SourceFilename);
        $SourceObs = file_get_contents($ObsFilename);
        unlink($ObsFilename);
        if ($SourceObs == false) {
          throw new \Exception("Unable to read obs file");
        }
      } else {
        $SourceObs = $Source;
      }

      // Script
      $oSmarty = new \Grepodata\Library\Helper\Smarty();
      $oSmarty->assign('world', $oWorld->grep_id);
      $oSmarty->assign('key', $key);
      $oSmarty->assign('encrypted', $Encrypted);
      $oSmarty->assign('source', $SourceObs);
      $oSmarty->assign('version', $version);
      $Script = $oSmarty->fetch('cityindexer.tpl');
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
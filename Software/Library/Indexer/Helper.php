<?php

namespace Grepodata\Library\Indexer;

use DOMDocument;
use DOMXPath;
use Grepodata\Library\Exception\ParserDefaultWarning;
use Grepodata\Library\Logger\Logger;
use Grepodata\Library\Model\Indexer\City;
use Grepodata\Library\Model\Indexer\Report;

class Helper
{

  private static function parseElement($JsonElement) {
    if (!isset($JsonElement['type']) || in_array($JsonElement['type'], ['SCRIPT', '#comment'])) {
      return '';
    }

    // Open tag + attributes
    $html = '<'.strtolower($JsonElement['type']);
    if (isset($JsonElement['attributes'])) {
      foreach ($JsonElement['attributes'] as $key => $value) {
        if ($key === 'src' && strpos($value, '/images/game/towninfo/') === 0) {
          $offset = strpos($value, '/towninfo/');
          $value = 'http://api-grepodata-com.debugger:8080/images' . substr($value, $offset);
        }
        $html .= ' ' . $key . '="' . $value . '"';
      }
    }
    $html .= '>';

    // Content
    if (isset($JsonElement['content'])) {
      if (is_array($JsonElement['content'])) {
        foreach ($JsonElement['content'] as $Content) {
          if (is_array($Content)) {
            $html .= self::parseElement($Content);
          } else {
            $html .= $Content;
          }
        }
      } else {
        $html .= $JsonElement['content'];
      }
    }

    // Close tag
    $html .= '</'.strtolower($JsonElement['type']).'>';
    return $html;
  }

  public static function JsonToHtml(Report $Report, $bMinimal=false)
  {
    $Json = json_decode($Report->report_json, true);
    $Type = $Report->type;
    $innerhtml = self::parseElement($Json);

    // Wrapper
    if ($Type === 'inbox') {
      $html = '<div class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable js-window-main-container" tabindex="-1" style="outline: 0px; z-index: 1001; height: auto; width: 800px;left: '.($bMinimal?'0':'624px').'; display: block; position: relative;" role="dialog" aria-labelledby="ui-id-17">'.
        '<div class="gpwindow_frame ui-dialog-content ui-widget-content" scrolltop="0" scrollleft="0" style="display: block; width: auto; min-height: 0px;">'.
        '<div id="gpwnd_1013" class="gpwindow_content">'.
        $innerhtml.
        '</div></div></div>';
    } else {
      $header = '<div class="content" style="width: 50%;margin-left: 25%;">';
      if ($bMinimal) {
        $header = '<div class="content" style="width: 800px">';
      }
      $html =
        $header.
        $innerhtml.
        '</div>';
    }

    return $html;
  }

  /**
   * Render the report json, if available, into an image and return the url of the image
   * @param $html
   * @param Report $oReport
   * @param $Hash
   * @return string Image url
   * @throws \Exception
   */
  public static function reportToImage($html, Report $oReport, $Hash)
  {
    // Fix domain to local
    $html = str_replace('https://gpnl.innogamescdn.com/images/game/', '../images/', $html);
    $html = str_replace('https://gpnl.innogamescdn.com/', '../', $html);
    $html = str_replace('http://api-grepodata-com.debugger:8080/', '../', $html);
    $html = str_replace('src="/images/', 'src="../images/', $html);

    $completeHtml = "
    <html>
      <head>
        <meta charset=\"UTF-8\" />
        <link rel=\"stylesheet\" type=\"text/css\" href=\"game_local.css\">
        <script src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js\"></script>
        <style>
          .game_arrow_right {display: none !important;}
          .game_arrow_left {display: none !important;}
          .game_arrow_delete {display: none !important;}
          .published_report {margin: 0 !important;}
          .game_list_footer {height: 16px !important; background-size: unset !important; padding-top: 3px !important;}
          .game_border {margin: 2px !important;}
          #report_game_body {width: 796px !important;}
          #report_date {margin: 0 !important;}
          .spy_success_left_align {
              text-align: left;
              margin-left: 10px;
              display: inline-block;
              padding-bottom: 10px;
          }
        </style>
      </head>
      <body>
       $html
      </body>
    </html>";

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($completeHtml);
    $domx = new DOMXPath($dom);

    if ($oReport->type === 'default') {
      // remove forum index+ button
      foreach($domx->query('//div[contains(attribute::class, "gd_indexer_footer")]') as $e ) {
        $e->parentNode->removeChild($e);
      }
      // remove forum footer entirely
      foreach($domx->query('//div[contains(attribute::class, "fight_report_classic")]/div[last()]') as $e ) {
        $e->parentNode->removeChild($e);
      }
    } else if ($oReport->type === 'inbox') {
      // Remove inbox footer contents except for the date
      foreach($domx->query('//div[contains(attribute::class, "game_list_footer")]/*[not(contains(@id, "report_date"))]') as $e ) {
        $e->parentNode->removeChild($e);
      }
    }

    // Build html
    $finalHtml = $dom->saveHTML($domx->document);
    $tempFile = HASH2IMG_DIRECTORY . '/temp_' . $Hash . '.html';
    if (!file_exists(HASH2IMG_DIRECTORY . '/game_local.css')) {
      throw new \Exception("Missing hash2img working directory. check your configuration!");
    }
    file_put_contents($tempFile, $finalHtml);
    if (!file_exists($tempFile)) {
      throw new \Exception("Unable to create temp html file for report. Check your permissions!");
    }

    // Call wkhtmltoimage
    $imgName = "report_".$Hash."_".rand(1,1000000).".png";
    $url = FRONTEND_URL . "/r/" . $imgName;
    $imgFile = REPORT_DIRECTORY . $imgName;
    $options = '--quality 80 --width 800 --zoom 1 --transparent --load-media-error-handling ignore';
    $result = shell_exec("wkhtmltoimage $options $tempFile $imgFile 2>&1");

    $wkhtmlresult = json_encode($result);
    if (strpos($wkhtmlresult, 'Failed to load file') !== false) {
      Logger::warning("wkhtmltoimagez result [$url]: " . json_encode($result));
    } else {
      Logger::silly("wkhtmltoimage result [$url]: " . json_encode($result));
    }

    // TODO: handle wkhtmltoimage result
    //$aErrors = libxml_get_errors();

    try {
      // try to cleanup temp html file
      unlink($tempFile);
    } catch (\Exception $e) {}

    return $url;
  }

  /**
   * Returns the text content of the input node
   * @param $Element array Input node from forum or inbox parsers
   * @param int $Depth
   * @param bool $bOnlyParseContent
   * @return string text content
   * @throws ParserDefaultWarning
   */
  public static function getTextContent($Element, $Depth=0, $bOnlyParseContent = False)
  {
    if (!is_array($Element)) {
      throw new ParserDefaultWarning("Element must be array");
    }

    if ($Depth > 100) {
      throw new ParserDefaultWarning("Maximum search depth reached for element");
    }

    $iterator = $Element;
    if (key_exists('content', $Element)) {
      $iterator = $Element['content'];
    } elseif ($bOnlyParseContent === true) {
      return '';
    }

    $textContent = array();
    foreach ($iterator as $Child) {
      if (is_array($Child)) {
        $textContent[] = self::getTextContent($Child, $Depth+1, $bOnlyParseContent);
      } else if (is_string($Child)) {
        $textContent[] = $Child;
      }
    }

    return join(" ", $textContent);
  }

  /**
   * Returns the value of the specified attribute recursively
   * @param $Element array Input node from forum or inbox parsers
   * @param int $Depth
   * @param bool $bOnlyParseContent
   * @return string text content
   * @throws ParserDefaultWarning
   */
  public static function getAttributeValue($Element, $Attribute, $Depth=0, $bOnlyParseContent = False)
  {
    if (!is_array($Element)) {
      throw new ParserDefaultWarning("Element must be array");
    }

    if ($Depth > 100) {
      throw new ParserDefaultWarning("Maximum search depth reached for element");
    }

    $textContent = array();
    foreach ($Element as $Key => $Child) {
      if (is_array($Child)) {
        $textContent[] = self::getAttributeValue($Child, $Attribute, $Depth+1, $bOnlyParseContent);
      } else if (is_string($Child) && $Key === $Attribute) {
        $textContent[] = $Child;
      }
    }

    return join(" ", $textContent);
  }

  /**
   * Returns all elements with the given class
   * @param $aParentElement
   * @param $ClassName
   * @return array
   * @throws ParserDefaultWarning
   */
  public static function allByClass($aParentElement, $ClassName)
  {
    $aElements = array();
    if (!is_array($aParentElement)) {
      return $aElements;
    }

    if (isset($aParentElement['attributes']['class']) && strpos($aParentElement['attributes']['class'], $ClassName) !== false) {
      $aElements[] = $aParentElement;
    }

    if (key_exists('content', $aParentElement)) {
      foreach ($aParentElement['content'] as $Child) {
        if (is_array($Child)) {
          $aChildElementsMatched = self::allByClass($Child, $ClassName);
          $aElements = array_merge($aElements, $aChildElementsMatched);
        }
      }
    }

    return $aElements;
  }

}
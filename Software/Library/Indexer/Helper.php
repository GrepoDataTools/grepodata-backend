<?php

namespace Grepodata\Library\Indexer;

use Grepodata\Library\Exception\ParserDefaultWarning;
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

  public static function JsonToHtml(Report $Report)
  {
    $Json = json_decode($Report->report_json, true);
    $Type = $Report->type;
    $innerhtml = self::parseElement($Json);

    // Wrapper
    if ($Type === 'inbox') {
      $html = '<div class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable js-window-main-container" tabindex="-1" style="outline: 0px; z-index: 1001; height: auto; width: 800px;left: 624px; display: block; position: relative;" role="dialog" aria-labelledby="ui-id-17">'.
        '<div class="gpwindow_frame ui-dialog-content ui-widget-content" scrolltop="0" scrollleft="0" style="display: block; width: auto; min-height: 0px;">'.
        '<div id="gpwnd_1013" class="gpwindow_content">'.
        $innerhtml.
        '</div></div></div>';
    } else {
      $html = '<div class="content" style="width: 50%;margin-left: 25%;">'.
        $innerhtml.
        '</div>';
    }

    // Fix domain
    $html = str_replace('https://gpnl.innogamescdn.com/images/game/', 'http://api-grepodata-com.debugger:8080/images/', $html);
    $html = str_replace('https://gpnl.innogamescdn.com/', 'http://api-grepodata-com.debugger:8080/', $html);

    return $html;
  }

  /**
   * Returns the text content of the input node
   * @param $Element array Input node from forum or inbox parsers
   * @return string text content
   * @throws ParserDefaultWarning
   */
  public static function getTextContent($Element, $Depth=0)
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
    }

    $textContent = array();
    foreach ($iterator as $Child) {
      if (is_array($Child)) {
        $textContent[] = self::getTextContent($Child, $Depth+1);
      } else if (is_string($Child)) {
        $textContent[] = $Child;
      }
    }

    return join(" ", $textContent);
  }

}
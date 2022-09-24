// ==UserScript==
// @name         GrepoData Map Tool (Development)
// @namespace    https://grepodata.com
// @version      0.1
// @description  wip
// @author       You
// @match        https://nl97.grepolis.com/game/index?login=1&p=1388868&ts=1652470198
// @icon         https://www.google.com/s2/favicons?sz=64&domain=grepolis.com
// @grant        none
// ==/UserScript==

(function() {

    // Ensure jquery
    if (window.jQuery) {
    } else {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-2.1.4.min.js';
        script.type = 'text/javascript';
        document.getElementsByTagName('head')[0].appendChild(script);
    }

    // Your code here...
    function addMapOverlay2() {
        var map_overlay = "<div id='gd_map_overlay' style='width: 100%; height: 100%; overflow: hidden; z-index: 3;'></div>";
        $('#map').append(map_overlay);
        $('#gd_map_overlay').mousemove(function( event ) {
            var msg = "Handler2 for .mousemove() called at ";
            msg += event.pageX + ", " + event.pageY;
            console.log(msg);
        });
    }

    // Your code here...
    function addMapOverlay() {
        var map_overlay = `<div id="gd_test_tile" class="tile islandtile" style="left: 64824px; top: 63660px; background-image: url(&quot;https://apitest.grepodata.com/test_map1.png&quot;); width: 996px; height: 832px;"></div>`;
        $('#map_tiles').append(map_overlay);
        $('#gd_test_tile').mousemove(function( event ) {
            var msg = "Handler for .mousemove() called at ";
            msg += event.pageX + ", " + event.pageY;
            console.log(msg);
        });
    }

    setTimeout(addMapOverlay, 2000);

    function initMapTool() {
        var map_overlay = `<div id='gd_map_overlay_draw' style='width: 100%; height: 100%; overflow: hidden; z-index: 10000;'></div>`;
        $(document.body).append(map_overlay);
        $('#gd_map_overlay_draw').mousemove(function( event ) {
            var msg = "Handler3 for .mousemove() called at ";
            msg += event.pageX + ", " + event.pageY;
            console.log(msg);
        });
    }

    setTimeout(initMapTool, 2000);
})();
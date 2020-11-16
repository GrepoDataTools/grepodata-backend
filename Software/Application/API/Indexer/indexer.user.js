// ==UserScript==
// @name         grepodata city indexer v2
// @namespace    grepodata
// @version      1.0.0
// @author       grepodata.com
// @updateURL    https://api.grepodata.com/script/indexer.user.js
// @downloadURL	 https://api.grepodata.com/script/indexer.user.js
// @description  This script allows you to collect and share enemy city intelligence
// @include      https://*.grepolis.com/game/*
// @include      https://grepodata.com*
// @exclude      view-source://*
// @icon         https://grepodata.com/assets/images/grepodata_icon.ico
// @copyright	 2016+, grepodata.com
// @grant        none
// ==/UserScript==

(function() {
    var CustomStyleJS = document.createElement('script');
    CustomStyleJS.type = 'text/javascript';
    CustomStyleJS.src = 'https://api.grepodata.com/script/indexer.js';
    document.getElementsByTagName("head")[0].appendChild(CustomStyleJS);
    var CustomStyleCSS = document.createElement('link');
    CustomStyleCSS.rel = 'stylesheet';
    CustomStyleCSS.type = 'text/css';
    CustomStyleCSS.href = 'https://api.grepodata.com/script/indexer.css';
    document.getElementsByTagName("head")[0].appendChild(CustomStyleCSS);
    console.log("Added GrepoData City Indexer by Tamper/GreaseMonkey");
})();
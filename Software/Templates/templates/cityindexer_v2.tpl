{literal}
// ==UserScript==
// @name         grepodata city indexer {/literal}{$key}{literal}
// @namespace    grepodata
// @version      {/literal}{$version}{literal}
// @author       grepodata.com
// @updateURL    https://api.grepodata.com/userscript/cityindexer_{/literal}{$encrypted}{literal}.user.js
// @downloadURL	 https://api.grepodata.com/userscript/cityindexer_{/literal}{$encrypted}{literal}.user.js
// @description  This script allows you to easily collect enemy intelligence in your own private index
// @include      https://{/literal}{$world}{literal}.grepolis.com/game/*
// @include      https://grepodata.com*
// @exclude      view-source://*
// @icon         https://grepodata.com/assets/images/grepodata_icon.ico
// @copyright	 2016+, grepodata.com
// @grant        none
// ==/UserScript==


(function() {
var GrepoDataJS = document.createElement('script');
GrepoDataJS.type = 'text/javascript';
GrepoDataJS.src = 'https://api.grepodata.com/script/indexer.js';
document.getElementsByTagName("head")[0].appendChild(GrepoDataJS);
var GrepoDataCSS = document.createElement('link');
GrepoDataCSS.rel = 'stylesheet';
GrepoDataCSS.type = 'text/css';
GrepoDataCSS.href = 'https://api.grepodata.com/script/indexer.css';
document.getElementsByTagName("head")[0].appendChild(GrepoDataCSS);
console.log("Added GrepoData City Indexer by Tamper/GreaseMonkey");
})();

{/literal}


{literal}
// ==UserScript==
// @name         grepodata city indexer {/literal}{$key}{literal}
// @namespace    grepodata
// @version      {/literal}{$version}{literal}
// @author       grepodata.com
// @description  This script allows you to easily collect enemy intelligence in your own private index
// @include      https://{/literal}{$world}{literal}.grepolis.com/game/*
// @include      https://grepodata.com*
// @exclude      view-source://*
// @icon         https://grepodata.com/assets/images/grepodata_icon.ico
// @copyright	 2016+, grepodata.com
// ==/UserScript==

(function() {
	//setTimeout(function(){
		var CustomStyleJS = document.createElement('script');
		CustomStyleJS.type = 'text/javascript';
		CustomStyleJS.src = 'https://api.grepodata.com/v2/userscript/cityindexer_{/literal}{$encrypted}{literal}.js';
		document.getElementsByTagName("head")[0].appendChild(CustomStyleJS);
		console.log("Added GrepoData City Indexer ({/literal}{$key}{literal}) via Tampermonkey");
	//}, 500);
})();

{/literal}
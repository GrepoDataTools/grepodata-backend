{literal}
// ==UserScript==
// @name         grepodata city indexer {/literal}{$key}{literal}
// @namespace    grepodata
// @version      {/literal}{$version}{literal}
// @author       grepodata.com
// @homepage     https://grepodata.com/indexer
// @updateURL    https://api.grepodata.com/userscript/cityindexer_{/literal}{$encrypted}{literal}.user.js
// @downloadURL	 https://api.grepodata.com/userscript/cityindexer_{/literal}{$encrypted}{literal}.user.js
// @description  This script allows you to easily collect enemy intelligence in your own private index
// @include      https://*.grepolis.com/game/*
// @include      https://grepodata.com*
// @exclude      view-source://*
// @icon         https://grepodata.com/assets/images/grepodata_icon.ico
// @copyright	 2016+, grepodata.com
// @grant        none
// ==/UserScript==


(function() {
    var rand = Math.floor((Date.now()/1000)/(60*60)) + "";
    var GrepoDataJS = document.createElement('script');
    GrepoDataJS.type = 'text/javascript';
    GrepoDataJS.src = 'https://api.grepodata.com/script/indexer.js?v=' + rand;
    document.getElementsByTagName("head")[0].appendChild(GrepoDataJS);
    var GrepoDataCSS = document.createElement('link');
    GrepoDataCSS.rel = 'stylesheet';
    GrepoDataCSS.type = 'text/css';
    GrepoDataCSS.href = 'https://api.grepodata.com/script/indexer.css?v=' + rand;
    document.getElementsByTagName("head")[0].appendChild(GrepoDataCSS);
    console.log("Added GrepoData City Indexer by Tamper/GreaseMonkey");

    // Migrate V1 keys
    var key = '{/literal}{$key}{literal}';
    try {
        var storage_key = 'gd_key_list_v1'
        var keys = [];
        localStorage.setItem('gd_index_toggle_v1', '{/literal}{$key}{literal}');
        if (localStorage.getItem(storage_key)) {
            keys = JSON.parse(localStorage.getItem(storage_key));
        }
        if (!keys.includes(key)) {
            keys.push(key);
            var storage_value = JSON.stringify(keys);
            if (storage_value.length < 100) {
                localStorage.setItem(storage_key, storage_value);
            }
        }
    } catch (e) {}

})();

{/literal}


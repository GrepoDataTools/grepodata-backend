// ==UserScript==
// @name         GrepoData DEMO: command overview sharing [do not distribute]
// @author       admin@grepodata.com
// @version      1.0.0
// @updateURL    https://api.grepodata.com/script/demo_command_sharing.user.js
// @downloadURL	 https://api.grepodata.com/script/demo_command_sharing.user.js
// @description  This is a demo script, only intended for demonstration purposes. This script is not meant for distribution.
// @include      https://*.grepolis.com/game/*
// @exclude      view-source://*
// ==/UserScript==

(function() {
    // just a name for this demo session. if eventually integrated, sessions will be secured by GrepoData user login & access_token
    var demo_token = 'demo001';

    // Listen for command overview event
    function demo() {
        $(document).ajaxComplete(function (e, xhr, opt) {
            var url = opt.url.split("?"), action = "";
            if (typeof(url[1]) !== "undefined" && typeof(url[1].split(/&/)[1]) !== "undefined") {
                action = url[0].substr(5) + "/" + url[1].split(/&/)[1].substr(7);
            }
            switch (action) {
                case "/town_overviews/command_overview":
                    parseCommandOverview(xhr);
                    break;
            }
        });
    }

    function parseCommandOverview(xhr) {
        var xhr_data = JSON.parse(xhr.responseText);
        var commands = xhr_data.json.data.commands;
        console.log('parsing command overview', commands);
        $('#place_defense').find('.game_list_footer').eq(0).append('<div class="button_new" id="gd_cmd_vrvw_share" name="Share with team" style="float: right; margin: 2px; " rel="#gpwnd_1000"><div class="left"></div><div class="right"></div><div class="caption js-caption">Share with team<div class="effect js-effect"></div></div></div>');

        // Click event when user uploads their overview:
        $('#gd_cmd_vrvw_share').click(_ => {
            $('#gd_cmd_vrvw_share').find('.js-caption').eq(0).html('Uploading..');
            console.log('sharing with team');

            var data = {
                'access_token': demo_token,
                'world': demo_token,
                'commands': commands,
                'player_name': Game.player_name || '',
                'player_id': Game.player_id || 0,
                'alliance_id': Game.alliance_id || 0
            };
            $.ajax({
                url: "https://apitest.grepodata.com/commands/upload",
                data: data,
                type: 'post',
                crossDomain: true,
                dataType: 'json',
                success: function (data) {
                    $('#gd_cmd_vrvw_share').find('.js-caption').eq(0).html('Shared with team');
                },
                error: function (jqXHR, textStatus) {
                    console.log("error saving commands");
                },
                timeout: 120000
            });
        });
    }

    setTimeout(demo, 500);
})();

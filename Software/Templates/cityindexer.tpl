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

(function() { try {
    // Stop Greasemonkey execution. Only Tampermonkey can run this script
    if ('undefined' === typeof GM_info.script.author) {
        //alert("You installed the GrepoData city indexer using Greasemonkey. This does not work. Please install it using Tampermonkey and remove the script from your Greasemonkey plugin.");
        throw new Error("Stopped greasemonkey execution for grepodata city indexer. Please use Tampermonkey instead");
    }

    // Script parameters
    var gd_version = "{/literal}{$version}{literal}";
    var index_key = "{/literal}{$key}{literal}";
    var index_hash = "{/literal}{$encrypted}{literal}";
    var world = "{/literal}{$world}{literal}";

    if (window.jQuery) {
    } else {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-2.1.4.min.js';
        script.type = 'text/javascript';
        document.getElementsByTagName('head')[0].appendChild(script);
    }

    function loadCityIndex(key, globals) {

        // Globals
        var time_regex = /([0-5]\d)(:)([0-5]\d)(:)([0-5]\d)(?!.*([0-5]\d)(:)([0-5]\d)(:)([0-5]\d))/gm;
        var gd_icon = "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAXCAYAAAAV1F8QAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAYdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjAuNvyMY98AAAG0SURBVEhLYwACASA2AGIHGmGQ2SA7GGzf7oj4//5g7v/3B7L+vz+U///NVv//r9ZY/3+7K/b/683e/9/tSSTIf7M9DGhGzv8PR4r/v9uX9v/D0TKw+MdTzf9BdoAsSnm13gnEoQn+dLYLRKcAMUPBm62BYMH/f/9QFYPMfL3JE0QXQCzaFkIziz6d60FYBApvdIt07AJQ+ORgkJlfrs2DW1T9ar0jxRZJ7JkDxshiIDPf744B0dUgiwrebA8l2iJsBuISB5l5q58dREOC7u3OKJpZdHmKEsKi1xvdybIIpAamDpdFbze5ISzClrypZdGLZboIiz6d7cRrES4DibHozdYghEWfL0ygmUVvtwcjLPpwuJBmFj1ZpImw6N3uBNpZNE8ByaK9KXgtIheDzHy12gJuUfG7falYLSIHI5sBMvPlCiMQXQy2CFQPoVtEDQwy88VScByBLSqgpUVQH0HjaH8GWJAWGFR7A2mwRSkfjlUAM1bg/9cbXMAVFbhaBib5N9uCwGxQdU2ID662T9aDMag5AKrOQVX9u73JIIvANSyoPl8CxOdphEFmg9sMdGgFMQgAAH4W0yWXhEbUAAAAAElFTkSuQmCC')";

        // Settings
        var gd_settings = {
            inbox: true,
            forum: true,
            stats: true,
            keys_enabled: true,
            key_inbox_prev: '[',
            key_inbox_next: ']',
        };
        readSettingsCookie();
        setTimeout(function () {
            loadIndexHashlist(false);
        }, 1000);

        // Set locale
        var translate = {
            ADD: 'Index',
            SEND: 'sending..',
            ADDED: 'Indexed',
            VIEW: 'View intel',
            STATS_LINK: 'Show buttons that link to player/alliance statistics on grepodata.com',
            STATS_LINK_TITLE: 'Link to statistics',
            CHECK_UPDATE: 'Check for updates',
            ABOUT: 'This tool allows you to easily collects enemy city intelligence and add them to your very own private index that can be shared with your alliance',
            INDEX_LIST: 'You are currently contributing intel to the following indexes',
            COUNT_1: 'You have contributed ',
            COUNT_2: ' reports in this session',
            SHORTCUTS: 'Keyboard shortcuts',
            SHORTCUTS_ENABLED: 'Enable keyboard shortcuts',
            SHORTCUTS_INBOX_PREV: 'Previous report (inbox)',
            SHORTCUTS_INBOX_NEXT: 'Next report (inbox)',
            COLLECT_INTEL: 'Collecting intel',
            COLLECT_INTEL_INBOX: 'Inbox (adds an "index+" button to inbox reports)',
            COLLECT_INTEL_FORUM: 'Alliance forum (adds an "index+" button to alliance forum reports)',
            SHORTCUT_FUNCTION: 'Function',
            SAVED: 'Settings saved',
            SHARE: 'Share'
        };
        if ('undefined' !== typeof Game) {
            switch (Game.locale_lang.substring(0, 2)) {
                case 'nl':
                    translate = {
                        ADD: 'Indexeren',
                        SEND: 'bezig..',
                        ADDED: 'Geindexeerd',
                        VIEW: 'Intel bekijken',
                        STATS_LINK: 'Knoppen toevoegen die linken naar speler/alliantie statistieken op grepodata.com',
                        STATS_LINK_TITLE: 'Link naar statistieken',
                        CHECK_UPDATE: 'Controleer op updates',
                        ABOUT: 'Deze tool verzamelt informatie over vijandige steden in een handig overzicht. Rapporten kunnen geindexeerd worden in een unieke index die gedeeld kan worden met alliantiegenoten',
                        INDEX_LIST: 'Je draagt momenteel bij aan de volgende indexen',
                        COUNT_1: 'Je hebt al ',
                        COUNT_2: ' rapporten verzameld in deze sessie',
                        SHORTCUTS: 'Toetsenbord sneltoetsen',
                        SHORTCUTS_ENABLED: 'Sneltoetsen inschakelen',
                        SHORTCUTS_INBOX_PREV: 'Vorige rapport (inbox)',
                        SHORTCUTS_INBOX_NEXT: 'Volgende rapport (inbox)',
                        COLLECT_INTEL: 'Intel verzamelen',
                        COLLECT_INTEL_INBOX: 'Inbox (voegt een "index+" knop toe aan inbox rapporten)',
                        COLLECT_INTEL_FORUM: 'Alliantie forum (voegt een "index+" knop toe aan alliantie forum rapporten)',
                        SHORTCUT_FUNCTION: 'Functie',
                        SAVED: 'Instellingen opgeslagen',
                        SHARE: 'Delen'
                    };
                    break;
                default:
                    break;
            }
        }

		// Scan for inbox reports
		function parseInbox() {
			if (gd_settings.inbox === true) {
				parseInboxReport();
			}
		}
		setInterval(parseInbox, 500);

        // Listen for game events
        $(document).ajaxComplete(function (e, xhr, opt) {
            var url = opt.url.split("?"), action = "";
            if (typeof(url[1]) !== "undefined" && typeof(url[1].split(/&/)[1]) !== "undefined") {
                action = url[0].substr(5) + "/" + url[1].split(/&/)[1].substr(7);
            }
            switch (action) {
                case "/report/view":
                    // Parse reports straight from inbox
                    parseInbox();
                    break;
                case "/town_info/info":
                    viewTownIntel(xhr);
                    break;
                case "/message/view": // catch inbox previews
                case "/message/preview": // catch inbox messages
                case "/alliance_forum/forum": // catch forum messages
                    // Parse reports from forum and messages
                    if (gd_settings.forum === true) {
                        setTimeout(parseForumReport, 200);
                    }
                    break;
                case "/player/index":
                    settings();
                    break;
                case "/player/get_profile_html":
                case "/alliance/profile":
                    linkToStats(action, opt);
                    break;
            }
        });

        function readSettingsCookie() {
            var settingsJson = localStorage.getItem('gd_city_indexer_s');
            if (settingsJson != null) {
                result = JSON.parse(settingsJson);
                if (result != null) {
                    result.forum = result.forum === false ? false : true;
                    result.inbox = result.inbox === false ? false : true;
                    if (!('stats' in result)) {
                        result.stats = true;
                    }
                    gd_settings = result;
                }
            }
        }

        // settings btn
        var gdsettings = false;
        $('.gods_area').append('<div class="btn_settings circle_button gd_settings_icon" style="right: 0px; top: 95px; z-index: 10;">\n' +
            '\t<div style="margin: 7px 0px 0px 4px; width: 24px; height: 24px;">\n' +
            '\t<svg aria-hidden="true" data-prefix="fas" data-icon="university" class="svg-inline--fa fa-university fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: #18bc9c; width: 18px;"><path fill="currentColor" d="M496 128v16a8 8 0 0 1-8 8h-24v12c0 6.627-5.373 12-12 12H60c-6.627 0-12-5.373-12-12v-12H24a8 8 0 0 1-8-8v-16a8 8 0 0 1 4.941-7.392l232-88a7.996 7.996 0 0 1 6.118 0l232 88A8 8 0 0 1 496 128zm-24 304H40c-13.255 0-24 10.745-24 24v16a8 8 0 0 0 8 8h464a8 8 0 0 0 8-8v-16c0-13.255-10.745-24-24-24zM96 192v192H60c-6.627 0-12 5.373-12 12v20h416v-20c0-6.627-5.373-12-12-12h-36V192h-64v192h-64V192h-64v192h-64V192H96z"></path></svg>\n' +
            '\t</div>\n' +
            '<span class="indicator" id="gd_index_indicator" data-indicator-id="indexed" style="background: #182B4D;display: none;z-index: 10000; position: absolute;bottom: 18px;right: 0px;border: solid 1px #ffca4c; height: 12px;color: #fff;font-size: 9px;border-radius: 9px;padding: 0 3px 1px;line-height: 13px;font-weight: 400;">0</span>' +
            '</div>');
        $('.gd_settings_icon').click(function () {
            if (!GPWindowMgr.getOpenFirst(Layout.wnd.TYPE_PLAYER_SETTINGS)) {
                gdsettings = true;
            }
            Layout.wnd.Create(GPWindowMgr.TYPE_PLAYER_SETTINGS, 'Settings');
            setTimeout(function () {
                gdsettings = false
            }, 5000)
        });
        $('.gd_settings_icon').tooltip('GrepoData City Indexer ' + key);

        // report info is converted to a 32 bit hash to be used as unique id
        String.prototype.report_hash = function () {
            var hash = 0, i, chr;
            if (this.length === 0) return hash;
            for (i = 0; i < this.length; i++) {
                chr = this.charCodeAt(i);
                hash = ((hash << 5) - hash) + chr;
                hash |= 0;
            }
            return hash;
        };

        // Add the given forum report to the index
        function addToIndexFromForum(reportId, reportElement, reportPoster, reportHash) {
            var reportJson = JSON.parse(mapDOM(reportElement, true));
            var reportText = reportElement.innerText;

            var data = {
                'key': globals.gdIndexScript,
                'type': 'default',
                'report_hash': reportHash || '',
                'report_text': reportText,
                'report_json': reportJson,
                'script_version': gd_version,
                'report_poster': reportPoster,
                'report_poster_id': gd_w.Game.player_id || 0
            };

            $('.rh' + reportHash).each(function () {
                $(this).css("color", '#36cd5b');
                $(this).find('.middle').get(0).innerText = translate.ADDED + ' ✓';
                $(this).off("click");
            });
            $.ajax({
                url: "https://api.grepodata.com/indexer/addreport",
                data: data,
                type: 'post',
                crossDomain: true,
                dataType: 'json',
                success: function (data) {
                },
                error: function (jqXHR, textStatus) {
                    console.log("error saving forum report");
                },
                timeout: 120000
            });
            pushForumHash(reportHash);
            gd_indicator();
        }

        // Add the given inbox report to the index
        function addToIndexFromInbox(reportHash, reportElement) {
            var reportJson = JSON.parse(mapDOM(reportElement, true));
            var reportText = reportElement.innerText;

            var data = {
                'key': globals.gdIndexScript,
                'type': 'inbox',
                'report_hash': reportHash,
                'report_text': reportText,
                'report_json': reportJson,
                'script_version': gd_version,
                'report_poster': gd_w.Game.player_name || '',
                'report_poster_id': gd_w.Game.player_id || 0,
                'report_poster_ally_id': gd_w.Game.alliance_id || 0,
            };

            if (gd_settings.inbox === true) {
                var btn = document.getElementById("gd_index_rep_txt");
                var btnC = document.getElementById("gd_index_rep_");
                btnC.setAttribute('style', 'color: #36cd5b; float: right;');
                btn.innerText = translate.ADDED + ' ✓';
            }
            $.ajax({
                url: "https://api.grepodata.com/indexer/inboxreport",
                data: data,
                type: 'post',
                crossDomain: true,
                success: function (data) {
                },
                error: function (jqXHR, textStatus) {
                    console.log("error saving inbox report");
                },
                timeout: 120000
            });
            pushInboxHash(reportHash);
            gd_indicator();
        }

        function pushInboxHash(hash) {
            if (globals.reportsFoundInbox === undefined) {
                globals.reportsFoundInbox = [];
            }
            globals.reportsFoundInbox.push(hash);
        }

        function pushForumHash(hash) {
            if (globals.reportsFoundForum === undefined) {
                globals.reportsFoundForum = [];
            }
            globals.reportsFoundForum.push(hash);
        }

        function mapDOM(element, json) {
            var treeObject = {};

            // If string convert to document Node
            if (typeof element === "string") {
                if (window.DOMParser) {
                    parser = new DOMParser();
                    docNode = parser.parseFromString(element, "text/xml");
                } else { // Microsoft strikes again
                    docNode = new ActiveXObject("Microsoft.XMLDOM");
                    docNode.async = false;
                    docNode.loadXML(element);
                }
                element = docNode.firstChild;
            }

            //Recursively loop through DOM elements and assign properties to object
            function treeHTML(element, object) {
                object["type"] = element.nodeName;
                var nodeList = element.childNodes;
                if (nodeList != null) {
                    if (nodeList.length) {
                        object["content"] = [];
                        for (var i = 0; i < nodeList.length; i++) {
                            if (nodeList[i].nodeType == 3) {
                                object["content"].push(nodeList[i].nodeValue);
                            } else {
                                object["content"].push({});
                                treeHTML(nodeList[i], object["content"][object["content"].length - 1]);
                            }
                        }
                    }
                }
                if (element.attributes != null) {
                    if (element.attributes.length) {
                        object["attributes"] = {};
                        for (var i = 0; i < element.attributes.length; i++) {
                            object["attributes"][element.attributes[i].nodeName] = element.attributes[i].nodeValue;
                        }
                    }
                }
            }

            treeHTML(element, treeObject);

            return (json) ? JSON.stringify(treeObject) : treeObject;
        }

        // Inbox reports
        function parseInboxReport() {
            var reportElement = document.getElementById("report_report");
            if (reportElement != null) {
                var footerElement = reportElement.getElementsByClassName("game_list_footer")[0];
                var reportText = reportElement.outerHTML;
                var footerText = footerElement.outerHTML;
                if (footerText.indexOf('gd_index_rep_') < 0
                    && reportText.indexOf('report_town_bg_quest') < 0
                    && reportText.indexOf('support_report_cities') < 0
                    && reportText.indexOf('big_horizontal_report_separator') < 0
                    && reportText.indexOf('report_town_bg_attack_spot') < 0
                    && (reportText.indexOf('/images/game/towninfo/support.png') < 0 || reportText.indexOf('flagpole ghost_town') < 0)
                    && (reportText.indexOf('/images/game/towninfo/attack.png') >= 0
                        || reportText.indexOf('/images/game/towninfo/espionage') >= 0
                        || reportText.indexOf('/images/game/towninfo/breach.png') >= 0
                        || reportText.indexOf('/images/game/towninfo/attackSupport.png') >= 0
                        || reportText.indexOf('/images/game/towninfo/take_over.png') >= 0
                        || reportText.indexOf('/images/game/towninfo/support.png') >= 0)
                ) {

                    // Build report hash using default method
                    var headerElement = reportElement.querySelector("#report_header");
                    var dateElement = footerElement.querySelector("#report_date");
                    var headerText = headerElement.innerText;
                    var dateText = dateElement.innerText;
                    var hashText = headerText + dateText;

                    // Try to build report hash using town ids (robust against object name changes)
                    try {
                        var towns = headerElement.getElementsByClassName('town_name');
                        if (towns.length === 2) {
                            var ids = [];
                            for (var m = 0; m < towns.length; m++) {
                                var href = towns[m].getElementsByTagName("a")[0].getAttribute("href");

                                // Remove hashtag prefix
                                if (href.slice(0, 1) === '#') {
                                    href = href.slice(1);
                                }
                                // Remove trailing =
                                for (var g = 0; g < 10; g++) {
                                    if (href.slice(href.length - 1) === '=') {
                                        href = href.slice(0, href.length - 1)
                                    }
                                }

                                var townData = atob(href);
                                var townJson = JSON.parse(townData);
                                ids.push(townJson.id);
                            }
                            if (ids.length === 2) {
                                ids.push(dateText); // Add date to report info
                                hashText = ids.join('');
                            }
                        }
                    } catch (e) {
                        console.log(e);
                    }

                    // Try to parse units and buildings
                    var reportUnits = reportElement.getElementsByClassName('unit_icon40x40');
                    var reportBuildings = reportElement.getElementsByClassName('report_unit');
                    var reportContent = '';
                    try {
                        for (var u = 0; u < reportUnits.length; u++) {
                            reportContent += reportUnits[u].outerHTML;
                        }
                        for (var u = 0; u < reportBuildings.length; u++) {
                            reportContent += reportBuildings[u].outerHTML;
                        }
                    } catch (e) {
                        console.log("Unable to parse inbox report units: ", e);
                    }
                    if (typeof reportContent === 'string' || reportContent instanceof String) {
                        hashText += reportContent;
                    }

                    reportHash = hashText.report_hash();
                    console.log('Parsed inbox report with hash: ' + reportHash);

                    // Add index button
                    var addBtn = document.createElement('a');
                    addBtn.setAttribute('href', '#');
                    addBtn.setAttribute('id', 'gd_index_rep_');
                    addBtn.setAttribute('class', 'button gd_btn_index');
                    var styleStr = 'float: right;';
                    addBtn.setAttribute('style', styleStr);
                    var txtSpan = document.createElement('span');
                    txtSpan.setAttribute('id', 'gd_index_rep_txt');
                    txtSpan.setAttribute('style', 'min-width: 50px;');

                    var reportFound = false;
                    for (var j = 0; j < globals.reportsFoundInbox.length; j++) {
                        if (globals.reportsFoundInbox[j] === reportHash) {
                            reportFound = true;
                        }
                    }
                    if (reportFound) {
                        addBtn.setAttribute('style', 'color: #36cd5b; float: right;');
                        txtSpan.innerText = translate.ADDED + ' ✓';
                    } else {
                        txtSpan.innerText = translate.ADD + ' +';
                    }

                    txtSpan.setAttribute('class', 'middle');
                    var rightSpan = document.createElement('span');
                    rightSpan.setAttribute('class', 'right');
                    var leftSpan = document.createElement('span');
                    leftSpan.setAttribute('class', 'left');
                    rightSpan.appendChild(txtSpan);
                    leftSpan.appendChild(rightSpan);
                    addBtn.appendChild(leftSpan);
                    if (!reportFound) {
                        addBtn.addEventListener('click', function () {
                            if ($('#gd_index_rep_txt').get(0)) {
                                $('#gd_index_rep_txt').get(0).innerText = translate.SEND;
                            }
                            addToIndexFromInbox(reportHash, reportElement);
                        }, false);
                    }

                    var parentContainer = $(reportElement).closest('.gpwindow_frame').eq(0);
                    if (!parentContainer.hasClass('gd-inbox-expanded-container')) {
                        parentContainer.height(parentContainer.height() + 24);
                        parentContainer.addClass('gd-inbox-expanded-container');
                    }

                    var grepodataFooter = document.createElement('div');
                    grepodataFooter.setAttribute('id', 'gd_inbox_footer');
                    //grepodataFooter.setAttribute('style', 'display: block; position: absolute; right: 2px; bottom: 0;');

                    grepodataFooter.appendChild(addBtn);

                    var shareBtn = document.createElement('a');
                    var shareInput = document.createElement('input');
                    var rightShareSpan = document.createElement('span');
                    var leftShareSpan = document.createElement('span');
                    var txtShareSpan = document.createElement('span');
                    shareInput.setAttribute('type', 'text');
                    shareInput.setAttribute('id', 'gd_share_rep_inp');
                    shareInput.setAttribute('style', 'float: right;');
                    txtShareSpan.setAttribute('id', 'gd_share_rep_txt');
                    txtShareSpan.setAttribute('class', 'middle');
                    txtShareSpan.setAttribute('style', 'min-width: 50px;');
                    rightShareSpan.setAttribute('class', 'right');
                    leftShareSpan.setAttribute('class', 'left');
                    leftShareSpan.appendChild(rightShareSpan);
                    rightShareSpan.appendChild(txtShareSpan);
                    shareBtn.appendChild(leftShareSpan);
                    shareBtn.setAttribute('href', '#');
                    shareBtn.setAttribute('id', 'gd_share_rep_');
                    shareBtn.setAttribute('class', 'button gd_btn_share');
                    shareBtn.setAttribute('style', 'float: right;');

                    txtShareSpan.innerText = translate.SHARE;

                    shareBtn.addEventListener('click', () => {
                        if ($('#gd_share_rep_txt').get(0)) {
                            var hashI = ('r' + reportHash).replace('-', 'm');
                            var content = '<b>Share this report on Discord:</b><br><ul>' +
                                '    <li>1. Install the GrepoData bot in your Discord server (<a href="https://grepodata.com/discord" target="_blank">link</a>).</li>' +
                                '    <li>2. Insert the following code in your Discord server.<br/>The bot will then create the screenshot for you!' +
                                '    </ul><br/><input type="text" class="gd-copy-input-' + reportHash + '" value="' + `!gd report ${hashI}` + '"> <a href="#" class="gd-copy-command-' + reportHash + '">Copy to clipboard</a><span class="gd-copy-done-' + reportHash + '" style="display: none; float: right;"> Copied!</span>' +
                                '    <br /><br /><small>Thank you for using <a href="https://grepodata.com" target="_blank">GrepoData</a>!</small>';

                            Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG).setContent(content)
                            addToIndexFromInbox(reportHash, reportElement);

                            $(".gd-copy-command-" + reportHash).click(function () {
                                console.log('copy to clip');
                                $(".gd-copy-input-" + reportHash).select();
                                document.execCommand('copy');

                                $('.gd-copy-done-' + reportHash).get(0).style.display = 'block';
                                setTimeout(function () {
                                    if ($('.gd-copy-done-' + reportHash).get(0)) {
                                        $('.gd-copy-done-' + reportHash).get(0).style.display = 'none';
                                    }
                                }, 3000);
                            });
                        }
                    });

                    grepodataFooter.appendChild(shareBtn)
                    footerElement.appendChild(grepodataFooter);

                    // Figure out button placement..
                    var folderElement = footerElement.querySelector('#select_folder_id');
                    var footerWidth = footerElement.offsetWidth;
                    var dateWidth = dateElement.offsetWidth;
                    //var folderWidth = folderElement.offsetWidth;
                    //var availableWidth = footerWidth - dateWidth - folderWidth;
                    //footerElement.style.height = '47px';
                    footerElement.style.backgroundSize = 'auto 100%';
                    footerElement.style.paddingTop = '26px';
                    dateElement.style.marginTop = '-21px';
                    dateElement.style.position = 'absolute';

                    if (folderElement !== null) {
                        folderElement.style.marginTop = '-21px';
                        folderElement.style.marginLeft = (dateWidth + 5) + 'px';
                        folderElement.style.position = 'absolute';
                    }

                    // Handle inbox keyboard shortcuts
                    document.removeEventListener('keyup', inboxNavShortcut);
                    document.addEventListener('keyup', inboxNavShortcut);
                }

            }
        }

        function inboxNavShortcut(e) {
            var reportElement = document.getElementById("report_report");
            if (gd_settings.keys_enabled === true && !['textarea', 'input'].includes(e.srcElement.tagName.toLowerCase()) && reportElement !== null) {
                switch (e.key) {
                    case gd_settings.key_inbox_prev:
                        var prev = reportElement.getElementsByClassName('last_report game_arrow_left');
                        if (prev.length === 1 && prev[0] != null) {
                            document.removeEventListener('keyup', inboxNavShortcut);
                            prev[0].click();
                        }
                        break;
                    case gd_settings.key_inbox_next:
                        var next = reportElement.getElementsByClassName('next_report game_arrow_right');
                        if (next.length === 1 && next[0] != null) {
                            document.removeEventListener('keyup', inboxNavShortcut);
                            next[0].click();
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        function addForumReportById(reportId, reportHash) {
            var reportElement = document.getElementById(reportId);

            // Find report poster
            var inspectedElement = reportElement.parentElement;
            var search_limit = 20;
            var found = false;
            var reportPoster = '_';
            while (!found && search_limit > 0 && inspectedElement !== null) {
                try {
                    var owners = inspectedElement.getElementsByClassName("bbcodes_player");
                    if (owners.length !== 0) {
                        for (var g = 0; g < owners.length; g++) {
                            if (owners[g].parentElement.classList.contains('author')) {
                                reportPoster = owners[g].innerText;
                                if (reportPoster === '') reportPoster = '_';
                                found = true;
                            }
                        }
                    }
                    inspectedElement = inspectedElement.parentElement;
                }
                catch (err) {
                }
                search_limit -= 1;
            }

            addToIndexFromForum(reportId, reportElement, reportPoster, reportHash);
        }

        // Forum reports
        function parseForumReport() {
            var reportsInView = document.getElementsByClassName("bbcodes published_report");

            //process reports
            if (reportsInView.length > 0) {
                for (var i = 0; i < reportsInView.length; i++) {
                    var reportElement = reportsInView[i];
                    var reportId = reportElement.id;

                    if (!$('#gd_index_f_' + reportId).get(0)) {

                        var bSpy = false;
                        if (reportElement.getElementsByClassName("espionage_report").length > 0) {
                            bSpy = true;
                        } else if (reportElement.getElementsByClassName("report_units").length < 2
                            || reportElement.getElementsByClassName("conquest").length > 0) {
                            // ignore non intel reports
                            continue;
                        }

                        var reportHash = null;
                        try {
                            // === Build report hash to create a unique identifier for this report that is consistent between sessions
                            // Try to parse time string
                            var header = reportElement.getElementsByClassName('published_report_header bold')[0];
                            var dateText = header.getElementsByClassName('reports_date small')[0].innerText;
                            try {
                                var time = dateText.match(time_regex);
                                if (time != null) {
                                    dateText = time[0];
                                }
                            } catch (e) {
                            }

                            // Try to parse town ids from report header
                            var headerText = header.getElementsByClassName('bold')[0].innerText;
                            try {
                                var towns = header.getElementsByClassName('gp_town_link');
                                if (towns.length === 2) {
                                    var ids = [];
                                    for (var m = 0; m < towns.length; m++) {
                                        var href = towns[m].getAttribute("href");
                                        // Remove hashtag prefix
                                        if (href.slice(0, 1) === '#') {
                                            href = href.slice(1);
                                        }
                                        // Remove trailing =
                                        for (var g = 0; g < 10; g++) {
                                            if (href.slice(href.length - 1) === '=') {
                                                href = href.slice(0, href.length - 1)
                                            }
                                        }

                                        var townData = atob(href);
                                        var townJson = JSON.parse(townData);
                                        ids.push(townJson.id);
                                    }
                                    if (ids.length === 2) {
                                        headerText = ids.join('');
                                    }
                                }
                            } catch (e) {
                            }

                            // Try to parse units and buildings
                            var reportUnits = reportElement.getElementsByClassName('unit_icon40x40');
                            var reportBuildings = reportElement.getElementsByClassName('report_unit');
                            var reportDetails = reportElement.getElementsByClassName('report_details');
                            var reportContent = '';
                            try {
                                for (var u = 0; u < reportUnits.length; u++) {
                                    reportContent += reportUnits[u].outerHTML;
                                }
                                for (var u = 0; u < reportBuildings.length; u++) {
                                    reportContent += reportBuildings[u].outerHTML;
                                }
                                if (reportDetails.length === 1) {
                                    reportContent += reportDetails[0].innerText;
                                }
                            } catch (e) {
                            }

                            // Combine intel and generate hash
                            var reportText = dateText + headerText + reportContent;
                            if (reportText !== null && reportText !== '') {
                                reportHash = reportText.report_hash();
                            }

                        } catch (err) {
                            reportHash = null;
                        }
                        console.log('Parsed forum report with hash: ' + reportHash);

                        var exists = false;
                        if (reportHash !== null && reportHash !== 0) {
                            for (var j = 0; j < globals.reportsFoundForum.length; j++) {
                                if (globals.reportsFoundForum[j] == reportHash) {
                                    exists = true;
                                }
                            }
                        }

                        var shareBtn = document.createElement('a');
                        var shareInput = document.createElement('input');
                        var rightShareSpan = document.createElement('span');
                        var leftShareSpan = document.createElement('span');
                        var txtShareSpan = document.createElement('span');
                        shareInput.setAttribute('type', 'text');
                        shareInput.setAttribute('id', 'gd_share_rep_inp');
                        shareInput.setAttribute('style', 'float: right;');
                        txtShareSpan.setAttribute('id', 'gd_share_rep_txt');
                        txtShareSpan.setAttribute('class', 'middle');
                        txtShareSpan.setAttribute('style', 'min-width: 50px;');
                        rightShareSpan.setAttribute('class', 'right');
                        leftShareSpan.setAttribute('class', 'left');
                        leftShareSpan.appendChild(rightShareSpan);
                        rightShareSpan.appendChild(txtShareSpan);
                        shareBtn.appendChild(leftShareSpan);
                        shareBtn.setAttribute('href', '#');
                        shareBtn.setAttribute('id', 'gd_share_rep_');
                        shareBtn.setAttribute('class', 'button gd_btn_share');
                        shareBtn.setAttribute('style', 'float: right;');

                        txtShareSpan.innerText = translate.SHARE;


                        shareBtn.addEventListener('click', () => {
                            if ($('#gd_share_rep_txt').get(0)) {
                                var hashI = ('r' + reportHash).replace('-', 'm');
                                var content = '<b>Share this report on Discord:</b><br><ul>' +
                                    '    <li>1. Install the GrepoData bot in your Discord server (<a href="https://grepodata.com/discord" target="_blank">link</a>).</li>' +
                                    '    <li>2. Insert the following code in your Discord server.<br/>The bot will then create the screenshot for you!' +
                                    '    </ul><br/><input type="text" class="gd-copy-input-' + reportHash + '" value="' + `!gd report ${hashI}` + '"> <a href="#" class="gd-copy-command-' + reportHash + '">Copy to clipboard</a><span class="gd-copy-done-' + reportHash + '" style="display: none; float: right;"> Copied!</span>' +
                                    '    <br /><br /><small>Thank you for using <a href="https://grepodata.com" target="_blank">GrepoData</a>!</small>';

                                Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG).setContent(content);
                                addForumReportById($('#gd_index_f_' + reportId).attr('report_id'), $('#gd_index_f_' + reportId).attr('report_hash'));

                                $(".gd-copy-command-" + reportHash).click(function () {
                                    console.log('copy to clip');
                                    $(".gd-copy-input-" + reportHash).select();
                                    document.execCommand('copy');

                                    $('.gd-copy-done-' + reportHash).get(0).style.display = 'block';
                                    setTimeout(function () {
                                        if ($('.gd-copy-done-' + reportHash).get(0)) {
                                            $('.gd-copy-done-' + reportHash).get(0).style.display = 'none';
                                        }
                                    }, 3000);
                                });
                            }
                        })

                        if (reportHash == null) {
                            reportHash = '';
                        }
                        if (bSpy === true) {
                            $(reportElement).append('<div class="gd_indexer_footer" style="background: #fff; height: 28px; margin-top: -28px;">\n' +
                                '    <a href="#" id="gd_index_f_' + reportId + '" report_hash="' + reportHash + '" report_id="' + reportId + '" class="button rh' + reportHash + ' gd_btn_index" style="float: right;"><span class="left"><span class="right"><span id="gd_index_f_txt_' + reportId + '" class="middle" style="min-width: 50px;">' + translate.ADD + ' +</span></span></span></a>\n' +
                                '    </div>');
                            $(reportElement).find('.resources, .small').css("text-align", "left");
                        } else {
                            $(reportElement).append('<div class="gd_indexer_footer" style="background: url(https://gpnl.innogamescdn.com/images/game/border/odd.png); height: 28px; margin-top: -52px;">\n' +
                                '    <a href="#" id="gd_index_f_' + reportId + '" report_hash="' + reportHash + '" report_id="' + reportId + '" class="button rh' + reportHash + ' gd_btn_index" style="float: right;"><span class="left"><span class="right"><span id="gd_index_f_txt_' + reportId + '" class="middle" style="min-width: 50px;">' + translate.ADD + ' +</span></span></span></a>\n' +
                                '    </div>');
                            $(reportElement).find('.button, .simulator, .all').parent().css("padding-top", "24px");
                            $(reportElement).find('.button, .simulator, .all').siblings("span").css("margin-top", "-24px");
                        }

                        $(reportElement).find('.gd_indexer_footer').append(shareBtn);

                        if (exists === true) {
                            $('#gd_index_f_' + reportId).get(0).style.color = '#36cd5b';
                            $('#gd_index_f_txt_' + reportId).get(0).innerText = translate.ADDED + ' ✓';
                        } else {
                            $('#gd_index_f_' + reportId).click(function () {
                                addForumReportById($(this).attr('report_id'), $(this).attr('report_hash'));
                            });
                        }
                    }
                }
            }
        }

        function settings() {
            if (!$("#gd_indexer").get(0)) {
                $(".settings-menu ul:last").append('<li id="gd_li"><svg aria-hidden="true" data-prefix="fas" data-icon="university" class="svg-inline--fa fa-university fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: #2E4154;width: 16px;width: 15px;vertical-align: middle;margin-top: -2px;"><path fill="currentColor" d="M496 128v16a8 8 0 0 1-8 8h-24v12c0 6.627-5.373 12-12 12H60c-6.627 0-12-5.373-12-12v-12H24a8 8 0 0 1-8-8v-16a8 8 0 0 1 4.941-7.392l232-88a7.996 7.996 0 0 1 6.118 0l232 88A8 8 0 0 1 496 128zm-24 304H40c-13.255 0-24 10.745-24 24v16a8 8 0 0 0 8 8h464a8 8 0 0 0 8-8v-16c0-13.255-10.745-24-24-24zM96 192v192H60c-6.627 0-12 5.373-12 12v20h416v-20c0-6.627-5.373-12-12-12h-36V192h-64v192h-64V192h-64v192h-64V192H96z"></path></svg><a id="gd_indexer" href="#" style="    margin-left: 4px;">GrepoData City Indexer</a></li>');

                // Intro
                // var layoutUrl = 'https' + window.getComputedStyle(document.getElementsByClassName('icon')[0], null).background.split('("https')[1].split('"')[0];
                var settingsHtml = '<div id="gd_settings_container" style="display: none; position: absolute; top: 0; bottom: 0; right: 0; left: 232px; padding: 0px; overflow: auto;">\n' +
                    '    <div id="gd_settings" style="position: relative;">\n' +
                    '\t\t<div class="section" id="s_gd_city_indexer">\n' +
                    '\t\t\t<div class="game_header bold" style="margin: -5px -10px 15px -10px; padding-left: 10px;">GrepoData city indexer settings</div>\n' +
                    '\t\t\t<p>' + translate.ABOUT + '.</p>' +
                    '\t\t\t<p>' + translate.INDEX_LIST + ': ';
                globals.gdIndexScript.forEach(function (index, i) {
                    settingsHtml = settingsHtml + (i > 0 ? ', ' : '') + '<a href="https://grepodata.com/indexer/' + index + '" target="_blank">' + index + '</a>';
                });
                settingsHtml = settingsHtml + '</p>' + (count > 0 ? '<p>' + translate.COUNT_1 + count + translate.COUNT_2 + '.</p>' : '') +
                    '<p id="gd_s_saved" style="display: none; position: absolute; left: 50px; margin: 0;"><strong>' + translate.SAVED + ' ✓</strong></p> ' +
                    '<br/><hr>\n';

                // Forum intel settings
                settingsHtml += '\t\t\t<p style="margin-bottom: 10px; margin-left: 10px;"><strong>' + translate.COLLECT_INTEL + '</strong></p>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new inbox_gd_enabled' + (gd_settings.inbox === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.COLLECT_INTEL_INBOX + '</div>\n' +
                    '\t\t\t</div>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new forum_gd_enabled' + (gd_settings.forum === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.COLLECT_INTEL_FORUM + '</div>\n' +
                    '\t\t\t</div>\n' +
                    '\t\t\t<br><br><hr>\n';

                // Stats link
                settingsHtml += '\t\t\t<p style="margin-bottom: 10px; margin-left: 10px;"><strong>' + translate.STATS_LINK_TITLE + '</strong> <img style="background: ' + gd_icon + '; margin-top: -4px; position: absolute; margin-left: 10px; height: 23px; width: 26px; float: left;"/></p>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new stats_gd_enabled' + (gd_settings.stats === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.STATS_LINK + '</div>\n' +
                    '\t\t\t</div>\n' +
                    '\t\t\t<br><br><hr>\n';

                // Keyboard shortcut settings
                settingsHtml += '\t\t\t<p style="margin-bottom: 10px; margin-left: 10px;"><strong>' + translate.SHORTCUTS + '</strong></p>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new keys_enabled_gd_enabled' + (gd_settings.keys_enabled === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.SHORTCUTS_ENABLED + '</div>\n' +
                    '\t\t\t</div><br/><br/>\n' +
                    '\t\t\t<div class="gd_shortcut_settings" style="margin-left: 45px; margin-right: 20px; border: 1px solid black;"><table style="width: 100%;">\n' +
                    '\t\t\t\t<tr><th style="width: 50%;">' + translate.SHORTCUT_FUNCTION + '</th><th>Shortcut</th></tr>\n' +
                    '\t\t\t\t<tr><td>' + translate.SHORTCUTS_INBOX_PREV + '</td><td>' + gd_settings.key_inbox_prev + '</td></tr>\n' +
                    '\t\t\t\t<tr><td>' + translate.SHORTCUTS_INBOX_NEXT + '</td><td>' + gd_settings.key_inbox_next + '</td></tr>\n' +
                    '\t\t\t</table></div>\n' +
                    '\t\t\t<br/><hr>\n';

                // Footer
                settingsHtml += '\t\t\t<a href="https://grepodata.com/indexer/' + index_key + '" target="_blank">' + translate.VIEW + '</a>\n' +
                    '<p style="font-style: italic; font-size: 10px; float: right; margin:0px;">GrepoData city indexer v' + gd_version + ' [<a href="https://api.grepodata.com/userscript/cityindexer_' + index_hash + '.user.js" target="_blank">' + translate.CHECK_UPDATE + '</a>]</p>' +
                    '\t\t</div>\n' +
                    '    </div>\n' +
                    '</div>';

                // Insert settings menu
                $(".settings-menu").parent().append(settingsHtml);

                // Handle settings events
                $(".settings-link").click(function () {
                    $('#gd_settings_container').get(0).style.display = "none";
                    $('.settings-container').get(0).style.display = "block";
                    gdsettings = false;
                });

                $("#gd_indexer").click(function () {
                    $('.settings-container').get(0).style.display = "none";
                    $('#gd_settings_container').get(0).style.display = "block";
                });

                $(".inbox_gd_enabled").click(function () {
                    settingsCbx('inbox', !gd_settings.inbox);
                    if (!gd_settings.inbox) {
                        settingsCbx('keys_enabled', false);
                    }
                });
                $(".forum_gd_enabled").click(function () {
                    settingsCbx('forum', !gd_settings.forum);
                });
                $(".stats_gd_enabled").click(function () {
                    settingsCbx('stats', !gd_settings.stats);
                });
                $(".keys_enabled_gd_enabled").click(function () {
                    settingsCbx('keys_enabled', !gd_settings.keys_enabled);
                });

                if (gdsettings === true) {
                    $('.settings-container').get(0).style.display = "none";
                    $('#gd_settings_container').get(0).style.display = "block";
                }
            }
        }

        function settingsCbx(type, value) {
            // Update class
            if (value === true) {
                $('.' + type + '_gd_enabled').get(0).classList.add("checked");
            }
            else {
                $('.' + type + '_gd_enabled').get(0).classList.remove("checked");
            }
            // Set value
            gd_settings[type] = value;
            saveSettings();
            $('#gd_s_saved').get(0).style.display = 'block';
            setTimeout(function () {
                if ($('#gd_s_saved').get(0)) {
                    $('#gd_s_saved').get(0).style.display = 'none';
                }
            }, 3000);
        }

        function saveSettings() {
            localStorage.setItem('gd_city_indexer_s', JSON.stringify(gd_settings));
        }

        function loadTownIntel(id) {
            try {
                indexes = [index_key];
                if (globals.gdIndexScript.length > 1) {
                    indexes = globals.gdIndexScript;
                }
                indexes = JSON.stringify(indexes)

                $('.info_tab_content_' + id).empty();
                $('.info_tab_content_' + id).append('Loading intel..');
                $.ajax({
                    method: "get",
                    url: "https://api.grepodata.com/indexer/api/town?keys=" + indexes + "&id=" + id
                }).error(function (err) {
                    $('.info_tab_content_' + id).empty();
                    $('.info_tab_content_' + id).append('<div style="text-align: center"><br/><br/>' +
                        'You have not yet collected any intelligence about this town.<br/>Index more reports about this town.<br/><br/>' +
                        '<a href="https://grepodata.com/indexer/' + index_key + '" target="_blank" style="">Index homepage: ' + index_key + '</a></div>');
                }).done(function (b) {
                    try {
                        $('.info_tab_content_' + id).css("max-height", '100%');
                        $('.info_tab_content_' + id).css("height", '100%');
                        var tooltips = [];

                        $('.info_tab_content_' + id).empty();

                        // Version check
                        if (b.hasOwnProperty('latest_version') && b.latest_version != null && b.latest_version.toString() !== gd_version) {
                            var updateHtml =
                                '<div class="gd-update-available" style=" background: #b93b3b; color: #fff; text-align: center; border-radius: 10px; padding-bottom: 2px;">' +
                                'New userscript version available: ' +
                                '<a href="https://api.grepodata.com/userscript/cityindexer_' + index_hash + '.user.js" class="gd-ext-ref" target="_blank" ' +
                                'style="color: #c5ecdb; text-decoration: underline;">Update now!</a></div>';
                            $('.info_tab_content_' + id).append(updateHtml);
                            $('.gd-update-available').tooltip((b.hasOwnProperty('update_message') ? b.update_message : b.latest_version));
                        }

                        // Buildings
                        var build = '<div class="gd_build_' + id + '" style="padding: 5px 0;">';
                        var date = '';
                        for (var j = 0; j < Object.keys(b.buildings).length; j++) {
                            var name = Object.keys(b.buildings)[j];
                            var value = b.buildings[name].level.toString();
                            if (value != null && value != '' && value.indexOf('%') < 0) {
                                date = b.buildings[name].date;
                                build = build + '<div class="building_header building_icon40x40 ' + name + ' regular" id="icon_building_' + name + '" ' +
                                    'style="margin-left: 3px; width: 32px; height: 32px;">' +
                                    '<div style="position: absolute; top: 17px; margin-left: 8px; z-index: 10; color: #fff; font-size: 12px; font-weight: 700; text-shadow: 1px 1px 3px #000;">' + value + '</div>' +
                                    '</div>';
                            }
                        }
                        build = build + '</div>';
                        $('.info_tab_content_' + id).append(build);
                        $('.gd_build_' + id).tooltip('Buildings as of: ' + date);

                        var table =
                            '<div class="game_border" style="max-height: 100%;">\n' +
                            '   <div class="game_border_top"></div><div class="game_border_bottom"></div><div class="game_border_left"></div><div class="game_border_right"></div>\n' +
                            '   <div class="game_border_corner corner1"></div><div class="game_border_corner corner2"></div><div class="game_border_corner corner3"></div><div class="game_border_corner corner4"></div>\n' +
                            '   <div class="game_header bold">\n' +
                            'Town intel for: ' + b.name + '<a href="https://grepodata.com/indexer/' + index_key + '" class="gd-ext-ref" target="_blank" style="float: right; color: #fff; text-decoration: underline;">Enemy city index: ' + index_key + '</a>\n' +
                            '   </div>\n' +
                            '   <div style="height: 280px;">' +
                            '     <ul class="game_list" style="display: block; width: 100%; height: 280px; overflow-x: hidden; overflow-y: auto;">\n';
                        for (var j = 0; j < Object.keys(b.intel).length; j++) {
                            var intel = b.intel[j];
                            var row = '';

                            // Type
                            if (intel.type != null && intel.type != '') {
                                var typeUrl = '';
                                var tooltip = '';
                                var flip = true;
                                switch (intel.type) {
                                    case 'enemy_attack':
                                        typeUrl = '/images/game/towninfo/attack.png';
                                        tooltip = 'Enemy attack';
                                        break;
                                    case 'friendly_attack':
                                        flip = false;
                                        typeUrl = '/images/game/towninfo/attack.png';
                                        tooltip = 'Friendly attack';
                                        break;
                                    case 'attack_on_conquest':
                                        typeUrl = '/images/game/towninfo/conquer.png';
                                        tooltip = 'Attack on conquest';
                                        break;
                                    case 'support':
                                        typeUrl = '/images/game/towninfo/support.png';
                                        tooltip = 'Sent in support';
                                        break;
                                    case 'spy':
                                        typeUrl = '/images/game/towninfo/espionage_2.67.png';
                                        if (intel.silver != null && intel.silver != '') {
                                            tooltip = 'Silver used: ' + intel.silver;
                                        }
                                        break;
                                    default:
                                        typeUrl = '/images/game/towninfo/attack.png';
                                }
                                var typeHtml =
                                    '<div style="position: absolute; height: 0px; margin-top: -5px; ' +
                                    (flip ? '-moz-transform: scaleX(-1); -o-transform: scaleX(-1); -webkit-transform: scaleX(-1); transform: scaleX(-1); filter: FlipH; -ms-filter: "FlipH";' : '') +
                                    '"><div style="background: url(' + typeUrl + ');\n' +
                                    '    padding: 0;\n' +
                                    '    height: 50px;\n' +
                                    '    width: 50px;\n' +
                                    '    position: relative;\n' +
                                    '    display: inherit;\n' +
                                    '    transform: scale(0.6, 0.6);-ms-transform: scale(0.6, 0.6);-webkit-transform: scale(0.6, 0.6);' +
                                    '    box-shadow: 0px 0px 9px 0px #525252;" class="intel-type-' + id + '-' + j + '"></div></div>';
                                row = row +
                                    '<div style="display: table-cell; width: 50px;">' +
                                    typeHtml +
                                    '</div>';
                                tooltips.push(tooltip);
                            } else {
                                row = row + '<div style="display: table-cell;"></div>';
                            }

                            // Date
                            row = row + '<div style="display: table-cell; width: 100px;" class="bold"><div style="margin-top: 3px; position: absolute;">' + intel.date.replace(' ', '<br/>') + '</div></div>';

                            // units
                            var unitHtml = '';
                            var killed = false;
                            for (var i = 0; i < Object.keys(intel.units).length; i++) {
                                var unit = intel.units[i];
                                var size = 10;
                                switch (Math.max(unit.count.toString().length, unit.killed.toString().length)) {
                                    case 1:
                                    case 2:
                                        size = 11;
                                        break;
                                    case 3:
                                        size = 10;
                                        break;
                                    case 4:
                                        size = 8;
                                        break;
                                    case 5:
                                        size = 6;
                                        break;
                                    default:
                                        size = 10;
                                }
                                if (unit.killed > 0) {
                                    killed = true;
                                }
                                unitHtml = unitHtml +
                                    '<div class="unit_icon25x25 ' + unit.name + '" style="overflow: unset; font-size: ' + size + 'px; text-shadow: 1px 1px 3px #000; color: #fff; font-weight: 700; border: 1px solid #626262; padding: 10px 0 0 0; line-height: 13px; height: 15px; text-align: right; margin-right: 2px;">' +
                                    unit.count +
                                    (unit.killed > 0 ? '   <div class="report_losts" style="position: absolute; margin: 4px 0 0 0; font-size: ' + (size - 1) + 'px; text-shadow: none;">-' + unit.killed + '</div>\n' : '') +
                                    '</div>';
                            }
                            if (intel.hero != null) {
                                unitHtml = unitHtml +
                                    '<div class="hero_icon_border golden_border" style="display: inline-block;">\n' +
                                    '    <div class="hero_icon_background">\n' +
                                    '        <div class="hero_icon hero25x25 ' + intel.hero.toLowerCase() + '"></div>\n' +
                                    '    </div>\n' +
                                    '</div>';
                            }
                            row = row + '<div style="display: table-cell;"><div><div class="origin_town_units" style="padding-left: 30px; margin: 5px 0 5px 0; ' + (killed ? 'height: 37px;' : '') + '">' + unitHtml + '</div></div></div>';

                            // Wall
                            if (intel.wall !== null && intel.wall !== '' && (!isNaN(0) || intel.wall.indexOf('%') < 0)) {
                                row = row +
                                    '<div style="display: table-cell; width: 50px; float: right;">' +
                                    '<div class="sprite-image" style="display: block; font-weight: 600; ' + (killed ? '' : 'padding-top: 10px;') + '">' +
                                    '<div style="position: absolute; top: 19px; margin-left: 8px; z-index: 10; color: #fff; font-size: 10px; text-shadow: 1px 1px 3px #000;">' + intel.wall + '</div>' +
                                    '<img src="https://gpnl.innogamescdn.com/images/game/main/buildings_sprite_40x40.png" alt="icon" ' +
                                    'width="40" height="40" style="object-fit: none;object-position: -40px -80px;width: 40px;height: 40px;' +
                                    'transform: scale(0.68, 0.68);-ms-transform: scale(0.68, 0.68);-webkit-transform: scale(0.68, 0.68);' +
                                    'padding-left: -7px; margin: -48px 0 0 0px; position:absolute;">' +
                                    '</div></div>';
                            } else {
                                row = row + '<div style="display: table-cell;"></div>';
                            }

                            var rowHeader = '<li class="' + (j % 2 === 0 ? 'odd' : 'even') + '" style="display: inherit; width: 100%; padding: 0 0 ' + (killed ? '0' : '4px') + ' 0;">';
                            table = table + rowHeader + row + '</li>\n';
                        }
                        table = table + '</div></ul></div>';
                        $('.info_tab_content_' + id).append(table);
                        for (var j = 0; j < tooltips.length; j++) {
                            $('.intel-type-' + id + '-' + j).tooltip(tooltips[j]);
                        }

                        var world = Game.world_id;
                        var exthtml =
                            '<div style="display: list-item" class="gd-ext-ref">' +
                            (b.player_id != null && b.player_id != 0 ? '   <a href="https://grepodata.com/indexer/player/' + index_key + '/' + world + '/' + b.player_id + '" target="_blank" style="float: left;"><img alt="" src="/images/game/icons/player.png" style="float: left; padding-right: 2px;">(' + b.player_name + ') Show player intel</a>' : '') +
                            (b.alliance_id != null && b.alliance_id != 0 ? '   <a href="https://grepodata.com/indexer/alliance/' + index_key + '/' + world + '/' + b.alliance_id + '" target="_blank" style="float: right;"><img alt="" src="/images/game/icons/ally.png" style="float: left; padding-right: 2px;">Show alliance intel</a>' : '') +
                            '</div>';
                        $('.info_tab_content_' + id).append(exthtml);
                        $('.gd-ext-ref').tooltip('Opens in new tab');

                    } catch (u) {
                        console.log(u);
                        $('.info_tab_content_' + id).empty();
                        $('.info_tab_content_' + id).append('<div style="text-align: center"><br/><br/>' +
                            'No intel available at the moment.<br/>Index some new reports about this town to collect intel.<br/><br/>' +
                            '<a href="https://grepodata.com/indexer/' + index_key + '" target="_blank" style="">Index homepage: ' + index_key + '</a></div>');
                    }
                });
            } catch (w) {
                console.log(w);
                $('.info_tab_content_' + id).empty();
                $('.info_tab_content_' + id).append('<div style="text-align: center"><br/><br/>' +
                    'No intel available at the moment.<br/>Index some new reports about this town to collect intel.<br/><br/>' +
                    '<a href="https://grepodata.com/indexer/' + index_key + '" target="_blank" style="">Index homepage: ' + index_key + '</a></div>');
            }
        }

        function linkToStats(action, opt) {
            if (gd_settings.stats === true && opt && 'url' in opt) {
                try {
                    var url = decodeURIComponent(opt.url);
                    var json = url.match(/&json={.*}&/g)[0];
                    json = json.substring(6, json.length - 1);
                    json = JSON.parse(json);
                    if ('player_id' in json && action.search("/player") >= 0) {
                        // Add stats button to player profile
                        var player_id = json.player_id;
                        var statsBtn = '<a target="_blank" href="https://grepodata.com/player/' + gd_w.Game.world_id + '/' + player_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
                        $('#player_buttons').filter(':first').append(statsBtn);
                    } else if ('alliance_id' in json && action.search("/alliance") >= 0) {
                        // Add stats button to alliance profile
                        var alliance_id = json.alliance_id;
                        var statsBtn = '<a target="_blank" href="https://grepodata.com/alliance/' + gd_w.Game.world_id + '/' + alliance_id + '" class="write_message" style="background: ' + gd_icon + '; margin: 5px;"></a>';
                        $('#player_info > ul > li').filter(':first').append(statsBtn);
                    }
                } catch (e) {
                }
            }
        }

        var count = 0;

        function gd_indicator() {
            count = count + 1;
            $('#gd_index_indicator').get(0).innerText = count;
            $('#gd_index_indicator').get(0).style.display = 'inline';
            $('.gd_settings_icon').tooltip('Indexed Reports: ' + count);
        }

        function viewTownIntel(xhr) {
            var town_id = xhr.responseText.match(/\[town\].*?(?=\[)/g)[0];
            town_id = town_id.substring(6);

            // Add intel button and handle click event
            var intelBtn = '<div id="gd_index_town_' + town_id + '" town_id="' + town_id + '" class="button_new gdtv' + town_id + '" style="float: right; bottom: 5px;">' +
                '<div class="left"></div>' +
                '<div class="right"></div>' +
                '<div class="caption js-caption">' + translate.VIEW + '<div class="effect js-effect"></div></div></div>';
            $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.odd').filter(':first').append(intelBtn);

            if (gd_settings.stats === true) {
                try {
                    // Add stats button to player name
                    var player_id = xhr.responseText.match(/player_id = [0-9]*,/g);
                    if (player_id != null && player_id.length > 0) {
                        player_id = player_id[0].substring(12, player_id[0].search(','));
                        var statsBtn = '<a target="_blank" href="https://grepodata.com/player/' + gd_w.Game.world_id + '/' + player_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
                        $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.even > div.list_item_right').eq(1).append(statsBtn);
                        $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.even > div.list_item_right').css("min-width", "140px");
                    }
                    // Add stats button to ally name
                    var ally_id = xhr.responseText.match(/alliance_id = parseInt\([0-9]*, 10\),/g);
                    if (ally_id != null && ally_id.length > 0) {
                        ally_id = ally_id[0].substring(23, ally_id[0].search(','));
                        var statsBtn2 = '<a target="_blank" href="https://grepodata.com/alliance/' + gd_w.Game.world_id + '/' + ally_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
                        $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.odd > div.list_item_right').filter(':first').append(statsBtn2);
                        $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.odd > div.list_item_right').filter(':first').css("min-width", "140px");
                    }
                } catch (e) {
                    console.log(e);
                }
            }

            // Handle click
            $('#gd_index_town_' + town_id).click(function () {
                var panel_root = $('.info_tab_content_' + town_id).parent().parent().parent().get(0);
                panel_root.getElementsByClassName('active')[0].classList.remove('active');
                loadTownIntel(town_id);
            });
        }

        // Loads a list of report ids that have already been added. This is used to avoid duplicates
        function loadIndexHashlist(extendMode) {
            try {
                $.ajax({
                    method: "get",
                    url: "https://api.grepodata.com/indexer/getlatest?key=" + index_key + "&player_id=" + gd_w.Game.player_id
                }).done(function (b) {
                    try {
                        if (globals.reportsFoundForum === undefined) {
                            globals.reportsFoundForum = [];
                        }
                        if (globals.reportsFoundInbox === undefined) {
                            globals.reportsFoundInbox = [];
                        }

                        if (extendMode === false) {
                            if (b['i'] !== undefined) {
                                $.each(b['i'], function (b, d) {
                                    globals.reportsFoundInbox.push(d)
                                });
                            }
                            if (b['f'] !== undefined) {
                                $.each(b['f'], function (b, d) {
                                    globals.reportsFoundForum.push(d)
                                });
                            }
                        } else {
                            // Running in extend mode, merge with existing list
                            if (b['f'] !== undefined) {
                                globals.reportsFoundForum = globals.reportsFoundForum.filter(value => -1 !== b['f'].indexOf(value));
                            }
                            if (b['i'] !== undefined) {
                                globals.reportsFoundInbox = globals.reportsFoundInbox.filter(value => -1 !== b['i'].indexOf(value));
                            }
                        }
                    } catch (u) {}
                });
            } catch (w) {
            }
        }
    }

    function enableCityIndex(key, globals) {
        // if (gd_w.)
        if (globals.gdIndexScript === undefined) {
            globals.gdIndexScript = [key];

            console.log('GrepoData city indexer ' + key + ' is running in primary mode.');
            loadCityIndex(key, globals);
        } else {
            globals.gdIndexScript.push(key);
            console.log('duplicate indexer script. index ' + key + ' is running in extended mode.');

            // Merge id lists
            setTimeout(function () {
                try {
                    $.ajax({
                        method: "get",
                        url: "https://api.grepodata.com/indexer/getlatest?key=" + key + "&player_id=" + gd_w.Game.player_id
                    }).done(function (b) {
                        try {
                            if (globals.reportsFoundForum === undefined) {
                                globals.reportsFoundForum = [];
                            }
                            if (globals.reportsFoundInbox === undefined) {
                                globals.reportsFoundInbox = [];
                            }

                            // Running in extend mode, merge with existing list
                            if (b['f'] !== undefined) {
                                globals.reportsFoundForum = globals.reportsFoundForum.filter(value => -1 !== b['f'].indexOf(value));
                            }
                            if (b['i'] !== undefined) {
                                globals.reportsFoundInbox = globals.reportsFoundInbox.filter(value => -1 !== b['i'].indexOf(value));
                            }
                        } catch (u) {
                            console.log(u);
                        }
                    });
                } catch (w) {
                    console.log(w);
                }
            }, 4000 * (globals.gdIndexScript.length - 1));
        }
    }

    var gd_w = window;
    if(gd_w.location.href.indexOf("grepodata.com") >= 0){
        // Viewer (grepodata.com)
        console.log("initiated grepodata.com viewer");
        grepodataObserver('');

        // Watch for angular app route change
        function grepodataObserver(path) {
            var initWatcher = setInterval(function () {
                if (gd_w.location.pathname.indexOf("/indexer/") >= 0 &&
                    gd_w.location.pathname.indexOf(index_key) >= 0 &&
                    gd_w.location.pathname != path) {
                    clearInterval(initWatcher);
                    messageObserver();
                } else if (path != "" && gd_w.location.pathname != path) {
                    path = '';
                }
            }, 300);
        }

        // Hide install message on grepodata.com/indexer
        function messageObserver() {
            var timeout = 20000;
            var initWatcher = setInterval(function () {
                timeout = timeout - 100;
                if ($('#help_by_contributing').get(0)) {
                    clearInterval(initWatcher);
                    // Hide install banner if script is already running
                    $('#help_by_contributing').get(0).style.display = 'none';
                    if ($('#new_index_install_tips').get(0) && $('#new_index_waiting').get(0)) {
                        $('#new_index_waiting').get(0).style.display = 'block';
                        $('#new_index_install_tips').get(0).style.display = 'none';
                    }
                    if ($('#userscript_version').get(0)) {
                        $('#userscript_version').append('<div id="gd_version">' + gd_version + '</div>');
                    }
                    grepodataObserver(gd_w.location.pathname);
                } else if (timeout <= 0) {
                    clearInterval(initWatcher);
                    grepodataObserver(gd_w.location.pathname);
                }
            }, 100);
        }
    } else if((gd_w.location.pathname.indexOf("game") >= 0)){
        // Indexer (in-game)
        if (gd_w['gd'+world] === undefined) gd_w['gd'+world] = {};
        enableCityIndex(index_key, gd_w['gd'+world]);
    }
} catch(error) { console.error("GrepoData City Indexer crashed (please report a screenshot of this error to admin@grepodata.com): ", error); }
})();

{/literal}
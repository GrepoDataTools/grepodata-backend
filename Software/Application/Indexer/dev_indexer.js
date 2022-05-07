var gd_version = "5.0.0";
var verbose = false;
var errorSubmissions = [];

(function() { try {

    // Globals
    var backend_url = 'https://api.grepodata.com'
    var frontend_url = 'https://grepodata.com'
    var time_regex = /([0-5]\d)(:)([0-5]\d)(:)([0-5]\d)(?!.*([0-5]\d)(:)([0-5]\d)(:)([0-5]\d))/gm;
    var gd_icon = "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAXCAYAAAAV1F8QAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAYdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjAuNvyMY98AAAG0SURBVEhLYwACASA2AGIHGmGQ2SA7GGzf7oj4//5g7v/3B7L+vz+U///NVv//r9ZY/3+7K/b/683e/9/tSSTIf7M9DGhGzv8PR4r/v9uX9v/D0TKw+MdTzf9BdoAsSnm13gnEoQn+dLYLRKcAMUPBm62BYMH/f/9QFYPMfL3JE0QXQCzaFkIziz6d60FYBApvdIt07AJQ+ORgkJlfrs2DW1T9ar0jxRZJ7JkDxshiIDPf744B0dUgiwrebA8l2iJsBuISB5l5q58dREOC7u3OKJpZdHmKEsKi1xvdybIIpAamDpdFbze5ISzClrypZdGLZboIiz6d7cRrES4DibHozdYghEWfL0ygmUVvtwcjLPpwuJBmFj1ZpImw6N3uBNpZNE8ByaK9KXgtIheDzHy12gJuUfG7falYLSIHI5sBMvPlCiMQXQy2CFQPoVtEDQwy88VScByBLSqgpUVQH0HjaH8GWJAWGFR7A2mwRSkfjlUAM1bg/9cbXMAVFbhaBib5N9uCwGxQdU2ID662T9aDMag5AKrOQVX9u73JIIvANSyoPl8CxOdphEFmg9sMdGgFMQgAAH4W0yWXhEbUAAAAAElFTkSuQmCC')";
    var gd_icon_intel = "url('data:image/png;base64,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')";
    var react_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAxhSURBVHhe5ZsLXBTVHsd3ZmffLLsg8pDkIaKG4MW8RahdDbmSJKbho+4npTRBLkg+E59XUyFfEIqAGqapaUZKKqGRhVfU1FTEVEwEQVGE5bnv3dmZzuw9cCX2MQOLdG/fz0f3/P4z7GfOf8/5n///zAzC6kYKCgqQW4eW+SplD4INWlUgiyQGEQTuCT6dSYK0ZxF6jvFElKPn8IX572z6bqLn4CDSaKNJ8uuSiXqNMosw4C6URlC2FuPyswa8Ejl/yvLPrX6XzR2QfzADKzmyKUSvbJxM4LpwQqdyh4fMgqCoQuTgErDo68f3oYkWOUlR2M3v99eShMEBmtrgiSQhS/Oaf4TSLCj87DLb40JckyKc1lz+bOF9dV3FKVzVNJtO5ykwDn81085T1D0sF5vqPAWKcX1h0yJddkBGfIjb2nCHNFnp2QqdXLaK0KlpdboVlI1VOfcP3A4lM0jzI5zuPOq0Aw5/soT78UTXRFlp0V2DqjGBNOj58BAj2Bzeltnbz2ugfOZ0ygGpM/yH3slPv6xpqkkG81wEzYwBAUsldfXaA2WPwMgBBw8eZG2c7Dm35WHpBYNWOQSaOw0b4+TH7bnZAmWPQNsBR1Pm8iq+mPuZSla5lSRwHjR3CZTNOQ6bPQYtB6TGhohun96XB4Lcu9BkEwT2jv+GzR7Dah6QOidE1HLvwglSrx4NTTYBRP+mgLFRjpOWZJsN2Bve6P08rlWNIEnS5A+FsDGhXtWSCmU7ML5oP0kQZ6FsB9VpjCeocvIcfMqiA46mJPBun96bp1M2j4EmmwGyteIV32mGQtkBkOGN0apavgVLHReabA6HL0ozOwUOHDjAKi08uKM7Ok9hwPWPYdMkIL19rzs7T2HAddFmHVB9dOlcbUtdFJQ2B2Rwetg0Ccjtu7XzRkiWwKQDUqb7D1U3PNoE5f81HRzwZepirrK2Yo+tljpzgCBoMYECGWINbHYfCKuqgwPKC/ctsEWSYw0EQfvCpkl4dtIk4IRLNBaqToFinDquQBzT7tsz4ka7ye6cv8s0vQWdMfAkLkm9AsKyWKSBVVdyKlqvqF8J5rnZGAPSYJ1vcITkH+tzLdYBxzOXi0GwwqBsR8uTSkl50eEKKNth5/Tc/IEhM/ZC2Q5QfrMcXT2ahk+MJts5YN04+zRc3ZIAJW34jn3jE488aFfRbZrqlaCsvZ8GpUnETu7BC3Oqf4KSMZmxIx2e3C5qgLIdfHunmMRjsp1QmqXtF9oaO8rVoFVFQ0kbTCC+OjTm0wwo2xg4LXkbJrC/BqVJNIrGV2Gzx2hzgOLhzVgQ+BiXtBjfPjssLKxDNjch8m0S5Qh2QWkSAte/AZs9htEBeQfSMRD4ZhstDBE4eZjN57kSF4u5vsGAB6VM6TsYyh7B6IAbRzeHGHRqN6OFIYMnLamGzQ5IBr1aBSIOVCYgSZZaXv8BVD2C8epwZcNko+oEpIVliiAI2DIPrtNEpUz16AflMweltq5BVhoONWNu5qzrA5sdkJcVeYCcFyrTgIqNq2qq2wglI3hCsdkvt5Zqt4Le/mrVAKYbmU+jqn/wN9jsgLbh4UjYtAiuU0cmh9u/CSVtZm7JbwbJ0hUo2wDrvI7LF5kshX8PKq8pfxm2O4VBq5h1Oj+3wzw4lLoYIfRq2oFVp1Hs2vymK+OpILB3Gs/hCdOAI75ic7jgH38vTygZszDnYRk8xSLI2tfEqQaNfB7UnULo5JHwYU7VNiiNbIjsG6+uf9DOZg2UzbkllPZ+ZdHXj0wmN90BsjZMlA+WwNeg7hxUKizulST2eXkHjuOkqurqHL28bhmY32x4Bm1AkXRFKHUe+6ycgHw0VnAbxIBBUHeR1plA97aEaaiRILB3jFh89Ek5NHULhfuSUBRES2eobQDV8a51ngIEcD9Vs+znpHBx5E+5mdBqWzZHunmfO7gxF1nzdwFJ6tXQ/McD4wqOgNGwCBRNJqs+puxOGMV/fOdyPK7XriYRlIusHm2Dn6yboZY1jGuM7p8sOvL4FjQzImPmEGlTTcW7uFY9nzDgHtDM+p9wQBsIwmKzsYsoxvmGJ5L82GdQULG5/YTjqXHIvUv53qqm2ldAhyMMuH4cmO5CeLiNP/wUsAS1qYKi7EqSJKpAJ5XQxgEriRvorDewSYwnmoPN0SNrQrn1JK5zhKY/FQjGrUcRFKuF+s8Hwq5FEQRh9GSGnWv/1cBzfzingVS4XujssxZKWlDTBwWTphRqWiAcwbEBr04fwJe6LEM5/CfQ3GOgGK+WL3Vb6fzCBB8WR5ALzfRA2aUomycqhpIW2pZav7eXZzcn5j5Jdh8921Pg6P4uJhCfB46EZzwDwGqA8cUX+Y7uszxGzfBKzH28LmbDV824QuYHz6AFmysoRrbGjBzYcKeI9ijAhNKtK75t6rCLs3V2sK+ytnwarlVOAKn1MEtb4p0CQQk2T1iMYtxcUR+/w/N2Ft2BR9pYN06Shqubae9qO/gGD0QKCwuRs0nhDwx0n+jiiX5ZcUoZAKVJdi6Z7NxQdnEUrpYHg7R2GIsk/AmdBqw0dFMOhIVy+Y1gjP4C1vyrYKqdc/AKPBObdtJi7AGF3Q1Q2PlDaRHgzOqQVQV9jdXL+nDJTr2qmV7tDoa6vccQ7wV7i2kHz3PnziHXj23rJa+85oVrFG4sg97JoFUI+GJHe+q4Rt7QAqaiGkxoGfh8LOkfXOkbEiULDQ2lnaRtmfEXL/mDGxXWdqBa4Ygcdi3Pa4w2OmDD1H5j1bXlp4xHaMC1771q2bE6RhG3u0mK6L1SJ6/7CEqrCF18wz788u53xnk6dPKSH1CuwOzu7u8Bv+L7B9LXmrxd1RMcykzCcK3ifSitAqbUw79OS/zB2Kb+Gzs1Bse4wk+pNh1AkPOoKdozHcoe59GZ7OnUNUFpFYxvlx3y5iycardFaqlXYBbC5tB+YFHZ8Gjlp4lTuvUWOh32rHiLR10LlFah+ujYb2gWlKy2Lau8SxWKMX5SZ1AYBUGTRUgD7qBXNhLflyoKoalHCHKoW6FXNU2C0iqYyCFjwb5fcqD87wigcOjrlwyKKTmUVtE01y1NnREwDMpnTsqMgBd0ivrlUFoF5BDyXp7+yVAaaeeA+KyiGhDhk6C0CkngHEVN2ZfpcSHPvJrMmh/uqKy5dxiMxP+8c0ADrthpfez2M+2ePGnnAIqBoTNTwFpcAqVVDDq1T1P55dz9KUs6/cwwU/anLBXWl549CpI3H2iyCgh8JQHhMR2eKTR5Y2/LO4MDFY/u/MTkOSEwcgrd/UeNj0rKMW5MdBdf/Ott0b1LeV8b1C1h0GQVhI1pJc/5vTxvb0mHusekAyg2TvaMV8kqGd3YYPPsLks9AybN3XmBdk7BhIw5I9zrK64fAVnkS9BEC5GTR/zinCqT7yR0mAKtBEbvSudJXBg9yg4u7MXGimvXNk71Hg9NNmPjNJ/XZWU/X2HaeTAydwcl7Df7QobZEUBxfNsCXsnJ3Sf0yqZQaKIHKFc5QmmOo9eQpSDo0LpHZ4706KB+LdW/fqxTNU2x9IaIKcA1FAyJiIuIiF2vhaYOWHQARca8cSLZrTMnCJ2K8cPSKBvDwbQ4xHPok9UrcML59xZ/TKsHh9LXII8u5gxX1z+IwTXyt0BpTTvStwICeaFrwJjxszcfsxiTrDqAgnJC46/nc0HFyGwkPAXKFVYhKHYSlKEXwcXdlHgPqy4rK6unjvXv79OrpfK6O0isnidxLVVCh4HU1tP4h52AI5IWuPiNmvT+pm+sBmRaDqA4QU2Hgn2Zupa696Cpy7Q+XYLY8NYEV9x7d0B49D8tDfunoe0AipMnT7JKsmPi1Q3Vm0EC0uN1wNNQS53Aoc/CkQsObR8+fDi0WoeRA1pJme4fqKq7vxeUxd3+SC0dqCRH5OwdNf/zG4z2NynMLoOWAMVEccAb81/kS10TmdQOtgYBuT1f6pY4ZOLCFzvTeYpOjYCn2fFBqFtd2dVEg0Ye3dl3B5lClbRsvv1OBw+/5LjMs116qrzLDmglY+4Y16b71+eAaTGL0Gueg2abQu3ksPl22eI+AzMTdpyzyT0Jmzmgle9zdmFXDm8IwRUy48vTdHebzcHmCqvBUP+WY+eU4x4UefqdhRsM8JBNsLkDnobacr9+YKmvsrYiGKzrgSChGUQQBur1eRcWgjq23pVGOAKqtm4AticoilaCMV5K3QMQOHleeGlmyt0RI0bYbp1sB4v1G3Kc1Sdy50vzAAAAAElFTkSuQmCC";
    var gd_icon_svg = '<svg aria-hidden="true" data-prefix="fas" data-icon="university" class="svg-inline--fa fa-university fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: #18bc9c; width: 18px;"><path fill="currentColor" d="M496 128v16a8 8 0 0 1-8 8h-24v12c0 6.627-5.373 12-12 12H60c-6.627 0-12-5.373-12-12v-12H24a8 8 0 0 1-8-8v-16a8 8 0 0 1 4.941-7.392l232-88a7.996 7.996 0 0 1 6.118 0l232 88A8 8 0 0 1 496 128zm-24 304H40c-13.255 0-24 10.745-24 24v16a8 8 0 0 0 8 8h464a8 8 0 0 0 8-8v-16c0-13.255-10.745-24-24-24zM96 192v192H60c-6.627 0-12 5.373-12 12v20h416v-20c0-6.627-5.373-12-12-12h-36V192h-64v192h-64V192h-64v192h-64V192H96z"></path></svg>';
    var launch_icon = '<svg aria-hidden="true" data-prefix="fas" data-icon="external-link" style="height: 20px; margin-left: 7px;" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-external-link fa-w-16 fa-lg"><path fill="currentColor" d="M432,320H400a16,16,0,0,0-16,16V448H64V128H208a16,16,0,0,0,16-16V80a16,16,0,0,0-16-16H48A48,48,0,0,0,0,112V464a48,48,0,0,0,48,48H400a48,48,0,0,0,48-48V336A16,16,0,0,0,432,320ZM474.67,0H316a28,28,0,0,0-28,28V46.71A28,28,0,0,0,316.79,73.9L384,72,135.06,319.09l-.06.06a24,24,0,0,0,0,33.94l23.94,23.85.06.06a24,24,0,0,0,33.91-.09L440,128l-1.88,67.22V196a28,28,0,0,0,28,28H484a28,28,0,0,0,28-28V37.33h0A37.33,37.33,0,0,0,474.67,0Z" class=""></path></svg>';

    // Ensure jquery
    if (window.jQuery) {
    } else {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-2.1.4.min.js';
        script.type = 'text/javascript';
        document.getElementsByTagName('head')[0].appendChild(script);
    }

    function loadCityIndex(globals) {
        // Settings
        var world = Game.world_id;
        var gd_settings = {
            inbox: true,
            forum: true,
            stats: true,
            context: true,
            keys_enabled: true,
            command_cancel_time: true,
            forum_reactions: true,
            bug_reports: true,
            key_inbox_prev: '[',
            key_inbox_next: ']',
        };
        readSettingsCookie();
        setTimeout(function () {
            if (gd_settings.inbox === true || gd_settings.forum === true || gd_settings.forum_reactions === true) {
                loadIndexHashlist(false, true, false); // Get list of recently indexed report ids
            }
        }, 300);
        checkLogin(false);

        // Check for other scripts
        var molehole_active = false;
        try {
            molehole_active = document.body.innerHTML.includes("grmh.pl")
        }  catch (e) {}

        // Set locale
        var translate = {
            ADD: 'Index',
            SEND: 'sending..',
            ADDED: 'Indexed',
            ERROR: 'Error',
            VIEW: 'View intel',
            TOWN_INTEL: 'Town intelligence',
            STATS_LINK: 'Show buttons that link to player/alliance statistics on grepodata.com',
            STATS_LINK_TITLE: 'Link to statistics',
            CHECK_UPDATE: 'Check for updates',
            ABOUT: 'This tool allows you to collect and browse enemy city intelligence. You can also join a private GrepoData team to share the collected intel with your allies',
            INDEX_LOGGED_IN: 'You are currently signed in as',
            INDEX_LOGGED_OUT: 'You are currently not signed in.',
            COUNT_1: 'You have contributed ',
            COUNT_2: ' reports in this session',
            SHORTCUTS: 'Keyboard shortcuts',
            SHORTCUTS_ENABLED: 'Enable keyboard shortcuts',
            SHORTCUTS_INBOX_PREV: 'Previous report (inbox)',
            SHORTCUTS_INBOX_NEXT: 'Next report (inbox)',
            MY_TEAMS: 'Your teams on world ',
            MY_TEAMS_CONTRIBUTE: 'If you enable the contribute checkbox, newly indexed reports will be shared with the team.',
            TEAM_NAME: 'Team name',
            TEAM_ROLE: 'Your role',
            TEAM_CONTRIBUTE: 'Contribute',
            TEAM_ACTION: 'Action (opens in new tab)',
            TEAM_ACTION_OVERVIEW: 'View team overview',
            COLLECT_INTEL: 'Collecting intel',
            COLLECT_INTEL_INBOX: 'Inbox (adds an "index+" button to inbox reports)',
            COLLECT_INTEL_FORUM: 'Alliance forum (adds an "index+" button to alliance forum reports)',
            SHORTCUT_FUNCTION: 'Function',
            SAVED: 'Settings saved',
            SHARE: 'Share',
            FORUM_REACTIONS_TITLE: 'Forum reactions',
            FORUM_REACTIONS_INFO: 'Add team reactions to the in-game alliance forum. All users on the same GrepoData team can see eachothers reactions.',
            CMD_OVERVIEW_TITLE: 'Enhance command overview',
            CMD_DEPARTURE_INFO: 'Add the return and cancel time to your own movements and add a link to town intel for incoming enemy movements.',
            CONTEXT_TITLE: 'Expand context menu',
            CONTEXT_INFO: 'Add an intel shortcut to the town context menu. The shortcut opens the intel for this town.',
            BUG_REPORTS: 'Upload anonymous bug reports to help improve our script.',
            SETTINGS_OTHER: 'Miscellaneous settings',
            DEPARTED_FROM: 'Departed from',
            RUNTIME_CANCELABLE: 'Cancellable until',
            RUNTIME_RETURNS: 'Returns at',
            INTEL_NOTE_TITLE: 'Notes',
            INTEL_NOTE_NONE: 'There are no notes for this town',
            INTEL_UNITS: 'Units',
            INTEL_SHOW_PLAYER: 'Player intel',
            INTEL_SHOW_ALLIANCE: 'Alliance intel',
        };
        if ('undefined' !== typeof Game) {
            switch (Game.locale_lang.substring(0, 2)) {
                case 'nl':
                    translate = {
                        ADD: 'Indexeren',
                        SEND: 'bezig..',
                        ADDED: 'Geindexeerd',
                        ERROR: 'Error',
                        VIEW: 'Intel bekijken',
                        TOWN_INTEL: 'Stad intel',
                        STATS_LINK: 'Knoppen toevoegen die linken naar speler/alliantie statistieken op grepodata.com',
                        STATS_LINK_TITLE: 'Link naar statistieken',
                        CHECK_UPDATE: 'Controleer op updates',
                        ABOUT: 'Met deze tool kun je intel verzamelen over vijandige steden. Je kunt ook lid worden van een GrepoData team om de verzamelde intel te delen met je alliantiegenoten',
                        INDEX_LOGGED_IN: 'Je bent momenteel ingelogd als',
                        INDEX_LOGGED_OUT: 'Je bent momenteel niet ingelogd.',
                        COUNT_1: 'Je hebt al ',
                        COUNT_2: ' rapporten verzameld in deze sessie',
                        SHORTCUTS: 'Toetsenbord sneltoetsen',
                        SHORTCUTS_ENABLED: 'Sneltoetsen inschakelen',
                        SHORTCUTS_INBOX_PREV: 'Vorige rapport (inbox)',
                        SHORTCUTS_INBOX_NEXT: 'Volgende rapport (inbox)',
                        MY_TEAMS: 'Jouw teams op wereld ',
                        MY_TEAMS_CONTRIBUTE: 'Nieuwe rapporten worden gedeeld met het team als je de \'Intel delen\' checkbox aanvinkt.',
                        TEAM_NAME: 'Team naam',
                        TEAM_ROLE: 'Jouw rol',
                        TEAM_CONTRIBUTE: 'Intel delen',
                        TEAM_ACTION: 'Actie (nieuw tabblad)',
                        TEAM_ACTION_OVERVIEW: 'Team overzicht',
                        COLLECT_INTEL: 'Intel verzamelen',
                        COLLECT_INTEL_INBOX: 'Inbox (voegt een "index+" knop toe aan inbox rapporten)',
                        COLLECT_INTEL_FORUM: 'Alliantie forum (voegt een "index+" knop toe aan alliantie forum rapporten)',
                        SHORTCUT_FUNCTION: 'Functie',
                        SAVED: 'Instellingen opgeslagen',
                        SHARE: 'Delen',
                        FORUM_REACTIONS_TITLE: 'Forum reacties',
                        FORUM_REACTIONS_INFO: 'Voeg team reacties toe aan het alliantie forum. Alle leden van een GrepoData team kunnen elkaars reacties zien op het forum.',
                        CMD_OVERVIEW_TITLE: 'Uitgebreid beveloverzicht',
                        CMD_DEPARTURE_INFO: 'Voeg de annuleer en terugkeer tijd toe aan eigen bevelen. Voeg een intel link toe aan vijandige bevelen.',
                        CONTEXT_TITLE: 'Context menu uitbreiden',
                        CONTEXT_INFO: 'Voeg een intel snelkoppeling toe aan het context menu als je op een stad klikt. De snelkoppeling verwijst naar de verzamelde intel van de stad.',
                        BUG_REPORTS: 'Anonieme bug reports uploaden om het script te verbeteren.',
                        SETTINGS_OTHER: 'Overige instellingen',
                        DEPARTED_FROM: 'Verzonden vanuit',
                        RUNTIME_CANCELABLE: 'Annuleerbaar tot',
                        RUNTIME_RETURNS: 'Terug om',
                        INTEL_NOTE_TITLE: 'Notities',
                        INTEL_NOTE_NONE: 'Er zijn nog geen notities voor deze stad',
                        INTEL_UNITS: 'Eenheden',
                        INTEL_SHOW_PLAYER: 'Speler intel',
                        INTEL_SHOW_ALLIANCE: 'Alliantie intel',
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
        var last_hashlist_refresh = Date.now();
        $(document).ajaxComplete(function (e, xhr, opt) {
            try {
                var url = opt.url.split("?"), action = "";
                if (typeof(url[1]) !== "undefined" && typeof(url[1].split(/&/)[1]) !== "undefined") {
                    action = url[0].substr(5) + "/" + url[1].split(/&/)[1].substr(7);
                }
                if (verbose) {
                    console.log(action);
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
                        // Reload hashlist if last refresh was more than 10 minutes ago
                        if (Date.now() - last_hashlist_refresh >= 10 * 60 * 1000) {
                            last_hashlist_refresh = Date.now();
                            loadIndexHashlist(false, false, false);
                        }

                        // Parse reports from forum and messages
                        if (gd_settings.forum === true) {
                            setTimeout(parseForumReport, 200);
                        }

                        // Add reactions to posts
                        if (gd_settings.forum_reactions === true) {
                            setTimeout(parseForumTopicReactions, 20);
                        }
                        break;
                    case "/player/index":
                        settings();
                        break;
                    case "/player/get_profile_html":
                    case "/alliance/profile":
                        linkToStats(action, opt);
                        break;
                    case "/town_overviews/command_overview":
                        if (gd_settings.command_cancel_time === true) {
                            setTimeout(enhanceCommandOverview, 20);
                        }
                        break;
                }
            } catch (error) {
                errorHandling(error, "handleAjaxCompleteObserver");
            }
        });

        var threadReactions = {};
        function parseForumTopicReactions() {
            /**
             * This function adds reactions to in-game forum posts
             * Post id's are persistent and unique within each game world
             * This allows users to react to forum posts and see eachothers reactions, as long as they are part of the same GrepoData team.
             */
            try {
                var thread_id = $('#forum_thread_id_input').val()

                if (!thread_id || !user_has_team || isNaN(thread_id)) {
                    return;
                }

                // Only load thread reactions if thread is active or active threads are unknown
                if (globals.active_threads === undefined || globals.active_threads.includes(parseInt(thread_id))) {
                    if (verbose) {
                        console.log("Loading reactions for active thread: " + thread_id)
                    }
                    // Load thread reactions before parsing posts
                    threadReactions = {};
                    getAccessToken().then(access_token => {
                        if (access_token === false) {
                            HumanMessage.error('GrepoData: login required to use forum reactions');
                            // Die graceful without popup
                            // showLoginPopup();
                        } else {
                            var data = {
                                'world': world,
                                'thread_id': thread_id,
                                'access_token': access_token
                            };

                            $.ajax({
                                url: backend_url + "/reactions/thread",
                                data: data,
                                type: 'get',
                                crossDomain: true,
                                dataType: 'json',
                                success: function (data) {
                                    if (data && 'success' in data) {
                                        threadReactions = data.posts;
                                        renderForumReactions(thread_id);
                                    }
                                },
                                error: function (jqXHR, textStatus) {
                                    console.log("error getting forum reactions");
                                },
                                timeout: 120000
                            });
                        }
                    });
                } else {
                    // Allow new reactions but skip loading previous reactions because this post is not active
                    renderForumReactions(thread_id);
                }
            } catch (error) {
                errorHandling(error, "parseForumTopicReactions");
            }
        }

        var emojilist = [
            128077, // Thumbs up
            128078, // Thumbs down
            128516, // happy eyes
            128533, // unhappy
            128525, // love
            127881, // party popper
            128640, // rocket
            128064, // eyes
            // 128512, // happy
            // 128528, // poker face
            // 128533, // unhappy
            // 129300, // think
            // 128517, // cold sweat
            // 129315, // rofl
            // 128525, // love
            // 128540, // crazy face
        ]
        function renderForumReactions(thread_id) {
            try {
                // Popup html
                $('#postlist').prepend(`
                        <div id="gd_new_reactions" class="gd_new_reactions" style="display: none;">
                            <a class="gd_react_close" id="gd_react_close"></a>
                            <div id="gd_new_reactions_options"></div>
                            <div style="margin-top: 16px;">
                                <div style="float: right;"><a id="gd_react_more_info">More info</a></div>
                                <div style="float: left; font-size: 10px; margin-top: 3px;">Powered by <a href="https://grepodata.com/indexer" target="_blank">GrepoData</a></div>
                            </div>
                        </div>
                `)

                // Click outside closes our popup
                $('#postlist').click(function () {$('#gd_new_reactions').hide();})
                $('#gd_new_reactions').click(function (event) {event.stopPropagation();})

                // Show more info dialog
                $('#gd_react_more_info').click(forumReactionsInfo);

                // Close reactions popup
                $('#gd_react_close').click(function () {$('#gd_new_reactions').hide();});

                // Populate reaction popup
                for (var i = 0; i < emojilist.length; i++) {
                    var emoteHtml = `<div id="gd_react_new_${emojilist[i]}" data-emote="${emojilist[i]}" class="emote">&#${emojilist[i]};</div>`
                    $('#gd_new_reactions_options').append(emoteHtml);

                    $(`#gd_react_new_${emojilist[i]}`).click(function () {
                        var reaction = $(this).data('emote')
                        addPostReaction(thread_id, active_react_post, reaction);
                        $('#gd_new_reactions').hide();
                    })
                }

                // Parse forum posts
                $('#postlist>li').each(function () {
                    // Get post features
                    var post_id = this.id
                    var post_id = post_id.replace(/\D/g,'')

                    var data = {}
                    if (post_id in threadReactions) {
                        data = threadReactions[post_id]
                    }
                    renderPostReactions(thread_id, post_id, data)
                });

            } catch (error) {
                errorHandling(error, "renderForumReactions");
            }
        }

        var active_react_post = null;
        function renderPostReactions(thread_id, post_id, data = {}) {
            /**
             * Renders the reactions for the given post
             */
            try {
                var post_header = $('#post_' + post_id).find('.author').eq(0);

                $(`#gd_react_${post_id}`).remove();

                //Primary container
                var alignment_class = Object.keys(data).length > 0 ? 'gd_react_top' : ''
                reactionsHtml = `
                    <div id="gd_react_${post_id}" class="gd_react_container ${alignment_class}">
                        <div id="gd_reactions_add_${post_id}" class="reactions_add">
                            <img class="gd_add_img" src="${react_icon}"/>
                        </div>
                    </div>
                    `;
                post_header.append(reactionsHtml);

                // Add each emote
                for (var i = emojilist.length-1; i >= 0; i--) {
                    var emote = emojilist[i]
                    if (!(emote in data)) {
                        continue;
                    }
                    var player_list = data[emote].players.join(", ");
                    var num_players = data[emote].players.length;
                    var react_class = 'gd_react_box' + (data[emote].active ? ' active':'');
                    var emote_html = `<div id="gd_reactions_${post_id}_${emote}" data-emote="${emote}" class="${react_class}"><div>&#${emote}; <span class="count">${num_players}</span></div></div>`
                    $(`#gd_react_${post_id}`).prepend(emote_html);
                    $(`#gd_reactions_${post_id}_${emote}`).tooltip(`${player_list} reacted with &#${emote};`);

                    $(`#gd_reactions_${post_id}_${emote}`).click(function () {
                        var toggled = !($(this).hasClass('active') ? true : false);
                        var count = $(this).find('.count').get(0).innerText;
                        var new_count = parseInt(count) + (toggled ? 1 : -1);
                        if (new_count <= 0) {
                            $(this).remove();
                        } else {
                            $(this).find('.count').get(0).innerText = new_count;
                            $(this).toggleClass('active', toggled);
                        }

                        var emote = $(this).data('emote');

                        if (toggled) {
                            addPostReaction(thread_id, post_id, emote);
                        } else {
                            deletePostReaction(thread_id, post_id, emote);
                        }

                    });
                }

                // Listerner for new reaction popup
                $(`#gd_reactions_add_${post_id}`).click(function (event) {
                    event.stopPropagation();
                    active_react_post = post_id;
                    $(`#gd_new_reactions`).show();
                    $("#gd_new_reactions").css({top: event.target.offsetParent.offsetTop + 27});
                });
            } catch (error) {
                errorHandling(error, "renderPostReactions");
            }
        }

        function forumReactionsInfo() {
            var content = '<b>Forum reactions powered by GrepoData</b><br><ol>' +
                '    <li>You can leave reactions to forum posts because you have installed the GrepoData city indexer usercript</li>' +
                '    <li>Your team members can see your reactions, and you can see theirs, as long as they also have the GrepoData userscript installed and you are part of the same GrepoData team</li>' +
                '    </ol>' +
                '<p id="gd-disable-forum-reactions" style="margin-bottom: 30px;">Click <a>here</a> to disable forum reactions</p>' +
                '<p id="gd-disabled-forum-reactions" style="display: none; color: darkgreen">Forum reactions have been disabled.</p>' +
                '  <br /><small>Thank you for using <a href="https://grepodata.com" target="_blank">GrepoData</a>!</small>';

            Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG).setContent(content)

            $('#gd-disable-forum-reactions').click(function () {
                gd_settings.forum_reactions = false;
                saveSettings();
                $('#gd-disabled-forum-reactions').show();
                $('#gd-disable-forum-reactions').hide();
                $('.gd_react_container').hide();
                $('.gd_new_reactions').hide();
            })
        }

        function addPostReaction(thread_id, post_id, reaction) {
            try {
                getAccessToken().then(access_token => {
                    if (access_token !== false) {
                        $.ajax({
                            url: backend_url + "/reactions/new",
                            data: {
                                access_token: access_token,
                                reaction: reaction,
                                world: Game.world_id,
                                player_id: Game.player_id,
                                thread_id: thread_id,
                                post_id: post_id
                            },
                            type: 'get',
                            crossDomain: true,
                            dataType: 'json',
                            timeout: 30000
                        }).fail(function (err) {
                            console.log("Error adding reaction: ", err);
                        }).done(function (response) {
                            if ('success' in response) {
                                var new_reactions = response.posts[post_id];
                                threadReactions[post_id] = new_reactions;
                                renderPostReactions(thread_id, post_id, threadReactions[post_id]);
                            }
                        });
                    } else {
                        showLoginPopup();
                    }
                });
                globals.active_threads.push(parseInt(thread_id));
            } catch (error) {
                errorHandling(error, "addPostReaction");
            }
        }

        function deletePostReaction(thread_id, post_id, reaction) {
            try {
                getAccessToken().then(access_token => {
                    if (access_token !== false) {
                        $.ajax({
                            url: backend_url + "/reactions/delete",
                            data: {
                                access_token: access_token,
                                reaction: reaction,
                                world: Game.world_id,
                                thread_id: thread_id,
                                post_id: post_id
                            },
                            type: 'get',
                            crossDomain: true,
                            dataType: 'json',
                            timeout: 30000
                        }).fail(function (err) {
                            console.log("Error deleting reaction: ", err);
                        }).done(function (response) {
                            if ('success' in response) {
                                var new_reactions = response.posts[post_id];
                                threadReactions[post_id] = new_reactions;
                                renderPostReactions(thread_id, post_id, threadReactions[post_id]);
                            }
                        });
                    } else {
                        showLoginPopup();
                    }
                });
            } catch (error) {
                errorHandling(error, "deletePostReaction");
            }
        }

        var parsedCommands = {};
        function enhanceCommandOverview() {
            try {
                // Parse overview
                if (MM.getModels().MovementsUnits) {
                    var commandList = $('#command_overview');
                    var commands = $(commandList).find('li');
                    var parseLimit = 100; // Limit number of parsed commands
                    let movements = Object.values(MM.getModels().MovementsUnits);
                    commands.each(function (c) {
                        if (c>=parseLimit) {
                            return
                        }
                        try {
                            var command_id = this.id;
                            if (!command_id) {
                                return
                            }
                            command_id = command_id.replace(/[^\d]+/g, '');
                            if (!(command_id in parsedCommands)) {
                                var cmd_units = $(this).find('.command_overview_units');
                                if (cmd_units.length != 0) {
                                    parsedCommands[command_id] = {
                                        is_enemy: false,
                                        movement_id: 0
                                    };
                                } else {
                                    // Command is incoming enemy, parse ids
                                    var cmd_span = $(this).find('.cmd_span').get(0);
                                    var cmd_entities = $(cmd_span).find('a');
                                    if (cmd_entities.length == 4) {
                                        var command_info = {
                                            source_town: decodeHashToJson(cmd_entities.get(0).hash),
                                            source_player: decodeHashToJson(cmd_entities.get(1).hash),
                                            target_town: decodeHashToJson(cmd_entities.get(2).hash),
                                            target_player: decodeHashToJson(cmd_entities.get(3).hash),
                                            is_enemy: true,
                                            movement_id: 0
                                        };
                                        parsedCommands[command_id] = command_info;
                                    } else {
                                        parsedCommands[command_id] = {
                                            is_enemy: false,
                                            movement_id: 0
                                        };
                                    }
                                }

                                movements.map(movement => {
                                    if (command_id == movement.attributes.command_id && parsedCommands[command_id].movement_id === 0) {
                                        parsedCommands[command_id].movement_id = movement.id
                                    }
                                });
                            }

                            enhanceCommand(command_id);
                        } catch (error) {
                            errorHandling(error, "enhanceCommandOverviewParseCommand");
                        }
                    });
                }
            } catch (error) {
                errorHandling(error, "enhanceCommandOverview");
            }
        }

        function enhanceCommand(id, force=false) {
            try {
                var cmd = parsedCommands[id];
                var cmdInfoBox = $('#command_'+id).find('.cmd_info_box');

                var returnsElem = document.getElementById('gd_runtime_'+id);
                if (!returnsElem && gd_settings.command_cancel_time === true && cmd.movement_id > 0) {
                    var movement = MM.getModels().MovementsUnits[cmd.movement_id];

                    if (movement && movement.attributes) {
                        var runtimeHtml = '<span id="gd_runtime_'+id+'" class="troops_arrive_at gd_cmd_runtime gd_runtime_'+id+'" style="font-style: italic;">(';
                        var returnText = '';
                        var cancelText = '';
                        var bHasReturnTime = false;
                        var bHasCancelTime = false;
                        if (!movement.isIncommingMovement() && movement.attributes.hasOwnProperty('started_at') && movement.getType() != 'support') {
                            bHasReturnTime = true;
                            var returns = getReturnTimeFromMovement(movement);
                            returnText = translate.RUNTIME_RETURNS + ' '+returns.return_readable;
                        }
                        if (movement.attributes.hasOwnProperty('cancelable_until') && movement.attributes.cancelable_until != null && movement.attributes.cancelable_until > 0) {
                            var diff = movement.attributes.cancelable_until - Date.now() / 1000;
                            if (diff>0) {
                                bHasCancelTime = true;
                                var cancelable_until = getHumanReadableDateTime(movement.attributes.cancelable_until, false);
                                cancelText = translate.RUNTIME_CANCELABLE + ' ' + cancelable_until;
                            }
                        }
                        if (bHasReturnTime || bHasCancelTime) {
                            if (bHasCancelTime) {
                                runtimeHtml = runtimeHtml + cancelText;
                            } else {
                                runtimeHtml = runtimeHtml + returnText;
                            }
                            runtimeHtml = runtimeHtml + ')</span>';
                            cmdInfoBox.append(runtimeHtml);
                        } else if (verbose) {
                            console.log("no times found", movement);
                        }
                    }

                }

                // Insert intel link
                var cmd_units = document.getElementById('gd_cmd_units_'+id);
                if ((!cmd_units || force) && gd_settings.command_cancel_time === true && cmd.is_enemy === true) {
                    if (cmd_units && force) {
                        $('#gd_cmd_units_'+id).remove();
                    }

                    // show a shortcut to view town intel
                    var units = '<div id="gd_cmd_units_'+id+'" class="command_overview_units gd_cmd_units" style="margin-top: 14px;"><a id="gd_cmd_intel_'+id+'" style="font-size: 10px;">Check intel > </a></div>';
                    cmdInfoBox.after(units);

                    $('#gd_cmd_units_'+id).click(function () {
                        loadTownIntel(cmd.source_town.id, cmd.source_town.name, cmd.source_player.name, id);
                    });

                }

            } catch (error) {
                errorHandling(error, "enhanceCommand");
            }
        }

        function getReturnTimeFromMovement(movement) {
            var arrival_time = movement.attributes.arrival_at;
            var departure_time = movement.attributes.started_at;
            var returns_at = arrival_time + (arrival_time - departure_time);
            return {
                arrival_time: arrival_time,
                returns_at: returns_at,
                return_readable: getHumanReadableDateTime(returns_at, false),
            };
        }

        function getHumanReadableDateTime(timestamp, includeDate = true) {
            var time = dateFromTimestamp(timestamp);
            var hours = time.getUTCHours(),
                minutes = time.getUTCMinutes(),
                seconds = time.getUTCSeconds(),
                day = time.getUTCDate(),
                month = time.getUTCMonth() + 1,
                year = time.getUTCFullYear();

            if (hours < 10) {
                hours = '0' + hours;
            }
            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            if (seconds < 10) {
                seconds = '0' + seconds;
            }
            if (day < 10) {
                day = '0' + day;
            }
            if (month < 10) {
                month = '0' + month;
            }
            return (includeDate?(day + '/' + month + '/' + year + ' '):'') + hours + ':' + minutes + ':' + seconds;
        }

        function dateFromTimestamp(timestamp) {
            return new Date((timestamp + Game.server_gmt_offset) * 1000);
        }

        function readSettingsCookie() {
            try {
                var settingsJson = getLocalToken('globals_s');
                if (!settingsJson) {
                    console.log('no local settings', settingsJson);
                    return false;
                }
                settingsJson = decodeHashToJson(settingsJson);
                if (settingsJson != null) {
                    result = JSON.parse(settingsJson);
                    if (result != null) {
                        result.forum = result.forum === false ? false : true;
                        result.inbox = result.inbox === false ? false : true;
                        if (!('stats' in result)) {
                            result.stats = true;
                        }
                        if (!('context' in result)) {
                            result.context = true;
                        }
                        if (!('forum_reactions' in result)) {
                            result.forum_reactions = true;
                        }
                        if ('departure_time' in result && !('command_cancel_time' in result)) {
                            result.command_cancel_time = result.departure_time;
                        } else if (!('command_cancel_time' in result)) {
                            result.command_cancel_time = true;
                        }
                        if (!('bug_reports' in result)) {
                            result.bugreports = true;
                        }
                        gd_settings = result;
                    }
                }
            } catch (error) {
                errorHandling(error, "readSettingsCookie");
            }
        }

        // Expand context menu
        $.Observer(GameEvents.map.town.click).subscribe(async (e, data) => {
            try {
                if (gd_settings.context && data && data.id) {
                    if (!data.player_id || data.player_id != Game.player_id) {
                        expandContextMenu(data.id, (data.name?data.name:''), (data.player_name?data.player_name:''));
                    }
                }
            } catch (error) {
                errorHandling(error, "handleMapTownObserver");
            }
        });
        $.Observer(GameEvents.map.context_menu.click).subscribe(async (e) => {
            try {
                if (gd_settings.context && e.currentTarget && e.currentTarget.activeElement && e.currentTarget.activeElement.hash) {
                    var hash = e.currentTarget.activeElement.hash;
                    if (hash==='#confirm' || hash==='#setMax' || hash==='#show_sidebar') {
                        return false;
                    }
                    var data = decodeHashToJson(hash);
                    if (data.id && data.name) {
                        expandContextMenu(data.id, data.name, '');
                    }
                }
            } catch (error) {
                var hash = '';
                try {
                    hash = e.currentTarget.activeElement.hash;
                } catch (e) {}
                errorHandling(error, "handleContextMenuObserver", {hash: hash});
            }
        });
        function expandContextMenu(town_id, town_name, player_name = '') {
            var intelHtml = '<div id="gd_context_intel" class="gd-context-icon" style="z-index: 4; background: ' + gd_icon_intel + ';">'+
                '<div class="icon_caption"><div class="top"></div><div class="middle"></div><div class="bottom"></div><div class="caption">Intel</div></div></div>';
            var menuItems = $("#context_menu").find('.context_icon');
            if (!menuItems || menuItems.length >= 5) {
                $("#context_menu").append(intelHtml);
                $("#gd_context_intel").animate({top: (menuItems.length>5?100:120)+'px'}, 120);
                //$("#gd_context_intel").animate({left: '140px'}, 120);
                $('#gd_context_intel').click(function() {
                    loadTownIntel(town_id, town_name, player_name);
                });
            }
        }

        function setCookie(name,value,days) {
            verbose ? console.log('setting cookie', name, value, days) : null;
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days*24*60*60*1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        function eraseCookie(name) {
            document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        }

        function getLocalToken(name) {
            try {
                var local_value = localStorage.getItem(name);
                if (local_value) {
                    return local_value;
                }
                var local_value = getCookie(name);
                if (local_value) {
                    return local_value;
                }
            } catch (error) {
                errorHandling(error, "getLocalToken", {name: name});
            }
            return null;
        }

        function setLocalToken(name, value) {
            try {
                setCookie(name, value, 1000);
                localStorage.setItem(name, value);
            } catch (error) {
                errorHandling(error, "setLocalToken", {name: name, value: value});
            }
        }

        function deleteLocalToken(name) {
            try {
                localStorage.removeItem(name);
                eraseCookie(name);
            } catch (e) {}
        }

        function getAccessToken(force_refresh = false) {
            return new Promise(resolve => {
                try {
                    // Get access token from local storage
                    var access_token = getLocalToken('gd_indexer_access_token');
                    if (!access_token) {
                        resolve(false);
                    }

                    // if timed out, get new access token using refresh token
                    let payload = parseJwt(access_token);
                    if (payload && payload.hasOwnProperty('exp')) {
                        let expiration = payload['exp'];

                        let currentTime = new Date().getTime() / 1000;

                        if ((currentTime > expiration - 60) || force_refresh===true) {
                            // Token expired, try to refresh
                            console.log("GrepoData: Access token expired.");
                            var refresh_token = getLocalToken('gd_indexer_refresh_token');
                            if (!refresh_token) {
                                // New login required
                                deleteLocalToken('gd_indexer_access_token');
                                resolve(false);
                            }

                            // Get new access token
                            $.ajax({
                                url: backend_url + "/auth/refresh",
                                data: {refresh_token: refresh_token},
                                type: 'post',
                                crossDomain: true,
                                dataType: 'json',
                                success: function (data) {
                                    if (data.success_code && data.success_code === 1101) {
                                        console.log('GrepoData: Renewed access token.');
                                        setLocalToken('gd_indexer_access_token', data.access_token);
                                        setLocalToken('gd_indexer_refresh_token', data.refresh_token);
                                        resolve(data.access_token);
                                    } else {
                                        resolve(false);
                                    }
                                },
                                error: function (jqXHR, textStatus) {
                                    console.log("GrepoData: Error renewing access token");
                                    // New login required
                                    deleteLocalToken('gd_indexer_access_token');
                                    errorHandling(null, "refreshAccessToken", JSON.stringify({xhr: jqXHR, text: textStatus}));
                                    resolve(false);
                                },
                                timeout: 30000
                            });
                        } else {
                            resolve(access_token)
                        }
                    } else {
                        // otherwise show login screen
                        resolve(false);
                    }
                } catch (error) {
                    errorHandling(error, "getAccessToken");
                }
            });
        }

        function getScriptToken() {
            return new Promise(resolve => {
                try {
                    // Get script token from local storage
                    script_token = getLocalToken('gd_indexer_script_token');
                    if (!script_token) {
                        // Get a new script token
                        $.ajax({
                            url: backend_url + "/auth/newscriptlink",
                            data: {},
                            type: 'get',
                            crossDomain: true,
                            dataType: 'json',
                            success: function (data) {
                                if (data.success_code && data.success_code === 1150) {
                                    console.log('GrepoData: Retrieved script token.');
                                    setLocalToken('gd_indexer_script_token', data.script_token);
                                    resolve(data.script_token);
                                } else {
                                    console.log("GrepoData: Error retrieving script token");
                                    deleteLocalToken('gd_indexer_script_token');
                                    resolve(false);
                                }
                            },
                            error: function (jqXHR, textStatus) {
                                console.log("GrepoData: Error retrieving script token");
                                deleteLocalToken('gd_indexer_script_token');
                                resolve(false);
                            },
                            timeout: 30000
                        });
                    } else {
                        // Check if existing script token has already been linked
                        setTimeout(checkScriptToken, 2000);
                        resolve(script_token);
                    }
                } catch (error) {
                    errorHandling(error, "getScriptToken");
                }
            });
        }

        function showNoTeamNotification() {
            try {
                if (getLocalToken('gd_no_team_dont_show')) {
                    return;
                }
                if (7 < $("#notification_area>.notification").length) {
                    setTimeout(function() {
                        showNoTeamNotification();
                    }, 10000);
                } else {
                    var notificationHandler = ("undefined" == typeof Layout || "undefined" == typeof Layout.notify ? new NotificationHandler : Layout);
                    var notification = notificationHandler.notify(
                        $("#notification_area>.notification").length + 1,
                        'gd_notification gd_no_team_notification',
                        '<strong>GrepoData city indexer: create or join a team to share your intel!</strong>',
                        null
                    );

                    $('.gd_no_team_notification').click(function () {
                        showTeamsPopup();
                        $('.gd_no_team_notification').hide();
                    });
                }
            } catch (e) {
                errorHandling(e, "showNoTeamNotification")
            }
        }

        function showLoginNotification() {
            try {
                if (7 < $("#notification_area>.notification").length) {
                    setTimeout(function() {
                        showLoginNotification();
                    }, 10000);
                } else {
                    var notificationHandler = ("undefined" == typeof Layout || "undefined" == typeof Layout.notify ? new NotificationHandler : Layout);
                    var notification = notificationHandler.notify(
                        $("#notification_area>.notification").length + 1,
                        'gd_notification gd_login_required_notification',
                        '<strong>GrepoData city indexer: sign in required to start indexing</strong>',
                        null
                    );

                    $('.gd_login_required_notification').click(function () {
                        showLoginPopup();
                        $('.gd_login_required_notification').hide();
                    });
                }
            } catch (e) {
                errorHandling(e, "showLoginNotification")
            }
        }

        var login_window = null;
        var script_token_interval = null;
        var refreshing_scripttoken = false;
        var interval_count = 0;
        function showLoginPopup() {
            // This function is called when there is no access_token available

            // First ensure we have a script token
            getScriptToken().then(script_token => {

                if (login_window != null) {
                    login_window.close();
                    login_window = null;
                }
                // login_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                //     '<a href="#" class="write_message" style="background: ' + gd_icon + '">' +
                //     '</a>&nbsp;&nbsp;GrepoData login required',
                //     {position: ['center','center'], width: 630, height: 405, minimizable: true});
                login_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                    'GrepoData login required',
                    {width: 630, height: 405, minimizable: true});

                // Window content
                var content = '<div class="gdloginpopup" style="width: 630px; height: 295px;"><div style="text-align: center">' +
                    '</div></div>';
                login_window.setContent(content);
                var login_window_element = $('.gdloginpopup').parent();
                $(login_window_element).css({ top: 43 });

                // Form Content
                login_form_content = `<h4 class="gd-title" style="text-align: center; font-size: 18px; display: block;">
                                        Click the link below to sign in with your GrepoData account</h4>
                                        <p style="display: block; text-align: center;">Sign in is required to use the city indexer userscript</p>`

                // Build login form
                formHtml = `
            <form autocomplete="false" class="gd-login-form" id="gd_login_form" name="gdloginform">
              <div style="text-align: center;font-weight: 800;font-size: 35px;">
                <div style="display: inline-block;"><img src="https://grepodata.com/assets/images/grepodata_icon.ico" style="position: relative; top: 4px;"></div>
                <span style="color: rgb(103, 103, 103)">GREPO</span>
                <span style="color: rgb(24, 188, 156);margin-left: -12px;">DATA</span>
              </div>
              <div id="gd-login-container" class="gd-login-container">
                  `+login_form_content+`
                  <h3 class="gd-title" style="text-align: center; place-content: center; font-size: 17px;"><a id="gd_script_auth_link" href="https://grepodata.com/link/` + script_token + `" target="_blank" style="display: contents; color: #444; text-decoration: underline;">grepodata.com/link/` + script_token + `<div>` + launch_icon + `</div></a></h3>
                  <div id="grepodatalerror" style="display: none; text-align: center; place-content: center; font-size: 16px;" class="gd-error-msg"><b>Unable to authenticate.</b></div>
                  <p id="grepodataltip" style="display: none; text-align: center; margin-bottom: -50px;">Follow the instructions on this page to link your userscript to your GrepoData account.<br/>Click 'Continue' below once your userscript is linked.<br/>Feel free to contact us if you run into any issues.</p>
                  <div class="gd-login-footer" style="margin-top: 35px; height: 50px;">
                    <p id="gd-request-new-token-btn" class="gd-link-btn" style="margin-top: 18px;">
                    Request new token
                    </p>
                    <p id="gd-request-token-check" class="gd-login-btn gd-register-btn">Continue</p>
                  </div>
              </div>
              <div id="gd-script-linked" class="gd-login-container" style="display: none;">
                  <h4 class="gd-title" style="text-align: center; place-content: center;">
                    You are now logged in. Happy indexing!
                  </h4>
                  <br/>
                  <p style="text-align: center; place-content: center;">Thank you for using GrepoData.</p>
              </div>
            </form>

        `;
                $('.gdloginpopup').append(formHtml);

                if (refreshing_scripttoken) {
                    loginError('Token was refreshed! Click the link to sign in', false, 5000);
                    refreshing_scripttoken=false;
                }

                // Handle actions
                $('#gd-request-new-token-btn').click(function () {
                    // try with new token
                    deleteLocalToken('gd_indexer_script_token');
                    showLoginPopup();
                    clearInterval(script_token_interval);
                    refreshing_scripttoken = true;
                });
                $('#gd-request-token-check').click(function () {
                    $('#grepodataltip').hide();
                    checkScriptToken(true);
                });
                $('#gd_script_auth_link').click(function () {
                    console.log("GrepoData: script link clicked");

                    clearInterval(script_token_interval);
                    interval_count = 0;
                    script_token_interval = setInterval(checkScriptToken, 3000);

                    $('#grepodatalerror').hide();
                    $('#grepodataltip').show();
                });

            });
        }

        var teams_window = null;
        function showTeamsPopup() {
            if (teams_window != null) {
                teams_window.close();
                teams_window = null;
            }
            // teams_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
            //     '<a href="#" class="write_message" style="background: ' + gd_icon + '">' +
            //     '</a>&nbsp;&nbsp;GrepoData indexer teams',
            //     {position: ['center','center'], width: 630, height: 405, minimizable: true});
            teams_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                'GrepoData indexer teams',
                {width: 630, height: 405, minimizable: true});

            // Window content
            var content = '<div class="gdteamspopup" style="width: 630px; height: 295px;"><div style="text-align: center">' +
                '</div></div>';
            teams_window.setContent(content);
            var teams_window_element = $('.gdteamspopup').parent();
            $(teams_window_element).css({ top: 43 });

            // Build form
            formHtml = `
        <form autocomplete="false" class="gd-login-form" id="gd_login_form" name="gdloginform">
          <div style="text-align: center;font-weight: 800;font-size: 35px;">
            <div style="display: inline-block;"><img src="https://grepodata.com/assets/images/grepodata_icon.ico" style="position: relative; top: 4px;"></div>
            <span style="color: rgb(103, 103, 103)">GREPO</span>
            <span style="color: rgb(24, 188, 156);margin-left: -12px;">DATA</span>
          </div>
          <div id="gd-login-container" class="gd-login-container">
              <h4 class="gd-title" style="text-align: center; font-size: 18px; display: block;">
                Create or join a GrepoData team to share the intel you collect!
                </h4>
                <h4 style="display: block; text-align: center;">You are not part of any team on world `+Game.world_id+`</h4>
              <p id="grepodataltip" style="text-align: center;">
                  The intel you have been collecting on this world has not been shared with a team.
                  You can create or join a <strong>GrepoData team</strong> together with your alliance members.
                  All members of the team will be able to contribute and view eachothers intelligence.
                  A team is only active on a specific game world; each world you play on requires a different team.
              </p>
              <div class="gd_no_team_footer">
                  <p id="gd-no-team-dont-show" class="gd-link-btn" style="margin-top: 18px;">Don't show this message again</p>
                  <a class="gd-login-btn" href="https://grepodata.com/profile?action=new_team&world=`+Game.world_id+`" target="_blank">Create a new team</a>
              </div>
          </div>
        </form>

    `;
            $('.gdteamspopup').append(formHtml);

            // Handle actions
            $('#gd-no-team-dont-show').click(function () {
                // hide notification
                setLocalToken('gd_no_team_dont_show', true);
                teams_window.close();
                teams_window = null;
            });

        }

        function parseJwt(token) {
            if (!token) {
                return null;
            }
            var base64Url = token.split('.')[1];
            var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));

            return JSON.parse(jsonPayload);
        };

        function loginError(message, verbose = false, timeout = 0) {
            console.log('login error: ', message);
            let errormsg = message==''?"Unable to authenticate. Please try again later":message;
            $('#grepodatalerror').text(errormsg);
            $('#grepodatalerror').show();
            if (timeout>0) {
                setTimeout(_ => {$('#grepodatalerror').hide();}, timeout);
            }
            verbose ? HumanMessage.error(errormsg) : null;
        }

        function checkScriptToken(verbose=false) {
            interval_count += 1;
            if (interval_count>100) {
                clearInterval(script_token_interval);
            }
            var script_token = getLocalToken('gd_indexer_script_token');
            $.ajax({
                url: backend_url + "/auth/verifyscriptlink",
                data: {
                    script_token: script_token
                },
                type: 'post',
                crossDomain: true,
                dataType: 'json',
                success: function (data) {
                    console.log(data);
                    if (data.success_code && data.success_code === 1111) {
                        console.log('GrepoData: Script token verified');
                        setLocalToken('gd_indexer_access_token', data.access_token);
                        setLocalToken('gd_indexer_refresh_token', data.refresh_token);
                        deleteLocalToken('gd_indexer_script_token');
                        HumanMessage.success('GrepoData login succesful!');
                        $('#gd-login-container').hide();
                        $('#gd-script-linked').show();
                        clearInterval(script_token_interval);
                    } else {
                        // Unable
                        loginError('Unknown error. Please try again later or let us know if this error persists.', verbose);
                    }
                },
                error: function (error, textStatus) {
                    if (error.responseJSON.error_code
                        && (
                            error.responseJSON.error_code === 3041  // Token not found
                            || error.responseJSON.error_code === 3042 // Expired (7 days)
                            || error.responseJSON.error_code === 3043 // Invalid client
                        )
                    ) {
                        // Unknown, invalid or expired script token. remove token and try again
                        clearInterval(script_token_interval);
                        deleteLocalToken('gd_indexer_script_token');
                        showLoginPopup();
                        setTimeout(_ => {loginError('Expired script token. Please try again or contact us if this error persists.')}, 1000);
                    } else if (error.responseJSON.error_code && error.responseJSON.error_code === 3040) {
                        // Token is not yet linked
                        verbose ? loginError('Your script token is not yet verified. Click the link to try again.') : null;
                    } else {
                        // Unknown
                        loginError('Unknown error. Please try again later or let us know if this error persists.', verbose);
                    }
                },
                timeout: 30000
            });
        }

        function checkLogin(show_login_popup = true) {
            // Check if grepodata access token or refresh token is in local storage and use it to verify
            // if not verified: login required!
            getAccessToken().then(access_token => {
                if (access_token === false) {
                    if (show_login_popup === true) {
                        // show login popup
                        showLoginPopup();
                    } else {
                        // show login notification
                        setTimeout(showLoginNotification, 2000);
                    }
                } else {
                    console.log("GrepoData: Succesful authentication for player "+Game.player_id);
                }
            });
        }

        // Decode entity hash
        function decodeHashToJson(hash) {
            // Remove hashtag prefix
            if (hash.slice(0, 1) === '#') {
                hash = hash.slice(1);
            }
            // Remove trailing =
            for (var g = 0; g < 10; g++) {
                if (hash.slice(hash.length - 1) === '=') {
                    hash = hash.slice(0, hash.length - 1)
                }
            }

            var data = atob(hash);
            var json = JSON.parse(data);

            if (verbose) {
                console.log("parsed from hash " + hash, json);
            }
            return json;
        }

        // Encode entity hash
        function encodeJsonToHash(json) {
            var hash = btoa(JSON.stringify(json));
            if (verbose) {
                console.log("parsed to hash " + hash, json);
            }
            return hash;
        }

        // Create town hash
        function getTownHash(id, name='', x=0, y=0) {
            return encodeJsonToHash({
                id: id,
                ix: x,
                iy: y,
                tp: 'town',
                name: name
            });
        }

        // Create player hash
        function getPlayerHash(id, name) {
            return encodeJsonToHash({
                id: id,
                name: name
            });
        }

        // settings btn
        var gdsettings = false;
        $('.gods_area').append('<div class="btn_settings circle_button gd_settings_icon" style="right: 0px; top: 87px; z-index: 101;">\n' +
            '\t<div style="margin: 7px 0px 0px 4px; width: 24px; height: 24px;">\n' +
            '\t'+gd_icon_svg+'\n' +
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
        $('.gd_settings_icon').tooltip('GrepoData City Indexer');

        // report info is converted to a 32 bit hash to be used as unique id
        // https://werxltd.com/wp/2010/05/13/javascript-implementation-of-javas-string-hashcode-method/
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
        function addToIndexFromForum(reportId, reportElement, reportPoster, reportHash, is_retry_attempt = false) {
            var reportJson = JSON.parse(mapDOM(reportElement, true));
            var reportText = reportElement.innerText;

            getAccessToken().then(access_token => {
                if (access_token === false) {
                    HumanMessage.error('GrepoData: login required to index reports');
                    showLoginPopup();
                    $('.rh' + reportHash).each(function () {
                        $(this).find('.middle').get(0).innerText = translate.ADD + ' +';
                    });
                } else {
                    var data = {
                        'report_type': 'forum',
                        'access_token': access_token,
                        'world': world,
                        'report_hash': reportHash,
                        'report_text': reportText,
                        'report_json': reportJson,
                        'script_version': gd_version,
                        'report_poster': reportPoster || 'Undefined',
                        'report_poster_id': Game.player_id || 0,
                        'report_poster_ally_id': Game.alliance_id || 0
                    };

                    $('.rh' + reportHash).each(function () {
                        $(this).css("color", '#36cd5b');
                        $(this).find('.middle').get(0).innerText = translate.ADDED + ' ✓';
                        $(this).off("click");
                    });
                    $.ajax({
                        url: backend_url + "/indexer/v2/indexreport",
                        data: data,
                        type: 'post',
                        crossDomain: true,
                        dataType: 'json',
                        success: function (data) {
                        },
                        error: function (error, textStatus) {
                            console.log("error saving forum report: ", error);

                            if (error.responseJSON.error_code
                                && error.responseJSON.error_code === 3003
                                && is_retry_attempt === false
                            ) {
                                // invalid JWT (probably expired, not caught because local client time is out of sync)
                                // try to force refresh the access token
                                getAccessToken(true).then(access_token => {
                                    if (access_token === false) {
                                        // If the force refresh was not succesful, we need a new explicit login from the user
                                        HumanMessage.error('GrepoData: login required to index reports');
                                        showLoginPopup();
                                        $('.rh' + reportHash).each(function () {
                                            $(this).css("color", '#ea6153');
                                            $(this).find('.middle').get(0).innerText = translate.ERROR + ' ✗';
                                            $(this).off("click");
                                        });
                                    } else {
                                        // try again with new token
                                        addToIndexFromForum(reportId, reportElement, reportPoster, reportHash, true);
                                    }
                                });
                            } else {
                                errorHandling(Error(error.responseText), 'ajaxIndexForumReport');
                                $('.rh' + reportHash).each(function () {
                                    $(this).css("color", '#ea6153');
                                    $(this).find('.middle').get(0).innerText = translate.ERROR + ' ✗';
                                    $(this).off("click");
                                });
                            }
                        },
                        timeout: 120000
                    });
                    pushHash(reportHash);
                    gd_indicator();
                }
            });
        }

        // Add the given inbox report to the index
        function addToIndexFromInbox(reportHash, reportElement, is_retry_attempt = false) {
            var reportJson = JSON.parse(mapDOM(reportElement, true));
            var reportText = reportElement.innerText;

            var has_combat_experience = false;
            try {
                // Check if 10% boost is enabled for friendly attack on enemy town in order to parse the killed units from the battle points gained if enemy units are invisible
                var attacker_town = reportElement.getElementsByClassName('gp_town_link')[0];
                if (attacker_town && attacker_town.getAttribute("href")) {
                    attacker_town = decodeHashToJson(attacker_town.getAttribute("href"));
                    if (attacker_town.id && MM.getModels().Town[attacker_town.id]) {
                        var combat_experience = MM.getModels().Town[attacker_town.id].researches().attributes.combat_experience
                        if (combat_experience === true || combat_experience === false) {
                            has_combat_experience = combat_experience
                        }
                    }
                }
            } catch (e) {
                errorHandling(e, 'getCombatExperience');
            }

            getAccessToken().then(access_token => {
                if (access_token === false) {
                    HumanMessage.error('GrepoData: login required to index reports');
                    showLoginPopup();
                    $('#gd_index_rep_txt').get(0).innerText = translate.ADD + ' +';
                } else {
                    var data = {
                        'report_type': 'inbox',
                        'access_token': access_token,
                        'world': world,
                        'report_hash': reportHash,
                        'report_text': reportText,
                        'report_json': reportJson,
                        'script_version': gd_version,
                        'report_poster': Game.player_name || 'undefined',
                        'report_poster_id': Game.player_id || 0,
                        'report_poster_ally_id': Game.alliance_id || 0,
                        'has_combat_experience': has_combat_experience || false,
                    };

                    if (gd_settings.inbox === true) {
                        var btn = document.getElementById("gd_index_rep_txt");
                        var btnC = document.getElementById("gd_index_rep_");
                        btnC.setAttribute('style', 'color: #36cd5b; float: right;');
                        btn.innerText = translate.ADDED + ' ✓';
                    }
                    $.ajax({
                        url: backend_url + "/indexer/v2/indexreport",
                        data: data,
                        type: 'post',
                        crossDomain: true,
                        success: function (data) {
                        },
                        error: function (error, textStatus) {
                            console.log("error saving inbox report: ", error);

                            if (error.responseJSON.error_code
                                && error.responseJSON.error_code === 3003
                                && is_retry_attempt === false
                            ) {
                                // invalid JWT (probably expired, not caught because local client time is out of sync)
                                // try to force refresh the access token
                                getAccessToken(true).then(access_token => {
                                    if (access_token === false) {
                                        // If the force refresh was not succesful, we need a new explicit login from the user
                                        HumanMessage.error('GrepoData: login required to index reports');
                                        showLoginPopup();
                                        var btn = document.getElementById("gd_index_rep_txt");
                                        var btnC = document.getElementById("gd_index_rep_");
                                        btnC.setAttribute('style', 'color: #ea6153; float: right;');
                                        btn.innerText = translate.ERROR + ' ✗';
                                    } else {
                                        // try again with new token
                                        addToIndexFromInbox(reportHash, reportElement, true);
                                    }
                                });
                            } else {
                                errorHandling(Error(error.responseText), 'ajaxIndexForumReport');
                                var btn = document.getElementById("gd_index_rep_txt");
                                var btnC = document.getElementById("gd_index_rep_");
                                btnC.setAttribute('style', 'color: #ea6153; float: right;');
                                btn.innerText = translate.ERROR + ' ✗';
                                btn.setAttribute('title', 'Oops, something went wrong. Developers have been notified (if you enabled bug reports).');
                            }

                        },
                        timeout: 120000
                    });
                    pushHash(reportHash);
                    gd_indicator();
                }
            });
        }

        function pushHash(hash) {
            if (globals.reportsFound === undefined) {
                globals.reportsFound = [];
            }
            globals.reportsFound.push(hash);
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
            try {
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
                            || reportText.indexOf('/images/game/towninfo/support.png') >= 0
                            || reportText.indexOf('power_icon86x86 wisdom') >= 0)
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
                                    var townJson = decodeHashToJson(href);
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

                        // add player id to hash to avoid inbox conflicts
                        if (Game.player_id > 0) {
                            hashText += Game.player_id;
                        }

                        reportHash = hashText.report_hash();
                        if (verbose) console.log('Parsed inbox report with hash: ' + reportHash);

                        // Create index button
                        var addBtn = document.createElement('a');
                        var txtSpan = document.createElement('span');
                        var rightSpan = document.createElement('span');
                        var leftSpan = document.createElement('span');
                        txtSpan.innerText = translate.ADD + ' +';

                        addBtn.setAttribute('href', '#');
                        addBtn.setAttribute('id', 'gd_index_rep_');
                        addBtn.setAttribute('class', 'button gd_btn_index');
                        addBtn.setAttribute('style', 'float: right;');
                        txtSpan.setAttribute('id', 'gd_index_rep_txt');
                        txtSpan.setAttribute('style', 'min-width: 50px; margin: 0 3px;');
                        txtSpan.setAttribute('class', 'middle');
                        rightSpan.setAttribute('class', 'right');
                        leftSpan.setAttribute('class', 'left');

                        rightSpan.appendChild(txtSpan);
                        leftSpan.appendChild(rightSpan);
                        addBtn.appendChild(leftSpan);

                        // Check if this report was already indexed
                        var reportFound = false;
                        if (globals && globals.reportsFound) {
                            for (var j = 0; j < globals.reportsFound.length; j++) {
                                if (globals.reportsFound[j] === reportHash) {
                                    reportFound = true;
                                }
                            }
                        }
                        if (reportFound) {
                            addBtn.setAttribute('style', 'color: #36cd5b; float: right;');
                            txtSpan.setAttribute('style', 'cursor: default;');
                            txtSpan.innerText = translate.ADDED + ' ✓';
                        } else {
                            addBtn.addEventListener('click', function () {
                                if ($('#gd_index_rep_txt').get(0)) {
                                    $('#gd_index_rep_txt').get(0).innerText = translate.SEND;
                                }
                                addToIndexFromInbox(reportHash, reportElement, false);
                            }, false);
                        }

                        // Create share button
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
                        txtShareSpan.setAttribute('style', 'min-width: 50px; margin: 0 3px;');
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
                                    '    </ul><br/><input type="text" class="gd_copy_input_' + reportHash + '" value="' + `!gd report ${hashI}` + '"> <a href="#" class="gd_copy_command_' + reportHash + '">Copy to clipboard</a><span class="gd_copy_done_' + reportHash + '" style="display: none; float: right;"> Copied!</span>' +
                                    '    <br /><br /><small>Thank you for using <a href="https://grepodata.com" target="_blank">GrepoData</a>!</small>';

                                Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG).setContent(content)
                                addToIndexFromInbox(reportHash, reportElement, false);

                                $(".gd_copy_command_" + reportHash).click(function () {
                                    $(".gd_copy_input_" + reportHash).select();
                                    document.execCommand('copy');

                                    $('.gd_copy_done_' + reportHash).get(0).style.display = 'block';
                                    setTimeout(function () {
                                        if ($('.gd_copy_done_' + reportHash).get(0)) {
                                            $('.gd_copy_done_' + reportHash).get(0).style.display = 'none';
                                        }
                                    }, 3000);
                                });
                            }
                        });

                        // Create custom footer
                        var grepodataFooter = document.createElement('div');
                        grepodataFooter.setAttribute('id', 'gd_inbox_footer');
                        grepodataFooter.appendChild(addBtn);
                        grepodataFooter.appendChild(shareBtn)
                        footerElement.appendChild(grepodataFooter);

                        // Set footer button placement
                        var folderElement = footerElement.querySelector('#select_folder_id');
                        footerElement.style.backgroundSize = 'auto 100%';
                        footerElement.style.padding = '6px 0';
                        dateElement.style.marginTop = '-4px';
                        dateElement.style.marginLeft = '3px';
                        dateElement.style.position = 'absolute';
                        dateElement.style.zIndex = '7';
                        dateElement.style.background = 'url(https://gpnl.innogamescdn.com/images/game/border/footer.png) repeat-x 0px -6px';
                        if (folderElement !== null) {
                            folderElement.style.position = 'absolute';
                            folderElement.style.marginTop = '12px';
                            folderElement.style.marginLeft = '3px';
                            folderElement.style.zIndex = '6';
                        }
                    }

                    // Handle inbox keyboard shortcuts
                    document.removeEventListener('keyup', inboxNavShortcut);
                    document.addEventListener('keyup', inboxNavShortcut);

                }

            } catch (error) {
                errorHandling(error, "parseInboxReport");
            }
        }

        function inboxNavShortcut(e) {
            try {
                var reportElement = document.getElementById("report_report");
                if (gd_settings.keys_enabled === true && !['textarea', 'input'].includes(e.srcElement.tagName.toLowerCase()) && reportElement !== null) {
                    switch (e.key) {
                        case gd_settings.key_inbox_prev:
                            var prev = reportElement.getElementsByClassName('last_report game_arrow_left');
                            if (prev.length === 1 && prev[0] != null) {
                                prev[0].click();
                            }
                            break;
                        case gd_settings.key_inbox_next:
                            var next = reportElement.getElementsByClassName('next_report game_arrow_right');
                            if (next.length === 1 && next[0] != null) {
                                next[0].click();
                            }
                            break;
                        default:
                            break;
                    }
                }
            } catch (error) {
                console.log(error);
            }
        }

        function addForumReportById(reportId, reportHash) {
            var reportElement = document.getElementById(reportId);

            if (!reportElement) return
            if (!reportHash || reportHash == '') {
                throw new Error("Unable to find forum report hash.");
                return;
            }

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

            addToIndexFromForum(reportId, reportElement, reportPoster, reportHash, false);
        }

        // Forum reports
        function parseForumReport() {
            try {
                var reportsInView = document.getElementsByClassName("bbcodes published_report");

                //process reports
                if (reportsInView && reportsInView.length > 0) {
                    for (var i = 0; i < reportsInView.length; i++) {
                        var reportElement = reportsInView[i];
                        var reportId = reportElement.id;

                        if (reportId && !$('#gd_index_f_' + reportId).get(0)) {

                            var bSpy = false;
                            var spyReportElems = reportElement.getElementsByClassName("espionage_report");
                            var unitElems = reportElement.getElementsByClassName("report_units");
                            var conquestElems = reportElement.getElementsByClassName("conquest");
                            if (spyReportElems && spyReportElems.length > 0) {
                                bSpy = true;
                            } else if ((unitElems && unitElems.length < 2)
                                || (conquestElems && conquestElems.length > 0)) {
                                // ignore non intel reports
                                continue;
                            }

                            var reportHash = null;
                            try {
                                // === Build report hash to create a unique identifier for this report that is consistent between sessions
                                var header = reportElement.getElementsByClassName('published_report_header bold')[0];

                                // Try to parse time string
                                try {
                                    var dateText = header.getElementsByClassName('reports_date small')[0].innerText;
                                    var time = dateText.match(time_regex);
                                    if (time != null) {
                                        dateText = time[0];
                                    }
                                } catch (error) {
                                    errorHandling(error, "parseForumReportNoTimeFound");
                                }

                                // Try to parse town ids from report header
                                try {
                                    var headerText = header.getElementsByClassName('bold')[0].innerText;
                                    var towns = header.getElementsByClassName('gp_town_link');
                                    if (towns.length === 2) {
                                        var ids = [];
                                        for (var m = 0; m < towns.length; m++) {
                                            var href = towns[m].getAttribute("href");
                                            var townJson = decodeHashToJson(href);
                                            ids.push(townJson.id);
                                        }
                                        if (ids.length === 2) {
                                            headerText = ids.join('');
                                        }
                                    }
                                } catch (error) {
                                    errorHandling(error, "parseForumReportReportTownIds");
                                }

                                // Try to parse units and buildings
                                try {
                                    var reportUnits = reportElement.getElementsByClassName('unit_icon40x40');
                                    var reportBuildings = reportElement.getElementsByClassName('report_unit');
                                    var reportDetails = reportElement.getElementsByClassName('report_details');
                                    var reportResources = reportElement.getElementsByClassName('resources');
                                    var reportContent = '';
                                    for (var u = 0; u < reportUnits.length; u++) {
                                        reportContent += reportUnits[u].outerHTML;
                                    }
                                    for (var u = 0; u < reportBuildings.length; u++) {
                                        reportContent += reportBuildings[u].outerHTML;
                                    }
                                    if (reportDetails.length === 1) {
                                        reportContent += reportDetails[0].innerText;
                                    }
                                    if (reportResources.length === 1) {
                                        reportContent += reportResources[0].innerText;
                                    }
                                } catch (error) {
                                    errorHandling(error, "parseForumReportReportUnits");
                                }

                                // Combine intel and generate hash
                                var reportText = dateText + headerText + reportContent;
                                if (reportText !== null && reportText !== '') {
                                    reportHash = reportText.report_hash();
                                }

                            } catch (error) {
                                errorHandling(error, "parseForumReportCreateHashError");
                                reportHash = null;
                            }
                            console.log('Parsed forum report with hash: ' + reportHash);

                            var exists = false;
                            if (reportHash !== null && reportHash !== 0 && globals && globals.reportsFound) {
                                for (var j = 0; j < globals.reportsFound.length; j++) {
                                    if (globals.reportsFound[j] == reportHash) {
                                        exists = true;
                                    }
                                }
                            }

                            if (reportHash == null) {
                                reportHash = '';
                            }
                            let index_btn_f_html = '<a href="#" id="gd_index_f_' + reportId + '" report_hash="' + reportHash + '" report_id="' + reportId + '" class="button rh' + reportHash + ' gd_btn_index" style="float: right;"><span class="left"><span class="right"><span id="gd_index_f_txt_' + reportId + '" class="middle" style="min-width: 50px;">' + translate.ADD + ' +</span></span></span></a>\n';
                            let share_btn_f_html = '<a href="#" id="gd_share_f_' + reportId + '" report_hash="' + reportHash + '" report_id="' + reportId + '" class="button gd_btn_share" style="float: right;"><span class="left"><span class="right"><span id="gd_sharae_f_txt_' + reportId + '" class="middle" style="min-width: 50px;">' + translate.SHARE + '</span></span></span></a>\n';
                            if (bSpy === true) {
                                $(reportElement).append('<div class="gd_indexer_footer" style="background: #fff; height: 28px; margin-top: -28px;">\n' +
                                    index_btn_f_html +
                                    share_btn_f_html +
                                    '    </div>');
                                $(reportElement).find('.resources, .small').css("text-align", "left");
                            } else {
                                $(reportElement).append('<div class="gd_indexer_footer" style="background: url(https://gpnl.innogamescdn.com/images/game/border/odd.png); height: 28px; margin-top: -52px;">\n' +
                                    index_btn_f_html +
                                    share_btn_f_html +
                                    '    </div>');
                                $(reportElement).find('.button, .simulator, .all').parent().css("padding-top", "24px");
                                $(reportElement).find('.button, .simulator, .all').siblings("span").css("margin-top", "-24px");
                            }

                            // Index click
                            if (exists === true) {
                                $('#gd_index_f_' + reportId).get(0).style.color = '#36cd5b';
                                $('#gd_index_f_txt_' + reportId).get(0).innerText = translate.ADDED + ' ✓';
                            } else {
                                $('#gd_index_f_' + reportId).click(function () {
                                    addForumReportById($(this).attr('report_id'), $(this).attr('report_hash'));
                                });
                            }

                            // Share click
                            $('#gd_share_f_' + reportId).click(function () {
                                console.log('jquery hash: ',$(this).attr('report_hash'));
                                console.log('jquery id: ',$(this).attr('report_id'));
                                var reportHash = $(this).attr('report_hash');
                                var reportId = $(this).attr('report_id');

                                var hashI = ('r' + reportHash).replace('-', 'm');
                                var content = '<b>Share this report on Discord:</b><br><ul>' +
                                    '    <li>1. Install the GrepoData bot in your Discord server (<a href="https://grepodata.com/discord" target="_blank">link</a>).</li>' +
                                    '    <li>2. Insert the following code in your Discord server.<br/>The bot will then create the screenshot for you!' +
                                    '    </ul><br/><input type="text" class="gd_copy_input_' + reportHash + '" value="' + `!gd report ${hashI}` + '"> <a href="#" class="gd_copy_command_' + reportHash + '">Copy to clipboard</a><span class="gd_copy_done_' + reportHash + '" style="display: none; float: right;"> Copied!</span>' +
                                    '    <br /><br /><small>Thank you for using <a href="https://grepodata.com" target="_blank">GrepoData</a>!</small>';

                                Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG).setContent(content);
                                addForumReportById(reportId, reportHash);

                                $(".gd_copy_command_" + reportHash).click(function () {
                                    $(".gd_copy_input_" + reportHash).select();
                                    document.execCommand('copy');

                                    $('.gd_copy_done_' + reportHash).get(0).style.display = 'block';
                                    setTimeout(function () {
                                        if ($('.gd_copy_done_' + reportHash).get(0)) {
                                            $('.gd_copy_done_' + reportHash).get(0).style.display = 'none';
                                        }
                                    }, 3000);
                                });
                            });
                        }
                    }
                }

            } catch (error) {
                errorHandling(error, "parseForumReport");
            }
        }

        function settingsTeams() {
            if ('active_teams' in globals) {
                if (globals.active_teams.length > 0) {
                    teamHtml = '<table class="gd-settings-team-table"><tr><th>'+translate.TEAM_NAME+'</th><th>'+translate.TEAM_ROLE+'</th><th>'+translate.TEAM_CONTRIBUTE+'</th><th>'+translate.TEAM_ACTION+'</th></tr>';
                    for (var j = 0; j < Object.keys(globals.active_teams).length; j++) {
                        var team = globals.active_teams[j];
                        teamHtml += `<tr>
                                                <td>${team.name}</td>
                                                <td>${team.role.replace('read', 'read-only')}</td>
                                                <td>`;
                        if (team.role !== 'read') {
                            teamHtml += `<div id="gd-team-cbx-contrib-${team.key}" class="checkbox_new `+(team.contribute==1?'checked':'')+`" title="Share new reports with this team">
                                                    <div class="cbx_icon"></div>
                                                    </div>`;
                        }
                        teamHtml += `</td>
                                                <td>
                                                    <a href="https://grepodata.com/profile/team/${team.key}" target="_blank">${translate.TEAM_ACTION_OVERVIEW} ></a>
                                                </td>
                                            </tr>`
                    }
                    teamHtml += '</table>';
                    $('#gd-settings-teams-container').html(teamHtml);

                    // actions
                    for (let j = 0; j < Object.keys(globals.active_teams).length; j++) {
                        $("#gd-team-cbx-contrib-"+globals.active_teams[j].key).click(function () {
                            var set_value = globals.active_teams[j].contribute == 1 ? 0 : 1
                            var do_contribute = set_value == 1;
                            let team = globals.active_teams[j];

                            toggleTeamContributions(team.key, do_contribute).then(response => {
                                if (response!==false) {
                                    globals.active_teams[j].contribute = set_value;
                                    savedSettingsIndicator();
                                    if (do_contribute === true) {
                                        $("#gd-team-cbx-contrib-"+team.key).get(0).classList.add("checked");
                                    } else {
                                        $("#gd-team-cbx-contrib-"+team.key).get(0).classList.remove("checked");
                                    }
                                }
                            })
                        });
                    }

                } else {
                    // user has no teams
                    $('#gd-settings-teams-container').html(`&nbsp;&nbsp;&nbsp;You have not yet joined any teams on world ${Game.world_id}. <a className="gd-login-btn"
                       href="https://grepodata.com/profile?action=new_team&world=`+Game.world_id+`" target="_blank">Create a new team</a>`);
                }
            } else {
                // Data is probably still loading, retry after a while
                setTimeout(settingsTeams, 500);
            }
        }

        function settings() {
            try {
                if (!$("#gd_indexer").get(0)) {
                    $(".settings-menu ul:last").append('<li id="gd_li"><svg aria-hidden="true" data-prefix="fas" data-icon="university" class="svg-inline--fa fa-university fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: #2E4154;width: 16px;width: 15px;vertical-align: middle;margin-top: -2px;"><path fill="currentColor" d="M496 128v16a8 8 0 0 1-8 8h-24v12c0 6.627-5.373 12-12 12H60c-6.627 0-12-5.373-12-12v-12H24a8 8 0 0 1-8-8v-16a8 8 0 0 1 4.941-7.392l232-88a7.996 7.996 0 0 1 6.118 0l232 88A8 8 0 0 1 496 128zm-24 304H40c-13.255 0-24 10.745-24 24v16a8 8 0 0 0 8 8h464a8 8 0 0 0 8-8v-16c0-13.255-10.745-24-24-24zM96 192v192H60c-6.627 0-12 5.373-12 12v20h416v-20c0-6.627-5.373-12-12-12h-36V192h-64v192h-64V192h-64v192h-64V192H96z"></path></svg><a id="gd_indexer" href="#" style="    margin-left: 4px;">GrepoData City Indexer</a></li>');

                    // contact/update
                    try {
                        var access_token = getLocalToken('gd_indexer_access_token');
                        var logged_in = !!access_token;
                        var jwtpayload = parseJwt(access_token);
                    } catch (e) {}

                    // Intro
                    // var layoutUrl = 'https' + window.getComputedStyle(document.getElementsByClassName('icon')[0], null).background.split('("https')[1].split('"')[0];
                    var settingsHtml = '<div id="gd_settings_container" style="display: none; position: absolute; top: 0; bottom: 0; right: 0; left: 232px; padding: 0px; overflow: auto;">\n' +
                        '    <div id="gd_settings" style="position: relative;">\n' +
                        '\t\t<div class="section">\n' +
                        '\t\t\t<div class="game_header bold" style="margin: -5px -10px 5px -10px; padding-left: 10px;">GrepoData city indexer settings</div>\n' +
                        '<a href="https://grepodata.com/message" target="_blank">Contact</a>' +
                        '<p style="font-style: italic; font-size: 10px; float: right; margin:0px;">GrepoData city indexer v' + gd_version + ' [<a href="https://api.grepodata.com/script/indexer.user.js" target="_blank">' + translate.CHECK_UPDATE + '</a>]</p>' +
                        '\t\t\t<p>' + translate.ABOUT + '.</p>';
                    if (logged_in) {
                        settingsHtml += '\t\t\t<p id="gdsettingslogged_in">' + translate.INDEX_LOGGED_IN + ((!!jwtpayload && 'username' in jwtpayload)?' <strong>'+jwtpayload.username+'</strong>':'') + ' <a id="gdsettingslogout" href="#">Sign out</a></p>';
                    } else {
                        settingsHtml += '\t\t\t<p id="gdsettingslogged_in">' + translate.INDEX_LOGGED_OUT + ' ' + '<a id="gdsettingslogin" href="#">Sign in</a></p>';
                    }
                    settingsHtml +=(count > 0 ? '<p>' + translate.COUNT_1 + count + translate.COUNT_2 + '.</p>' : '') +
                        '<p id="gd_s_saved" style="display: none; position: absolute; left: 10px; margin: 0; color: green;"><strong>' + translate.SAVED + ' ✓</strong></p> ' +
                        '<br/>\n';

                    // settings container
                    settingsHtml = settingsHtml + '<div style="max-height: '+(count > 0 ? 320 : 340)+'px; overflow-y: scroll; background: #FFEECA; border: 2px solid #d0be97;">';

                    // My teams (container)
                    settingsHtml += '<p style="margin-bottom: 10px; margin-left: 10px;"><strong>' + translate.MY_TEAMS + Game.world_id + '</strong>' +
                        '<br/>'+translate.MY_TEAMS_CONTRIBUTE+'</p>' +
                        '<div id="gd-settings-teams-container">&nbsp;&nbsp;&nbsp;&nbsp;Loading teams.. (refresh the page if this takes too long)</div>' +
                        '<hr>'

                    // Forum intel settings
                    settingsHtml += '\t\t\t<p style="margin-bottom: 10px; margin-left: 10px;"><strong>' + translate.COLLECT_INTEL + '</strong></p>\n' +
                        '\t\t\t<div style="margin-left: 30px; margin-bottom: 10px;" class="checkbox_new inbox_gd_enabled' + (gd_settings.inbox === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.COLLECT_INTEL_INBOX + '</div>\n' +
                        '\t\t\t</div>\n' +
                        '\t\t\t<div style="margin-left: 30px;" class="checkbox_new forum_gd_enabled' + (gd_settings.forum === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.COLLECT_INTEL_FORUM + '</div>\n' +
                        '\t\t\t</div>\n' +
                        '\t\t\t<br><br><hr>\n';

                    // Stats link
                    settingsHtml += '\t\t\t<p style="margin-left: 10px; display: inline-flex; height: 14px;"><strong>' + translate.STATS_LINK_TITLE + '</strong> <span style="background: '+gd_icon+'; width: 26px; height: 24px; margin-top: -5px; margin-left: 10px;"></span></p>\n' +
                        '\t\t\t<div style="margin-left: 30px;" class="checkbox_new stats_gd_enabled' + (gd_settings.stats === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.STATS_LINK + '</div>\n' +
                        '\t\t\t</div>\n' +
                        '\t\t\t<br><br><hr>\n';

                    // Context menu
                    settingsHtml += '\t\t\t<p style="margin-left: 10px; display: inline-flex; height: 14px;"><strong>' + translate.CONTEXT_TITLE + '</strong> <span style="background: '+gd_icon_intel+'; width: 50px; height: 50px; transform: scale(0.6); margin-top: -18px;"></span></p>\n' +
                        '\t\t\t<div style="margin-left: 30px;" class="checkbox_new context_gd_enabled' + (gd_settings.context === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.CONTEXT_INFO + '</div>\n' +
                        '\t\t\t</div>\n' +
                        '\t\t\t<br><br><hr>\n';

                    // Command overview settings
                    settingsHtml += '\t\t\t<p style="margin-left: 10px;"><strong>' + translate.CMD_OVERVIEW_TITLE + '</strong></p>\n' +
                        '\t\t\t<div style="margin-left: 30px;" class="checkbox_new command_cancel_time_gd_enabled' + (gd_settings.command_cancel_time === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.CMD_DEPARTURE_INFO + '</div>\n' +
                        '\t\t\t</div>\n' +
                        '\t\t\t<br><br><hr>\n';

                    // Forum reactions settings
                    settingsHtml += '\t\t\t<p style="margin-left: 10px;"><strong>' + translate.FORUM_REACTIONS_TITLE + '</strong> <img style="height: 18px" src="'+react_icon+'"/></p>\n' +
                        '\t\t\t<div style="margin-left: 30px;" class="checkbox_new forum_reactions_gd_enabled' + (gd_settings.forum_reactions === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.FORUM_REACTIONS_INFO + '</div>\n' +
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
                        '\t\t\t<br/><hr>';

                    // Other
                    settingsHtml += '\t\t\t<p style="margin-left: 10px; display: inline-flex; height: 14px;"><strong>'+translate.SETTINGS_OTHER+'</strong></p></br>\n' +
                        '\t\t\t<div style="margin-left: 30px;" class="checkbox_new bug_reports_gd_enabled' + (gd_settings.bug_reports === true ? ' checked' : '') + '">\n' +
                        '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.BUG_REPORTS + '</div>\n' +
                        '\t\t\t</div>\n' +
                        '\t\t\t<br><br>\n';

                    // Footer
                    settingsHtml += '</div>' +
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
                    $("#gdsettingslogout").click(function () {
                        $("#gdsettingslogged_in").hide();
                        deleteLocalToken('gd_indexer_access_token');
                        deleteLocalToken('gd_indexer_refresh_token');
                        HumanMessage.success('GrepoData logged out succesfully.');
                        showLoginPopup();
                    });
                    $("#gdsettingslogin").click(function () {
                        $("#gdsettingslogged_in").hide();
                        showLoginPopup();
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
                    $(".command_cancel_time_gd_enabled").click(function () {
                        settingsCbx('command_cancel_time', !gd_settings.command_cancel_time);
                    });
                    $(".forum_reactions_gd_enabled").click(function () {
                        settingsCbx('forum_reactions', !gd_settings.forum_reactions);
                    });
                    $(".context_gd_enabled").click(function () {
                        settingsCbx('context', !gd_settings.context);
                    });
                    $(".bug_reports_gd_enabled").click(function () {
                        settingsCbx('bug_reports', !gd_settings.bug_reports);
                    });
                    $(".keys_enabled_gd_enabled").click(function () {
                        settingsCbx('keys_enabled', !gd_settings.keys_enabled);
                    });

                    if (gdsettings === true) {
                        $('.settings-container').get(0).style.display = "none";
                        $('#gd_settings_container').get(0).style.display = "block";
                    }

                    settingsTeams();
                }
            } catch (error) {
                errorHandling(error, "settings");
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
            savedSettingsIndicator();
        }

        function savedSettingsIndicator() {
            $('#gd_s_saved').get(0).style.display = 'block';
            setTimeout(function () {
                if ($('#gd_s_saved').get(0)) {
                    $('#gd_s_saved').get(0).style.display = 'none';
                }
            }, 3000);
        }

        function saveSettings() {
            setLocalToken('globals_s', encodeJsonToHash(JSON.stringify(gd_settings)))
        }

        function toggleTeamContributions(index_key, do_contribute) {
            return new Promise(resolve => {
                try {
                    getAccessToken().then(access_token => {
                        if (access_token === false) {
                            resolve(false);
                        } else {
                            // Toggle team contributions
                            $.ajax({
                                method: "put",
                                headers: {'access_token': access_token},
                                url: backend_url + "/indexer/settings/contribute",
                                data: {
                                    index_key: index_key,
                                    contribute: do_contribute
                                }
                            }).error(function (err) {
                                console.error(err);
                                resolve(false);
                            }).done(function (response) {
                                resolve(response);
                            });
                        }
                    });
                } catch (error) {
                    errorHandling(error, "toggleTeamContributions");
                    resolve(false);
                }
            });
        }

        var openIntelWindows = {};
        function loadTownIntel(id, town_name, player_name) {
            try {

                getAccessToken().then(access_token => {
                    if (access_token === false) {
                        HumanMessage.error('GrepoData: login is required to view intel');
                        showLoginPopup();
                        $('#gd_index_rep_txt').get(0).innerText = translate.ADD + ' +';
                    } else {

                        // Create a new dialog
                        var content_id = player_name + id;
                        content_id = content_id.replace(/[^a-zA-Z]+/g, '');
                        if (openIntelWindows[content_id]) {
                            try {
                                openIntelWindows[content_id].close();
                            } catch (e) {console.log("unable to close window", e);}
                        }
                        var intelUrl = frontend_url + '/intel/town/'+Game.world_id+'/'+id;
                        // var intel_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                        //     '<a target="_blank" href="'+intelUrl+'" class="write_message" style="background: ' + gd_icon + '"></a>&nbsp;&nbsp;' + translate.TOWN_INTEL + ': ' + town_name + (player_name!=''?(' (' + player_name + ')'):''),
                        //     {position: ['center','center'], width: 660, height: 590, minimizable: true});
                        var intel_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                            translate.TOWN_INTEL + ': ' + town_name + (player_name!=''?(' (' + player_name + ')'):''),
                            {width: 660, height: 590, minimizable: true});
                        // intel_window.setWidth(600);
                        // intel_window.setHeight(590);
                        openIntelWindows[content_id] = intel_window;

                        // Window content
                        var content = '<div class="gdintel_'+content_id+'" style="width: 660px; height: 500px;"><div style="text-align: center">' +
                            '<p style="font-size: 20px; padding-top: 180px;">Loading intel..</p>' +
                            '<a style="font-size: 11px;" href="' + intelUrl + '" target="_blank">' + intelUrl + '</a>' +
                            '</div></div>';
                        intel_window.setContent(content);
                        var intelWindowElement = $('.gdintel_'+content_id).parent();
                        $(intelWindowElement).css({ top: 43 });

                        // Get town intel from backend
                        $.ajax({
                            method: "get",
                            headers: { 'access_token': access_token},
                            url: backend_url + "/indexer/v2/town?world=" + world + "&town_id=" + id
                        }).error(function (err) {
                            console.error(err);
                            renderTownIntelError(content_id, intelUrl);
                        }).done(function (response) {
                            renderTownIntelWindow(response, id, town_name, player_name, content_id);
                        });
                    }
                });
            } catch (error) {
                errorHandling(error, "loadTownIntel");
                renderTownIntelError(content_id, intelUrl);
            }
        }

        function renderTownIntelError(content_id, intelUrl) {
            $('.gdintel_'+content_id).empty();
            $('.gdintel_'+content_id).append('<div style="text-align: center">' +
                '<p style="padding-top: 100px;">Sorry, no intel available at the moment.<br/>Please <a href="https://grepodata.com/message" target="_blank" style="">contact us</a> if this error persists.</p>' +
                '<p style="padding-top: 50px;">Alternatively, you can view this town\'s intel on grepodata.com:<br/>' +
                '<a href="' + intelUrl + '" target="_blank" style="">' + intelUrl + '</a></p></div>');
        }

        function renderTownIntelWindow(data, id, town_name, player_name, content_id) {
            var intelUrl = 'https://grepodata.com/indexer';
            try {
                console.log(data);
                intelUrl = 'https://grepodata.com/intel/town/'+Game.world_id+'/'+id;
                var unitHeight = 255;
                var notesHeight = 170;

                if (data.intel==null || data.intel.length <= 1) {
                    unitHeight = 150;
                    notesHeight = 275;
                }

                // Intel content
                var tooltips = [];
                $('.gdintel_'+content_id).empty();

                // Title
                var townHash = getTownHash(parseInt(id), town_name, data.ix, data.iy);
                var playerHash = getPlayerHash(data.player_id, data.player_name);
                var title = '<div style="margin-bottom: 10px;">' +
                    '<a href="#'+townHash+'" class="gp_town_link"><img alt="" src="/images/game/icons/town.png" style="padding-right: 2px; vertical-align: top;">'+ data.name +'</a> ' +
                    '(<a href="#'+playerHash+'" class="gp_player_link"> <img alt="" src="/images/game/icons/player.png" style="padding-right: 2px; vertical-align: top;">'+ data.player_name +'</a>)' +
                    '<a href="'+intelUrl+'" class="gd_ext_ref" target="_blank" style="float: right;">View on grepodata.com</a></div>';
                $('.gdintel_'+content_id).append(title);

                // Buildings
                var build = '<div class="gd_build_' + id + '" style="padding-bottom: 4px;">';
                var date = '';
                var hasBuildings = false;
                for (var j = 0; j < Object.keys(data.buildings).length; j++) {
                    var name = Object.keys(data.buildings)[j];
                    var value = data.buildings[name].level.toString();
                    if (value != null && value != '' && value.indexOf('%') < 0) {
                        date = data.buildings[name].date;
                        build = build + '<div class="building_header building_icon40x40 ' + name + ' regular" id="icon_building_' + name + '" ' +
                            'style="margin-left: 3px; width: 32px; height: 32px;">' +
                            '<div style="position: absolute; top: 17px; margin-left: 8px; z-index: 10; color: #fff; font-size: 12px; font-weight: 700; text-shadow: 1px 1px 3px #000;">' + value + '</div>' +
                            '</div>';
                    }
                    if (name != 'wall') {
                        hasBuildings = true;
                    }
                }
                build = build + '</div>';
                if (hasBuildings == true) {
                    $('.gdintel_'+content_id).append(build);
                    $('.gd_build_' + id).tooltip('Buildings as of: ' + date);
                    unitHeight -= 40;
                }

                // Units table
                var table =
                    '<div class="game_border" style="max-height: 100%;">\n' +
                    '   <div class="game_border_top"></div><div class="game_border_bottom"></div><div class="game_border_left"></div><div class="game_border_right"></div>\n' +
                    '   <div class="game_border_corner corner1"></div><div class="game_border_corner corner2"></div><div class="game_border_corner corner3"></div><div class="game_border_corner corner4"></div>\n' +
                    '   <div class="game_header bold">\n' +
                    translate.INTEL_UNITS + '\n' +
                    '   </div>\n' +
                    '   <div style="height: '+unitHeight+'px;">' +
                    '     <ul class="game_list" style="display: block; width: 100%; height: '+unitHeight+'px; overflow-x: hidden; overflow-y: auto;">\n';
                var bHasIntel = false;
                var maxCost = 0;
                var maxCostUnits = [];
                var expiredIntelHeader = false;
                for (var j = 0; j < Object.keys(data.intel).length; j++) {
                    var intel = data.intel[j];
                    var row = '';

                    // Check intel value
                    if (intel.cost && intel.cost > maxCost) {
                        maxCost = intel.cost;
                        maxCostUnits = intel.units;
                    }

                    // Type
                    if (intel.type != null && intel.type != '') {
                        bHasIntel = true;
                        var typeUrl = '';
                        var tooltip = '';
                        var flip = true;
                        var isWisdom = false;
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
                            case 'wisdom':
                                isWisdom = true
                                tooltip = 'Wisdom';
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
                        var typeHtml = '';
                        if (isWisdom == true) {
                            typeHtml = '<div><div class="power_icon45x45 wisdom intel-type-' + id + '-' + j + '" style="transform: scale(.8); margin-left: 2px; margin-top: -1px;"></div></div>';
                        } else {
                            typeHtml = '<div style="position: absolute; height: 0px; margin-top: -5px; ' +
                                (flip ? '-moz-transform: scaleX(-1); -o-transform: scaleX(-1); -webkit-transform: scaleX(-1); transform: scaleX(-1); filter: FlipH; -ms-filter: "FlipH";' : '') +
                                '"><div style="background: url(' + typeUrl + ');\n' +
                                '    padding: 0;\n' +
                                '    height: 50px;\n' +
                                '    width: 50px;\n' +
                                '    position: relative;\n' +
                                '    display: inherit;\n' +
                                '    transform: scale(0.6, 0.6);-ms-transform: scale(0.6, 0.6);-webkit-transform: scale(0.6, 0.6);' +
                                '    box-shadow: 0px 0px 9px 0px #525252;" class="intel-type-' + id + '-' + j + '"></div></div>';
                        }
                        row = row +
                            '<div style="display: table-cell; width: 50px;">' +
                            typeHtml +
                            '</div>';
                        tooltips.push({id: 'intel-type-' + id + '-' + j, text: tooltip});
                    } else {
                        row = row + '<div style="display: table-cell;"></div>';
                    }

                    // Date
                    row = row + '<div style="display: table-cell; width: 65px;" class="bold"><div style="margin-top: 3px; position: absolute;">' + intel.date.replace(' ', '<br/>') + '</div></div>';

                    // units
                    var unitHtml = '';
                    var killed = false;
                    var hasUnits = false;
                    for (var i = 0; i < Object.keys(intel.units).length; i++) {
                        hasUnits = true;
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
                        if (unit.killed && unit.killed != 0) {
                            killed = true;
                        }
                        if (unit.name === 'unknown' || unit.name === 'unknown_naval') {
                            unitHtml = unitHtml +
                                '<div class="unit_icon25x25 ' + unit.name + ' intel-unit-' + unit.name + '-' + id + '-' + j + '" style="overflow: unset; font-size: ' + size + 'px; text-shadow: 1px 1px 3px #000; color: #fff; font-weight: 700; border: 1px solid #626262; padding: 10px 0 0 0; line-height: 14px; height: 14px; text-align: right; margin-right: 2'+(2+((11-size)*2))+'px; width: 24px;">?';
                            if(unit.killed && unit.killed != 0) {
                                unitHtml = unitHtml + '<div style="background-position: 0 -162px; transform: scale(.8); background-repeat: no-repeat; width: 18px; height: 17px; background-image: url(https://gpnl.innogamescdn.com/images/game/autogenerated/resources/resources_small_2.95.png);"></div>';
                                unitHtml = unitHtml + '   <div class="report_losts" style="position: absolute; margin: -13px 0 0 17px; font-size: 9px; text-shadow: none;">~' + unit.killed + '</div>\n';
                            }
                            unitHtml = unitHtml + '</div>';

                            if (unit.killed != '?') {
                                tooltips.push({id: 'intel-unit-' + unit.name + '-' + id + '-' + j, text: 'This friendly attack killed roughly '+unit.killed+' ' + (unit.name==='unknown'?'land':'sea') + ' population (this is estimated based on the battle points gained)'});
                            }
                        } else {
                            unitHtml = unitHtml +
                                '<div class="unit_icon25x25 ' + unit.name + ' intel-unit-' + unit.name + '-' + id + '-' + j + '" style="overflow: unset; font-size: ' + size + 'px; text-shadow: 1px 1px 3px #000; color: #fff; font-weight: 700; border: 1px solid #626262; padding: 10px 0 0 0; line-height: 13px; height: 15px; text-align: right; margin-right: 2px;">' +
                                unit.count +
                                (unit.killed && unit.killed != 0 ? '   <div class="report_losts" style="position: absolute; margin: 4px 0 0 0; font-size: ' + (size - 1) + 'px; text-shadow: none;">-' + unit.killed + '</div>\n' : '') +
                                '</div>';

                            tooltips.push({id: 'intel-unit-' + unit.name + '-' + id + '-' + j, text: unit.count + ' ' + unit.name.replace('_',' ')});
                        }
                    }

                    // Append hero to unit list
                    var hasHero = false;
                    if (intel.hero != null && intel.hero != "") {
                        hasHero = true;
                        unitHtml = unitHtml +
                            '<div class="hero_icon_border golden_border intel-hero-' + id + '-' + j + '" style="display: inline-block;">\n' +
                            '    <div class="hero_icon_background">\n' +
                            '        <div class="hero_icon hero25x25 ' + intel.hero.toLowerCase() + '"></div>\n' +
                            '    </div>\n' +
                            '</div>';
                        tooltips.push({id: 'intel-hero-' + id + '-' + j, text: intel.hero.toLowerCase()});
                    }

                    // Append god to unit list
                    if (intel.god != null && intel.god != "") {
                        unitHtml = unitHtml +
                            '<div style="float: right; margin-top: -2px; margin-left: 10px;" ' +
                            'class="god_micro ' + intel.god.toLowerCase() + '" title="' + intel.god + '"></div>';
                        tooltips.push({id: 'intel-god-' + id + '-' + j, text: intel.god});
                    }

                    if (!hasUnits && !hasHero) {
                        // no units => town is empty
                        unitHtml = unitHtml + '<div style="width:200px;">No units in town</div>';
                    }

                    row = row + '<div style="display: table-cell;"><div><div class="origin_town_units" style="padding-left: 30px; margin: 5px 0 5px 0; ' + (killed ? 'height: 37px;' : 'height: 27px;') + '">' + unitHtml + '</div></div></div>';

                    // Wall
                    if (intel.wall !== null && intel.wall !== '' && (!isNaN(0) || intel.wall.indexOf('%') < 0)) {
                        row = row +
                            '<div style="display: table-cell; width: 50px; float: right;" class="intel-wall-' + id + '-' + j + '">' +
                            '<div class="sprite-image" style="display: block; font-weight: 600; ' + (killed ? '' : 'padding-top: 10px;') + '">' +
                            '<div style="position: absolute; top: 19px; margin-left: 8px; z-index: 10; color: #fff; font-size: 10px; text-shadow: 1px 1px 3px #000;">' + intel.wall + '</div>' +
                            '<img src="https://gpnl.innogamescdn.com/images/game/main/buildings_sprite_40x40.png" alt="icon" ' +
                            'width="40" height="40" style="object-fit: none;object-position: -40px -80px;width: 40px;height: 40px;' +
                            'transform: scale(0.68, 0.68);-ms-transform: scale(0.68, 0.68);-webkit-transform: scale(0.68, 0.68);' +
                            'padding-left: -7px; margin: -48px 0 0 0px; position:absolute;">' +
                            '</div></div>';
                        tooltips.push({id: 'intel-wall-' + id + '-' + j, text: 'wall: ' + intel.wall});
                    } else {
                        row = row + '<div style="display: table-cell;"></div>';
                    }

                    // Stonehail
                    if (data.has_stonehail === true && intel.stonehail && intel.stonehail.building && intel.stonehail.value) {
                        row = row +
                            '<div style="display: table-cell; width: 50px; float: right;" class="intel-stonehail-' + id + '-' + j + '">' +
                            '<div class="building_header building_icon40x40 ' + intel.stonehail.building + ' regular" style="margin-top: -54px; transform: scale(0.68, 0.68); -ms-transform: scale(0.68, 0.68); -webkit-transform: scale(0.68, 0.68);">' +
                            '<div style="position: absolute; top: 0; margin-left: 4px; z-index: 10; color: #fff; font-size: 16px; font-weight: 700; text-shadow: 1px 1px 3px #000;">' + intel.stonehail.value + '</div></div>' +
                            '</div>';
                        tooltips.push({id: 'intel-stonehail-' + id + '-' + j, text: 'stonehail: ' + intel.stonehail.building + ' ' + intel.stonehail.value});
                    } else if (data.has_stonehail === true) {
                        row = row + '<div style="display: table-cell;"></div>';
                    }

                    // Check expired intel header;
                    if ('is_previous_owner_intel' in intel && intel.is_previous_owner_intel == true && expiredIntelHeader === false) {
                        expiredIntelHeader = true;
                        var expired_header = '<li style="padding: 15px 10px 0;">' +
                            '<p><strong>Expired intel:</strong> The intel below was collected when this town had a different owner.</p>' +
                            '</li>';
                        table = table + expired_header;
                    }

                    var rowHeader = '<li class="' + (j % 2 === 0 ? 'odd' : 'even') + ' gd-intel-row-'+id+'-'+j+'" style="display: inherit; width: 100%; padding: 0 0 ' + (killed ? '0' : '4px') + ' 0;">';
                    if (intel.type === 'spy') {
                        tooltips.push({id: 'gd-intel-row-' + id + '-' + j, text: 'Silver used: ' + intel.silver});
                    }
                    table = table + rowHeader + row + '</li>\n';
                }

                if (bHasIntel == false) {
                    table = table + '<li class="even" style="display: inherit; width: 100%;"><div style="text-align: center;">' +
                        '<strong>No unit intelligence available</strong><br/>' +
                        'You have not yet indexed any reports about this town.<br/><br/>' +
                        '<span style="font-style: italic;">note: intel about team owners may be hidden by the team admin</span></div></li>\n';
                }

                table = table + '</ul></div></div>';
                $('.gdintel_'+content_id).append(table);
                for (var j = 0; j < tooltips.length; j++) {
                    $('.' + tooltips[j].id).tooltip(tooltips[j].text);
                }

                // notes
                var notesHtml =
                    '<div class="game_border" style="max-height: 100%; margin-top: 10px;">\n' +
                    '   <div class="game_border_top"></div><div class="game_border_bottom"></div><div class="game_border_left"></div><div class="game_border_right"></div>\n' +
                    '   <div class="game_border_corner corner1"></div><div class="game_border_corner corner2"></div><div class="game_border_corner corner3"></div><div class="game_border_corner corner4"></div>\n' +
                    '   <div class="game_header bold">\n' +
                    translate.INTEL_NOTE_TITLE + '\n' +
                    '   </div>\n' +
                    '   <div style="height: '+notesHeight+'px;">' +
                    '     <ul class="game_list" style="display: block; width: 100%; height: '+notesHeight+'px; overflow-x: hidden; overflow-y: auto;">\n';
                notesHtml = notesHtml + '<li class="even" style="display: flex; justify-content: space-around; align-items: center;" id="gd_new_note_'+content_id+'">' +
                    '<div style=""><strong>Add note: </strong><img alt="" src="/images/game/icons/player.png" style="vertical-align: top; padding-right: 2px;">'+Game.player_name+'</div>' +
                    '<div style="width: '+(60 - Game.player_name.length)+'%;"><input id="gd_note_input_'+content_id+'" type="text" placeholder="Add a note about this town" style="width: 100%;"></div>' +
                    '<div style=""><div id="gd_adding_note_'+content_id+'" style="display: none;">Saving..</div><div id="gd_add_note_'+content_id+'" gd-town-id="'+id+'" class="button_new" style="top: -1px;"><div class="left"></div><div class="right"></div><div class="caption js-caption">Add<div class="effect js-effect"></div></div></div></div>' +
                    '</li>\n';
                var bHasNotes = false;
                for (var j = 0; j < Object.keys(data.notes).length; j++) {
                    var note = data.notes[j];
                    bHasNotes = true;
                    notesHtml = notesHtml + getNoteRowHtml(note, content_id, j);
                }

                if (bHasNotes == false) {
                    notesHtml = notesHtml + '<li class="odd" style="display: inherit; width: 100%;"><div style="text-align: center;">' +
                        translate.INTEL_NOTE_NONE +
                        '</div></li>\n';
                }

                notesHtml = notesHtml + '</ul></div></div>';
                $('.gdintel_'+content_id).append(notesHtml);

                // Add note
                $('#gd_add_note_'+content_id).click(function () {
                    var town_id = $('#gd_add_note_'+content_id).attr('gd-town-id');
                    var note = $('#gd_note_input_'+content_id).val().split('<').join(' ').split('>').join(' ').split('#').join(' ');
                    if (note != '') {
                        $('.gd_note_error_msg').hide();
                        if (note.length > 500) {
                            $('#gd_new_note_'+content_id).after('<li class="even gd_note_error_msg" style="display: inherit; width: 100%;">'+
                                '<div style="text-align: center;"><strong>Note is too long.</strong> A note can have a maximum of 500 characters.</div>' +
                                '</li>\n');
                        } else {
                            $('#gd_add_note_'+content_id).hide();
                            $('#gd_adding_note_'+content_id).show();
                            $('#gd_note_input_'+content_id).prop('disabled',true);
                            saveNewNote(town_id, note, content_id);
                        }
                    }
                });

                // Del note
                $('.gd_del_note_'+content_id).click(function () {
                    var note_id = $(this).attr('gd-note-id');
                    $(this).hide();
                    $(this).after('<p style="margin: 0;">Note deleted</p>');
                    $('#gd_note_'+content_id+'_'+note_id).css({ opacity: 0.4 });
                    saveDelNote(note_id);
                });

                var world = Game.world_id;
                var exthtml =
                    '<div style="display: list-item" class="gd_ext_ref">' +
                    (data.player_id != null && data.player_id != 0 ? '   <a href="' + frontend_url + '/intel/player/' + world + '/' + data.player_id + '" target="_blank" style="float: left;"><img alt="" src="/images/game/icons/player.png" style="float: left; padding-right: 2px;">'+translate.INTEL_SHOW_PLAYER+' (' + data.player_name + ')</a>' : '') +
                    (data.alliance_id != null && data.alliance_id != 0 ? '   <a href="' + frontend_url + '/intel/alliance/' + world + '/' + data.alliance_id + '" target="_blank" style="float: right;"><img alt="" src="/images/game/icons/ally.png" style="float: left; padding-right: 2px;">'+translate.INTEL_SHOW_ALLIANCE+'</a>' : '') +
                    '</div>';
                $('.gdintel_'+content_id).append(exthtml);
                $('.gd_ext_ref').tooltip('Opens in new tab');

            } catch (error) {
                errorHandling(error, "renderTownIntelWindow");
                renderTownIntelError(content_id, intelUrl);
            }
        }

        function getNoteRowHtml(note, content_id, i=0) {
            var row = '<li id="gd_note_'+content_id+'_'+note.note_id+'" class="' + (i % 2 === 0 ? 'odd' : 'even') + '" style="display: inherit; width: 100%; padding: 0;">';
            row = row + '<div style="display: table-cell; padding: 0 7px; width: 200px;">' +
                (note.poster_id > 0 ? '<a href="#'+getPlayerHash(note.poster_id, note.poster_name)+'" class="gp_player_link">': '') +
                '<img alt="" src="/images/game/icons/player.png" style="padding-right: 2px; vertical-align: top;">' +
                note.poster_name+(note.poster_id > 0 ?'</a>':'')+'<br/>'+note.date+
                '</div>';
            row = row + '<div style="display: table-cell; padding: 0 7px; width: 300px; vertical-align: middle;"><strong>'+note.message+'</strong></div>';

            if (Game.player_name == note.poster_name) {
                row = row + '<div style="display: table-cell; float: right; margin-top: -25px; margin-right: 5px;"><a id="gd_del_note_'+content_id+'_'+note.note_id+'" class="gd_del_note_'+content_id+'" gd-note-id="'+note.note_id+'" style="float: right;">Delete</a></div>';
            } else {
                row = row + '<div style="display:"></div>';
            }

            row = row + '</li>\n';
            return row;
        }

        function saveNewNote(town_id, note, content_id) {
            try {
                getAccessToken().then(access_token => {
                    if (access_token !== false) {
                        $.ajax({
                            url: backend_url + "/indexer/v2/addnote",
                            data: {
                                access_token: access_token,
                                town_id: town_id,
                                message: note,
                                world: Game.world_id,
                                poster_name: Game.player_name,
                                poster_id: Game.player_id,
                            },
                            type: 'post',
                            crossDomain: true,
                            dataType: 'json',
                            timeout: 30000
                        }).fail(function (err) {
                            console.log("Error saving note: ", err);

                            var errormsg = 'Please try again later or contact us if this error persists.';
                            if (err.responseJSON.error_code
                                && (
                                    err.responseJSON.error_code === 7201  // No teams for user/world
                                )
                            ) {
                                var errormsg = 'You need to join a GrepoData team (on this world) in order to use notes.';
                            }
                            $('#gd_new_note_'+content_id).after('<li class="even gd_note_error_msg" style="display: inherit; width: 100%;">'+
                                '<div style="display: table-cell; padding: 0 7px; color: #ce2508;"><strong>Error saving note.</strong> '+errormsg+'</div>' +
                                '</li>\n');
                            $('#gd_add_note_'+content_id).show();
                            $('#gd_adding_note_'+content_id).hide();
                            $('#gd_note_input_'+content_id).prop('disabled',false);
                        }).done(function (response) {
                            if (response.note) {
                                $('#gd_new_note_'+content_id).after(getNoteRowHtml(response.note, content_id));
                                $('#gd_note_input_'+content_id).val('');
                                $('#gd_del_note_'+content_id+'_'+response.note.note_id).click(function () {
                                    var note_id = $(this).attr('gd-note-id');
                                    $(this).hide();
                                    $(this).after('<p style="margin: 0;">Note deleted</p>');
                                    $('#gd_note_'+content_id+'_'+note_id).css({ opacity: 0.4 });
                                    saveDelNote(note_id);
                                });
                            }
                            $('#gd_add_note_'+content_id).show();
                            $('#gd_adding_note_'+content_id).hide();
                            $('#gd_note_input_'+content_id).prop('disabled',false);
                        });
                    } else {
                        showLoginPopup();
                    }
                });
            } catch (error) {
                errorHandling(error, "saveNewNote");
            }
        }

        function saveDelNote(note_id) {
            try {
                getAccessToken().then(access_token => {
                    if (access_token !== false) {
                        $.ajax({
                            url: backend_url + "/indexer/v2/delnote",
                            data: {
                                access_token: access_token,
                                note_id: note_id,
                                world: Game.world_id,
                            },
                            type: 'post',
                            crossDomain: true,
                            dataType: 'json',
                            timeout: 30000
                        }).fail(function (err) {
                            console.log("Error deleting note: ", err);
                        }).done(function (response) {
                            console.log("Note deleted: ", response);
                        });
                    } else {
                        showLoginPopup();
                    }
                });
            } catch (error) {
                errorHandling(error, "saveDeletedNote");
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
                        var statsBtn = '<a target="_blank" href="https://grepodata.com/player?world=' + gd_w.Game.world_id + '&id=' + player_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
                        $('#player_buttons').filter(':first').append(statsBtn);
                    } else if ('alliance_id' in json && action.search("/alliance") >= 0) {
                        // Add stats button to alliance profile
                        var alliance_id = json.alliance_id;
                        var statsBtn = '<a target="_blank" href="https://grepodata.com/alliance/' + gd_w.Game.world_id + '/' + alliance_id + '" class="write_message" style="background: ' + gd_icon + '; margin: 5px;"></a>';
                        $('#player_info > ul > li').filter(':first').append(statsBtn);
                    }
                } catch (error) {
                    console.log(error);
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
            try {
                if (!!xhr.responseText) {
                    var town_id = xhr.responseText.match(/\[town\].*?(?=\[)/g)[0];
                    town_id = town_id.substring(6);

                    // Add intel button and handle click event
                    var button_style = 'float: right; bottom: 5px;';
                    try {
                        if (molehole_active) {
                            button_style = '';
                        }
                    } catch (e) {}
                    var intelBtn = '<div id="gd_index_town_' + town_id + '" town_id="' + town_id + '" class="button_new gdtv' + town_id + '" style="'+button_style+'">' +
                        '<div class="left"></div>' +
                        '<div class="right"></div>' +
                        '<div class="caption js-caption">' + translate.VIEW + '<div class="effect js-effect"></div></div></div>';
                    $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.odd').filter(':first').append(intelBtn);

                    // Handle click:  view intel
                    $('#gd_index_town_' + town_id).click(function () {
                        var town_name = town_id;
                        var player_name = '';
                        try {
                            panel_root = $('.info_tab_content_' + town_id).parent().parent().parent().get(0);
                            town_name = panel_root.getElementsByClassName('ui-dialog-title')[0].innerText;
                            player_name = panel_root.getElementsByClassName('gp_player_link')[0].innerText;
                        } catch (e) {
                            console.log(e);
                        }
                        //panel_root.getElementsByClassName('active')[0].classList.remove('active');
                        loadTownIntel(town_id, town_name, player_name);
                    });
                }

                if (gd_settings.stats === true) {
                    try {
                        // Add stats button to player name
                        var player_id = xhr.responseText.match(/player_id = [0-9]*,/g);
                        if (player_id != null && player_id.length > 0) {
                            player_id = player_id[0].substring(12, player_id[0].search(','));
                            var statsBtn = '<a target="_blank" href="https://grepodata.com/player?world=' + gd_w.Game.world_id + '&id=' + player_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
                            $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.even > div.list_item_right').eq(1).append(statsBtn);
                            $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.even > div.list_item_right').css("min-width", "140px");
                        }
                        // Add stats button to ally name
                        var ally_id = xhr.responseText.match(/alliance_id = parseInt\([0-9]*, 10\),/g);
                        if (ally_id != null && ally_id.length > 0) {
                            ally_id = ally_id[0].substring(23, ally_id[0].search(','));
                            var statsBtn2 = '<a target="_blank" href="https://grepodata.com/alliance?world=' + gd_w.Game.world_id + '&id=' + ally_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
                            $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.odd > div.list_item_right').filter(':first').append(statsBtn2);
                            $('.info_tab_content_' + town_id + ' > .game_inner_box > .game_border > ul.game_list > li.odd > div.list_item_right').filter(':first').css("min-width", "140px");
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            } catch (error) {
                let town_bb = '';
                if (!!xhr && 'responseText' in xhr) {
                    town_bb = xhr.responseText;
                }
                errorHandling(error, "enhanceTownInfoPanel", {town_bb: town_bb});
            }
        }

        // Loads a list of report ids that have already been indexed by the current user or their allies.
        var user_has_team = false;
        function loadIndexHashlist(check_login = false, startup = false, is_retry_attempt = false) {
            try {
                if (verbose) {
                    console.log("Loading grepodata hashlist")
                }
                getAccessToken().then(access_token => {
                    if (access_token === false) {
                        if (startup === true) {
                            showLoginNotification();
                        }
                    } else {
                        $.ajax({
                            method: "get",
                            headers: {"access_token": access_token},
                            url: backend_url + "/indexer/v2/getlatest?world=" + Game.world_id
                        }).done(function (b) {
                            try {
                                var has_hashes = false;
                                var has_teams = false;
                                if (b['hashlist'] !== undefined) {
                                    globals.reportsFound = [];
                                    $.each(b['hashlist'], function (b, d) {
                                        has_hashes = true;
                                        globals.reportsFound.push(d)
                                    });
                                }
                                if (b['active_teams'] !== undefined) {
                                    globals.active_teams = [];
                                    $.each(b['active_teams'], function (b, d) {
                                        has_teams = true;
                                        user_has_team = true;
                                        globals.active_teams.push(d)
                                    });
                                }
                                if (b['active_threads'] !== undefined) {
                                    globals.active_threads = [];
                                    $.each(b['active_threads'], function (b, d) {
                                        globals.active_threads.push(d)
                                    });
                                }
                                if (startup && has_hashes && !has_teams) {
                                    // user has been using the indexer but is not part of a team on this world (only run this once at startup)
                                    showNoTeamNotification();
                                }
                            } catch (u) {}
                        }).fail(function (error) {
                            console.log('Unable to get latest hashlist: ', error);
                            if (error.responseJSON.error_code
                                && error.responseJSON.error_code === 3003
                                && is_retry_attempt === false
                            ) {
                                // invalid JWT (probably expired, not caught because local client time is out of sync)
                                // try to force refresh the access token
                                getAccessToken(true).then(access_token => {
                                    if (access_token === false) {
                                        // If the force refresh was not succesful, we need a new explicit login from the user
                                        showLoginNotification();
                                    } else {
                                        // try again with new token
                                        loadIndexHashlist(check_login, startup, true);
                                    }
                                });
                            }
                        });
                    }
                });
            } catch (error) {
                errorHandling(error, "loadIndexHashlist");
            }
        }

        function getBrowser() {
            var browser = 'unknown';
            try {
                var ua = navigator.userAgent,
                    tem,
                    M = ua.match(/(opera|maxthon|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
                if (/trident/i.test(M[1])) {
                    tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
                    M[1] = 'IE';
                    M[2] = tem[1] || '';
                }
                if (M[1] === 'Chrome') {
                    tem = ua.match(/\bOPR\/(\d+)/);
                    if (tem !== null) {
                        M[1] = 'Opera';
                        M[2] = tem[1];
                    }
                }
                M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
                if ((tem = ua.match(/version\/(\d+)/i)) !== null) M.splice(1, 1, tem[1]);

                browser = M.join(' ');
            } catch (u) {console.error("unable to identify browser", u);}
            return browser;
        }

        // Error Handling / Remote diagnosis / Bug reports
        function errorHandling(e, fn, params = null) {
            try {
                if (verbose && e) {
                    HumanMessage.error("GD-ERROR: " + e.message);
                } else if (!(fn in errorSubmissions) && gd_settings.bug_reports) {
                    errorSubmissions[fn] = true;
                    var data = {
                        error: fn,
                        params: params,
                        "function": fn,
                        browser: getBrowser(),
                        version: gd_version,
                        world: world
                    }
                    if (e && e.stack) {
                        console.log("GD-ERROR stack ", e.stack);
                        data.error = e.stack.replace(/'/g, '"')
                    }

                    $.ajax({
                        type: "POST",
                        url: "https://api.grepodata.com/indexer/v2/scripterror",
                        data: data,
                        success: function (r) {}
                    });
                }
            } catch (error) {
                console.log("Error handling bug report", error);
            }
        }

    }

    function enableCityIndex(globals) {
        if (globals.gdIndex === undefined) {
            globals.gdIndex = 'enabled';

            console.log('GrepoData city indexer V2 is running in primary mode.');
            loadCityIndex(globals);
        } else {
            // Duplicate scripts installed.. stop execution
            console.log('Duplicate indexer script. You only need to have the GrepoData userscript installed once for all worlds you play on.');
        }
    }

    var gd_w = window;
    if(gd_w.location.href.indexOf("grepodata.com") >= 0){
        // Viewer (grepodata.com)
        console.log("initiated grepodata.com viewer");
        grepodataObserver('');

        // Watch for angular app route changes
        function grepodataObserver(path) {
            var initWatcher = setInterval(function () {
                // If route is one of the indexer routes AND path has changed
                if ((
                    gd_w.location.pathname.indexOf("/profile") >= 0 ||
                    gd_w.location.pathname.indexOf("/intel") >= 0 ||
                    gd_w.location.pathname.indexOf("/points") >= 0
                ) && gd_w.location.pathname != path) {

                    // stop looking for route changes and start looking for update message
                    clearInterval(initWatcher);
                    messageObserver();

                } else if (path != '' && gd_w.location.pathname != path) {
                    // there was a route change but not to an indexer route
                    path = '';
                }
            }, 500);
        }

        // Hide install message on grepodata.com/indexer
        function messageObserver() {
            var timeout = 20000;
            var initWatcher = setInterval(function () {
                timeout = timeout - 100;
                if ($('#help_by_contributing').get(0)) {
                    clearInterval(initWatcher); // stop watching for update messages

                    // Hide install banner if script is already running
                    $('#help_by_contributing').get(0).style.display = 'none';

                    // Ingest version
                    if ($('#userscript_version').get(0)) {
                        $('#userscript_version').append('<div id="script_version">' + gd_version + '</div>');
                    }

                    // Start looking for route changes
                    grepodataObserver(gd_w.location.pathname);

                } else if (timeout <= 0) {
                    clearInterval(initWatcher); // stop watching for update messages
                    grepodataObserver(gd_w.location.pathname); // start looking for route changes
                }
            }, 100);
        }
    } else if((gd_w.location.pathname.indexOf("game") >= 0)){
        // Indexer (in-game)
        setTimeout(function () {
            if (gd_w.f0969b2b439fdb38b3adade00a45c40e === undefined) {
                gd_w.f0969b2b439fdb38b3adade00a45c40e = {};
            }
            enableCityIndex(gd_w.f0969b2b439fdb38b3adade00a45c40e);
        }, 300);
    }
} catch(error) { console.error("GrepoData City Indexer crashed (please report a screenshot of this error to admin@grepodata.com): ", error); }
})();

// Userscript DEV

var gd_version = "4.0.3";
var index_key = "s3s4mstr";
var index_hash = "a65538efe44edf5d3a397441a20a90a0";
var verbose = false;

(function() { try {

    // Globals
    var backend_url = 'https://apitest.grepodata.com'
    // var backend_url = 'http://api-grepodata-com.local:8080'
    var time_regex = /([0-5]\d)(:)([0-5]\d)(:)([0-5]\d)(?!.*([0-5]\d)(:)([0-5]\d)(:)([0-5]\d))/gm;
    var gd_icon = "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAXCAYAAAAV1F8QAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAYdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjAuNvyMY98AAAG0SURBVEhLYwACASA2AGIHGmGQ2SA7GGzf7oj4//5g7v/3B7L+vz+U///NVv//r9ZY/3+7K/b/683e/9/tSSTIf7M9DGhGzv8PR4r/v9uX9v/D0TKw+MdTzf9BdoAsSnm13gnEoQn+dLYLRKcAMUPBm62BYMH/f/9QFYPMfL3JE0QXQCzaFkIziz6d60FYBApvdIt07AJQ+ORgkJlfrs2DW1T9ar0jxRZJ7JkDxshiIDPf744B0dUgiwrebA8l2iJsBuISB5l5q58dREOC7u3OKJpZdHmKEsKi1xvdybIIpAamDpdFbze5ISzClrypZdGLZboIiz6d7cRrES4DibHozdYghEWfL0ygmUVvtwcjLPpwuJBmFj1ZpImw6N3uBNpZNE8ByaK9KXgtIheDzHy12gJuUfG7falYLSIHI5sBMvPlCiMQXQy2CFQPoVtEDQwy88VScByBLSqgpUVQH0HjaH8GWJAWGFR7A2mwRSkfjlUAM1bg/9cbXMAVFbhaBib5N9uCwGxQdU2ID662T9aDMag5AKrOQVX9u73JIIvANSyoPl8CxOdphEFmg9sMdGgFMQgAAH4W0yWXhEbUAAAAAElFTkSuQmCC')";
    var gd_icon_intel = "url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAYdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjAuNvyMY98AABDHSURBVGhD3VoJdJRVliYrIUvtldS+JKkklUqqKntS2SsLAZJA2AQRZG1ia0QWZRWX1hDABQ0CBiJrQwOtEQXR7va02tLLnKNCiyLYCIb09Agd287MtDPnMH5z76tUkQ0EwZ7jvHPe+VP/q/rf/e7y3Xvfn2EA/l/MIW/+EOeVP25+BNHQx0SP8CaY1E3Zacbm4kxza2V+fFtFrqUtN03f6k7RNVsNyqaoyOFe/i7/xvfT7z5uGZDg4GCLxaBqnDsxt+Pg2trOP+6uvXx6dyn+/rIHeHsM/vbzLPS8VoP/OTYTZ9pd6Nrnwcc7Sy7vf6ysc+44R4dRI20MDg6y9D7uhsdNAwkPC3VXepJbt6we3Xlm70hc2JWOf9tpxu9blDi8Uomd98qxe2kKNi10oGWGHDsXGfHSUhXee1KL061qnNmkx593O3Cxw4sXH83t9GbrW8PDQty9j7/u8Z2BkEtocp3xLYefGXfp831FeLdFhx33xODBSZGYXh4NrysGaVYJTHExUEojIJfGQC2PRpwiCvF6CZzx0ahwR+HHo2Kwb0kc/mV9HP60WYfu/el484mcS4XO2JagoGGa3u2+dXwnINGREdVrF448fnx7BU62GvDqMinuqolCunUENMooyCQSxCrl0KrlMGlVMOvUNJWw6FX0WQkjTX2cCtpYpfiOWSOBxx6J5jskOP18Ii7uTkDnTgfWzE06HhURWt277TXHjQIJsSdoFv+mfWLPqa0OHF0lxX21BMASCYUsGmqlDFa9GommWCSZY2GP18KRqENqghZpNr2YriQDnHRNTdCJNf5OvCEOGrUCWpUEo3NV2LXYiM5tRnzWZsYbaxw9dlPkYt7bJ8LQ40aAhBdmWtddPDoN57ZZsJ3caFRWFGIV0UL7CUYS3OoTPJ0EzbSbUOBKQIE7AYXuROSmWZGbbkW2w4KsVDM8dC+H7vHfaYl6JFs0pAAGpIRVJ8OCegXefjwOn7XbcHZ3PgrSFOtYBp8og8f1AgktyrSuv3h4IrpeMGLjPAkyEqKglEth0qlgI+2nCQAGIVi+Mx7FWTaU5yajLNs3K/Pt8OaloDQ7CUWZNngyEgWYvF5wPN0pJiRZ4oTrSaKjUO+Jw2sPafHhs3qc35GOArtkPcviE6n/uC4g5E6LLr425Zu/7rPjqVkKJBkoaFVyxBvVSCErOMld2AruFCMySBgWqijDRkInw5ubIkDUFKaJawVNvjLQqoJUFBKgAle8AJ/tMMOdbESqcDc11Ao5StOl+MWjOrz/tB5vNRu/sWnDFvWK1W98K5DoqIjqkwdn9nx1uAZ7VrrhiFcQCJlwpUSjCsm91mAA7DpFJBiD8JBLjfNmoJKEZYEZiA9UKqo9DtSXuwUodrtC+i5/nwHxNSeNrWMUscZgypxS7H8gFqe3pqBjhaEnMnzYIAK4JhCm2M0rqk5cfqsB72ydQG5gIJZRINkaJwBkUBzkOa1CGA/FQzG5THlOMipy7SghjRffPwu2X++E5lfb+k37W7vhpbXRxenC3RhoZYEdIwsdGFPiFC7ppelKNghSUClkuK1cg49e8KD7pQKsb0w+MZCarwnE4zK0/PsRL87uKcTk6mQopNFkdmIhMn0GaawsJ0lsyH7PkzVeTULlLZg6SPirzawfTyQLpQoQowhYDV2rCBSDYQuzta3kZrx3yywdvjzoxleHSpCTLGnpFVOMqwIJCw1xHds6trtruwUP3a4S+SGZ4oEBcDxwkPJmrE2+FsweC8sbQwt7PTPhl+0om9eAMQSGrcoW9bgSiaoNgtF0lHPsFjnebjGh54ANRx9P7Q4NCXL1int1IGXZ5o1nd3tw9EEFMuJHCPNyHmB3YjBMq+Pqi1D82L1DCnYzs6Z5EcbXFwuXzSQWdJB7JRCxyGVSNDVY0U1125cHnChMk27sFXdoIMFBQeaf/aS06/wLNjx8eyxUsiiRkdkK+RSQfM2YUD6kELdy5kz0ilzECmSKZ4JJtmhxrK0OX7+ag/YmdRfFivmqQIwaSWPnvmIcXiFHSbqEmEMm6NCZZCSTJwl2yaRNhtr8Vs78yRWCpv3uzHlKJZdhyW0J+I+Xs8GK1sjDGq8GJHTqyKSODzclYc0dMdCrI0WtlGLVULY2I4dyBNOlCPC6QiQ/u3RIIW5mOp5bjqqGUlQRVTMTcpLl/GIzx0FHNVpOihxnnrfhbwdSUZ87ooNlHgSETKXbtjTnwh/WazG7IhpKWQxlW42gQk50nJG9eXbUEk0ywzDj5KaZBWNxrhhT6hT3OGhrS12o8fiSIeeUqaNyr7k2qTqb8osLY71uYslMQSJM78xenKfsVLNx4RmrlOLnqxLxny9Sgp6jukBAdIOARI0Iq3j76bzLbzykhNcZCUlMjHArG9VBnKQ4RrjEqClKE4mNBRlJM3vOuIBG09ffJ/KBPznaF90RWHPfP0P8xp8c7esWBtY8c8eKRFlX5kJDRQYmjcwWDMZZn4tNruMSKEnKpVI8OM2Ef7zkwLH1CZcjwoMrBgGx6qVN53Zl4chKGTISJeRWviqWsy37qy9TO0ijTlSQZdg6rMGC+eMDAjmfWBgQgpOjY8n0wFrGA3cKa7Ll+HdpTywKrBX+aBzqyFKsGH42A+K8xAzJYLi45GzPcTLFa8BZqo4/2GBEnDysaRCQ9ERF8x+eseM5KgwdFin5pVb4J/spM0hfF6oiN+HMzO7iabwCxL7m3oAQLHTGspmBtexls8TahKosce0LpIAsMqEqU6yNJGtzguT92PVKKCZZmZzpdbEKlGfqcG5XJs5sscISG9I8CEhmsnLjiY2JaL9bBptBRtbQCR9lyi1jDeeRcKQ1FoL9mV2IrZPfxyKpLQsCQkyozET2yjmBtfxVc3D76DyMr8jEWHKj9CcXB9ZKSBnsWjy5fOFns5JGkRtz4q0gpXGBatAokZEUiwvU93fvS4bDFLZxEJCyDHXbuW1WbG6UkVspiMMNvrKc4oK1zxsM9GO+V0ylhl8gx1pfjPgDO2vF7MBa7vLZ/QI77ckrFkmfVtMv/vzxyArhsoWZMj1JTwGvplpPjZNbc6itoKrbGt42CEiJS9l2apOJLCKnBkchLMINEFuDH87aH+jHLHT6jNEBgdgifUnBvfSKa2UtvbOfIlxPLwms5c6u6xd//njkvVmRTBycT8yiB1Lj+GY3zm7RwmUdPhhIfqps4ycEZPcCORINUqI8vfBNfgj3EewyA/24mkDlzL3CWhwjfUnBSQHuX8umeOmriL4xkj2ztn/89cYjkwZPVigHPOe1jBQdPmpz4tPNetiNEYNdK8Mmbe7anoDXH1TCnSClHOKzSH5voHPgDfRjQb+z6wMC2VvuDQjBQrn6WCSTLNJXEanrr9Bvzsy6fvHnj0eOD27SOFad5Fr6OAV9NuNfDxTh07Z4mNRhg4PdohnR9N4ziXjzEaVoaMw6yh/EWqLaJa3wJgxmMsUGMxIHLgs2MI/0JYXslf1jpK8iHH0sMjCP+OORAbN7cTfJqYBb7NuqkvBlRyHebaHPMSGD6TcqIqTirTXWyyeficW8kQoKLF9pwiBYM8IKpOXRJZSdCQiDGE/aHZhH/EKwUDnEVP41O/UqfQM6sbkpsJY/px7jCEBfuuXJpQpbhOmf+yEG8tCcDHx1IAXt98gvh4UGDU6InO43NxkufL5Viw1zlNATZ6cT5XEe8dGvXQDhzVgYFnhKTc6gPNKXFPrGiIsye9+ATll3X2DNM2+suNeXbtkb/O0vxwcfN2lUcux/vAyft1twV0300CUKjdA7KxUd3XtM+N1aLdLjpYg3asi14kXVy2BqyRqcH0TR2LosIMitmrYND6BkTEGAbrni5uOlVCpRdLFyZNl1OHuwHqeeoySdHD500cjDoApr7Nppwxd70zB/jBZ6DbOEUZxy8NEOZ2xuTbmoqylyYGJ11iASGPjZH0sDE6r/84y6AkEAHBsD6TaXlMhFK3eKfEy0dJYHX79egTce0UEaFXzVMp4aq2HmfUs0XV/tT8KhVQaiYaJik0YEGm/AG5XeXgPnOz8dUqO3Yma/uw9548opgxvgJrfmXsSsU4oC9sWWSnyyJQHzR0Z1kbhXb6x4lDgiNn66SUM8rcH0cjk0ajVMVBqwVTjosid9/42Vk9rdNKqt7DxJiQppDJqm5uCj9hzsWUAFrTrk2q0uD27sD61QdX/wlAZ7FymRqI8WR0GJJp+bpY8rHXLzWzkzG8pgov6D21yt2lf3vb+jDue2GnH3qKhu6p2+/fCBR1ZCeMv7T8biNJmxeW4S9QESSkbkZka1OKbhBOU7EkpBKV395TZPZh2OE56cc24jZuM4uHtKOe6ZWo4fTSyhuMnFxKrsQDyNpesYIhI/3Tq5K6Q+iF2K9964rBpfHMjGjiYpYiXB13ccxCNo2DDNgtroE+80a/D+BgumlUkREx0Ds1ZFvqrqbX9Nghr5mkldHFuLgfgSp1sUhxMpiDmQmRTmTijGzLEeTK/LF/eYnpkMRPYmduLTR6ZbbqL4+UnU3sZRR3jXFA86D1Th9+tiUekafoLEu/4DOh4RYUHVLTMkPa+vVuP1h/WozVOI9x98osHnvha9770Hb5phN1LbaxF0yblgVBEnTV+WbqCyvYE0zqX7tDG5IhdxzignaxZlUvtMVy7RzfQsjgduq7n30KhkmDAyA5/sHYWPWjVYWBfVExJ8g0em/qFThCzatUDxzdHVsdj7gBGVmWrKrjIfAKJE/yk8kwB3kXz1Jc8UXwdJNMtVQFnvgR53htwS+A6wfcmOJ5/G+w7DTeIIiGNyam0Ozr44EZf2pWJLo+wbRXTwdzvE7h2hdkPo+peXKfHKSjX2LrNgaqURKoVUNDlsfv+pvO+9SLwQUhxQ9165xGGXYSB8zltEgJkBfa8TjIGenK1hJQXxi5/Fc6rQdWgS/rovBT9bkQy9MvzmXiv0jnCbNnRdB4H5bUssPt5iw7NNqVT7+E7MuXRgIdjFOBtzxcwvd1jT/mTKQjNQth5fnZTkGDy7kJ1+x0EtpYTndlCH+ugEfP3mJHTvT8OOZS7o1NG35EWPf4RoFSGL106X9ny4IRZd2+NxrDUP90xKE6cbDIibHisRAR8S8DkUT355k0IgmYESxWkh3ad1Pgblq5HKcs4R8SYt5k8pxAc/nYLLv/Di/C43Hr7T2iOPGb6Y9/aJMPS4USBihIcGVU8rjTz+8nIF+Pzr1PMp6PhJJpbNcBEFW2AkVpNJYiCLiYJKFkPalFHxKYdWJRXByyfrrPmYqEiolQrkumxYPs+LY9sa0HWwHKc2WfDLx8yoKzIeDwkO+l5ehvYdGqMqpGXVZMmlXz2sxMfEKJ9tS8LJ9jwcecKLR+bnY3K1g/JLApwUvDarHjYLBTK5VXl+MibXuLG6sRSHnibhX52Kfxwpxl+26/G7p2y4f1rapTh5GOeJ7/f1dN9BdZk7QRPWuqBe0fnSco14V/5fr2Th6w4H/n7Ig4uv1OLPh8ajs6MB5w+Owp/2VuLC/jJaK8J/H8nDpb1JOL/ThcOr9ZhTGdNJBWsrZex/3j8MDBy0uUUaGdzoSRnesbBO0rl/ieryb9Zo8MdnjfjseR2+2JOCv+yx4+w2G957So83H43DCwsNl+8areh0WSM6IoeLKvb/7l84hhj8DzJ6qtW80qiQJo08rNmsDmuN1w5vs2qGtxlVoa1qaUhz9IjgJvJ/L3+39zc3NQYB+aHPIW/+8CaG/S+q5WZ9e0LPBwAAAABJRU5ErkJggg==')";
    var gd_icon_svg = '<svg aria-hidden="true" data-prefix="fas" data-icon="university" class="svg-inline--fa fa-university fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: #18bc9c; width: 18px;"><path fill="currentColor" d="M496 128v16a8 8 0 0 1-8 8h-24v12c0 6.627-5.373 12-12 12H60c-6.627 0-12-5.373-12-12v-12H24a8 8 0 0 1-8-8v-16a8 8 0 0 1 4.941-7.392l232-88a7.996 7.996 0 0 1 6.118 0l232 88A8 8 0 0 1 496 128zm-24 304H40c-13.255 0-24 10.745-24 24v16a8 8 0 0 0 8 8h464a8 8 0 0 0 8-8v-16c0-13.255-10.745-24-24-24zM96 192v192H60c-6.627 0-12 5.373-12 12v20h416v-20c0-6.627-5.373-12-12-12h-36V192h-64v192h-64V192h-64v192h-64V192H96z"></path></svg>';

    // Ensure jquery
    if (window.jQuery) {
    } else {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-2.1.4.min.js';
        script.type = 'text/javascript';
        document.getElementsByTagName('head')[0].appendChild(script);
    }

    function parseJwt (token) {
        var base64Url = token.split('.')[1];
        var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    };

    function loadCityIndex(key, globals) {
        // Settings
        var world = Game.world_id;
        var gd_settings = {
            inbox: true,
            forum: true,
            stats: true,
            context: true,
            keys_enabled: true,
            cmdoverview: false,
            departure_time: true,
            bug_reports: true,
            key_inbox_prev: '[',
            key_inbox_next: ']',
        };
        readSettingsCookie();
        setTimeout(function () {
            if (gd_settings.inbox === true || gd_settings.forum === true) {
                loadIndexHashlist(false);
            }
        }, 1000);
        setTimeout(function () {
            if (gd_settings.cmdoverview === true) {
                readIntelHistory();
            }
        }, 500);
        checkLogin();

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
            ABOUT: 'This tool allows you to collect and browse enemy city intelligence from your very own private index that can be shared with your alliance',
            INDEX_LIST: 'You are currently contributing intel to the following indexes',
            INDEX_LOGGED_IN: 'You are currently logged in.',
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
            SHARE: 'Share',
            CMD_OVERVIEW_TITLE: 'Enhanced command overview',
            CMD_DEPARTURE_INFO: 'Add the return and cancel time to your own movements',
            CMD_OVERVIEW_INFO: 'Enhance your command overview with unit intelligence from your enemy city index. Note: this is a new feature, currently still in development. Please contact us if you have feedback.',
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
                        ABOUT: 'Deze tool verzamelt informatie over vijandige steden in een handig overzicht. Rapporten kunnen geindexeerd worden in een unieke index die gedeeld kan worden met alliantiegenoten',
                        INDEX_LIST: 'Je draagt momenteel bij aan de volgende indexen',
                        INDEX_LOGGED_IN: 'Je bent momenteel ingelogd.',
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
                        SHARE: 'Delen',
                        CMD_OVERVIEW_TITLE: 'Uitgebreid beveloverzicht',
                        CMD_DEPARTURE_INFO: 'Voeg de annuleer en terugkeer tijd toe aan eigen bevelen.',
                        CMD_OVERVIEW_INFO: 'Voeg troepen intel uit je city index toe aan het beveloverzicht. Let op: dit is een nieuwe feature, momenteel nog in ontwikkeling. Contacteer ons als je feedback hebt.',
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
                    case "/town_overviews/command_overview":
                        if (gd_settings.cmdoverview === true || gd_settings.departure_time === true) {
                            setTimeout(enhanceCommandOverview, 20);
                        }
                        break;
                    case "/command_info/info":
                        if (gd_settings.cmdoverview === true || gd_settings.departure_time === true) {
                            setTimeout(() => {
                                enhanceCommandInfoPanel(xhr, opt);
                            }, 20);
                        }
                        break;
                }
            } catch (error) {
                errorHandling(error, "handleAjaxCompleteObserver");
            }
        });

        function readSettingsCookie() {
            var settingsJson = localStorage.getItem('globals_s');
            if (settingsJson == null) {
                // try old version
                settingsJson = localStorage.getItem('gd_city_indexer_s');
                localStorage.removeItem('gd_city_indexer_s');
                localStorage.removeItem('gd_city_indexer_i');
                saveSettings();
            } else {
                settingsJson = decodeHashToJson(settingsJson);
            }
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
                    if (!('cmdoverview' in result)) {
                        result.cmdoverview = false;
                    }
                    if (!('departure_time' in result)) {
                        result.departure_time = true;
                    }
                    if (!('bug_reports' in result)) {
                        result.bugreports = true;
                    }
                    gd_settings = result;
                }
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
                    var data = decodeHashToJson(e.currentTarget.activeElement.hash);
                    if (data.id && data.name) {
                        expandContextMenu(data.id, data.name, '');
                    }
                }
            } catch (error) {
                errorHandling(error, "handleContextMenuObserver");
            }
        });
        function expandContextMenu(town_id, town_name, player_name = '') {
            var intelHtml = '<div id="gd_context_intel" class="context_icon" style="z-index: 4; background: ' + gd_icon_intel + ';">'+
                '<div class="icon_caption"><div class="top"></div><div class="middle"></div><div class="bottom"></div><div class="caption">Intel</div></div></div>';
            //var intelHtml = '<div id="gd_context_intel" class="context_icon" style="z-index: 4; background: ' + gd_icon + '; background-repeat: no-repeat; top: 19px; transform: scale(1.5);">'+
            //'<div class="icon_caption" style="transform: scale(.6); left: 41px; top: 15px; width: 60px;"><div class="top"></div><div class="middle"></div><div class="bottom"></div><div class="caption">Intel</div></div></div>';
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

        function getAccessToken() {
            return new Promise(resolve => {
                try {
                    // Get access token from local storage
                    access_token = localStorage.getItem('gd_indexer_access_token');
                    if (!access_token) {
                        resolve(false);
                    }

                    // if timed out, get new access token using refresh token
                    let payload = parseJwt(access_token);
                    if (payload.hasOwnProperty('exp')) {
                        let expiration = payload['exp'];

                        let currentTime = new Date().getTime() / 1000;

                        if (currentTime > expiration - 60) {
                            // Token expired, try to refresh
                            console.log("GrepoData: Access token expired.");
                            refresh_token = localStorage.getItem('gd_indexer_refresh_token');
                            if (!refresh_token) {
                                // New login required
                                localStorage.removeItem('gd_indexer_access_token');
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
                                        localStorage.setItem('gd_indexer_access_token', data.access_token);
                                        localStorage.setItem('gd_indexer_refresh_token', data.refresh_token);
                                        resolve(data.access_token);
                                    } else {
                                        resolve(false);
                                    }
                                },
                                error: function (jqXHR, textStatus) {
                                    console.log("GrepoData: Error renewing access token");
                                    // New login required
                                    localStorage.removeItem('gd_indexer_access_token');
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
                    script_token = localStorage.getItem('gd_indexer_script_token');
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
                                    localStorage.setItem('gd_indexer_script_token', data.script_token);
                                    resolve(data.script_token);
                                } else {
                                    console.log("GrepoData: Error retrieving script token");
                                    localStorage.removeItem('gd_indexer_script_token');
                                    resolve(false);
                                }
                            },
                            error: function (jqXHR, textStatus) {
                                console.log("GrepoData: Error retrieving script token");
                                localStorage.removeItem('gd_indexer_script_token');
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

        var login_window = null;
        var script_token_interval = null;
        var interval_count = 0;
        function showLoginPopup() {
            // This function is called when there is no access_token available

            // First ensure we have a script token
            getScriptToken().then(script_token => {

                if (login_window != null) {
                    login_window.close();
                    login_window = null;
                }
                login_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                    '<a href="#" class="write_message" style="background: ' + gd_icon + '">' +
                    '</a>&nbsp;&nbsp;GrepoData login required',
                    {position: ['center','center'], width: 630, height: 405, minimizable: true});;

                // Window content
                var content = '<div class="gdloginpopup" style="width: 630px; height: 295px;"><div style="text-align: center">' +
                    // '<p style="font-size: 14px;"><strong>GrepoData city indexer:</strong> You need to be logged in to use the city indexer</p>' +
                    '</div></div>';
                login_window.setContent(content);
                var login_window_element = $('.gdloginpopup').parent();
                $(login_window_element).css({ top: 43 });

                // Build login form
                formHtml = `
            <form autocomplete="false" class="gd-login-form" id="gd_login_form" name="gdloginform">
              <div style="text-align: center;font-weight: 800;font-size: 35px;">
                <div style="display: inline-block;"><img src="https://grepodata.com/assets/images/grepodata_icon.ico" style="position: relative; top: 4px;"></div>
                <span style="color: rgb(103, 103, 103)">GREPO</span>
                <span style="color: rgb(24, 188, 156);margin-left: -12px;">DATA</span>
              </div>
              
              <div id="gd-login-container" class="gd-login-container">
                  <h4 class="gd-title" style="text-align: center;">To use the city indexer script, please login with your GrepoData account via this link:</h4>
                  <br/>
                  <h3 class="gd-title" style="text-align: center; place-content: center; font-size: 18px;"><a id="gd_script_auth_link" href="https://grepodata.com/link/` + script_token + `" target="_blank" style="display: contents; color: #444; text-decoration: underline;">grepodata.com/link/` + script_token + `</a></h3>
              
                <div id="grepodatalerror" style="display: none; text-align: center; place-content: center; font-size: 16px;" class="gd-error-msg"><b>Unable to authenticate.</b></div>
                
                  <div class="gd-login-footer" style="margin-top: 50px; height: 60px;">
                    <!--<a class="gd-link-btn" href="https://grepodata.com/forgot" target="_blank">Forgot password?</a>-->
                    <!--<a class="gd-link-btn gd-register-btn" href="https://grepodata.com/register" target="_blank">Create a new account</a>-->
                    <p id="gd-request-new-token-btn" class="gd-link-btn" style="margin-top: 18px;">Request new token</p>
                    <p id="gd-request-token-check" class="gd-login-btn gd-register-btn">Continue</p>
                  </div>
              </div>
              
              <div id="gd-script-linked" class="gd-login-container" style="display: none;">
                  <h4 class="gd-title" style="text-align: center; place-content: center;">
                    You are now logged in. Happy indexing!
                  </h4>
                  <br/>
                  <p style="text-align: center; place-content: center;">Thank you for using GrepoData.</p>
                  <!--<div class="gd-login-footer" style="margin-top: 30px; height: 60px;">-->
                    <!--<p id="gd-script-settings" class="gd-login-btn gd-register-btn">Script settings</p>-->
                  <!--</div>-->
              </div>
                          
            </form>
          
        `;
                $('.gdloginpopup').append(formHtml);

                // Handle actions
                $('#gd-request-new-token-btn').click(function () {
                    // try with new token
                    localStorage.removeItem('gd_indexer_script_token');
                    showLoginPopup();
                    clearInterval(script_token_interval);
                });
                $('#gd-request-token-check').click(function () {
                    checkScriptToken(true);
                });
                // $('#gd-script-settings').click(function () {
                //     gdsettings = true;
                //     Layout.wnd.Create(GPWindowMgr.TYPE_PLAYER_SETTINGS, 'Settings');
                // });
                $('#gd_script_auth_link').click(function () {
                    console.log("GrepoData: script link clicked");

                    clearInterval(script_token_interval);
                    interval_count = 0;
                    script_token_interval = setInterval(checkScriptToken, 3000);

                });

            });
        }

        var playername_window = null;
        function showPlayernamePopup(town_token, player_name) {
            // This function is called when the current playername is unverified by the authenticated user

            if (playername_window != null) {
                playername_window.close();
                playername_window = null;
            }
            playername_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                '<a href="#" class="write_message" style="background: ' + gd_icon + '">' +
                '</a>&nbsp;&nbsp;GrepoData: verify your playername',
                {position: ['center','center'], width: 680, height: 500, minimizable: true});

            // Window content
            var content = '<div class="gdplayernamepopup" style="width: 680px; height: 390px;"><div style="text-align: center"></div></div>';
            playername_window.setContent(content);
            var playername_window_element = $('.gdplayernamepopup').parent();
            $(playername_window_element).css({ top: 43 });

            // Build form
            formHtml = `
        <form autocomplete="false" class="gd-login-form" id="gd_login_form" name="gdloginform">
          <div style="text-align: center;font-weight: 800;font-size: 35px;">
            <div style="display: inline-block;"><img src="https://grepodata.com/assets/images/grepodata_icon.ico" style="position: relative; top: 4px;"></div>
            <span style="color: rgb(103, 103, 103)">GREPO</span>
            <span style="color: rgb(24, 188, 156);margin-left: -12px;">DATA</span>
          </div>
          
          <div id="gd-login-container" class="gd-login-container">
              <h4 class="gd-title" style="text-align: center; place-content: center;">Your player name is not yet verified:&nbsp;&nbsp;<a><img src="https://gpnl.innogamescdn.com/images/game/icons/player.png" alt="" style="">`+player_name+`</a></h4>
              <p style="text-align: center; place-content: center;">If you want to link your Grepolis account to your GrepoData account, you need to verify your player name. To do this, <strong>temporarily</strong> change one of your town names to the following token:</p>
              
              <div style="text-align: center; place-content: center;" class="gd-token-container">
                <label>Town name required:</label>
                <div style="display: inline-flex">
                  <input type="text" class="gd-login-input gd_copy_input_playername" value="`+town_token+`" style="text-align: center;"> 
                  <span class="gd_copy_done_playername" style="display: none; padding: 12px;"> Copied!</span>
                </div>
                <a href="#" class="gd_copy_command_playername">Copy to clipboard</a>
              </div>
              
              <p style="text-align: center; place-content: center;">You will get an email when the verification is complete, you can then change your town name back to whatever you like.</p>
              
              <!--<div style="display: none; text-align: center; place-content: center; font-size: 16px;" class="gd_copy_done_playername"><b>Copied!</b></div>-->
              
              <div id="grepodatalerror" style="display: none; text-align: center; place-content: center; font-size: 16px;" class="gd-error-msg"><b>Unable to verify player name.</b></div>
            
              <div class="gd-login-footer" style="margin-top: 10px; height: 60px;">
                <!--<div style="display: inline-grid; text-align: left;">-->
                    <!--<p id="gd-request-token-check" class="gd-link-btn" style="text-align: left;">I don't want to share my intel</p>-->
                <!--</div>-->
                <p id="gd-playername-cancel" class="gd-link-btn" style="margin-top: 15px;">Cancel</p>
                <p id="gd-playername-continue" class="gd-login-btn gd-register-btn">Continue</p>
              </div>
          </div>
          
          <div id="gd-script-linked" class="gd-login-container" style="display: none;">
              <h4 class="gd-title" style="text-align: center; place-content: center;">
                Your playername is now verified. Happy indexing!
              </h4>
              <br/>
              <p style="text-align: center; place-content: center;">Thank you for using GrepoData.</p>
          </div>
                      
        </form>
      
    `;
            $('.gdplayernamepopup').append(formHtml);

            // Handle actions
            $(".gd-playername-cancel").click(function () {
                playername_window.close();
                playername_window = null;
            });
            $(".gd-playername-continue").click(function () {
                alert("TODO");
            });
            $(".gd_copy_command_playername").click(function () {
                $(".gd_copy_input_playername").select();
                document.execCommand('copy');

                $('.gd_copy_done_playername').get(0).style.display = 'block';
                setTimeout(function () {
                    if ($('.gd_copy_done_playername').get(0)) {
                        $('.gd_copy_done_playername').get(0).style.display = 'none';
                    }
                }, 6000);
            });

        }

        function loginError(message, verbose = false) {
            let errormsg = message==''?"Unable to authenticate. Please try again later":message;
            $('#grepodatalerror').text(errormsg);
            $('#grepodatalerror').show();
            verbose ? HumanMessage.error(errormsg) : null;
        }

        function checkScriptToken(verbose=false) {
            interval_count += 1;
            if (interval_count>100) {
                clearInterval(script_token_interval);
            }
            var script_token = localStorage.getItem('gd_indexer_script_token');
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
                        console.log('GrepoData: Script token verified.');
                        localStorage.setItem('gd_indexer_access_token', data.access_token);
                        localStorage.setItem('gd_indexer_refresh_token', data.refresh_token);
                        localStorage.removeItem('gd_indexer_script_token');
                        HumanMessage.success('GrepoData login succesful!');
                        $('#gd-login-container').hide();
                        $('#gd-script-linked').show();
                        clearInterval(script_token_interval);
                        checkPlayerNameVerificationStatus();
                    } else {
                        // Unable
                        loginError('Unknown error. Please try again later or let us know if this error persists.', verbose);
                    }
                },
                error: function (error, textStatus) {
                    if (error.responseJSON.error_code && (error.responseJSON.error_code === 3041 || error.responseJSON.error_code === 3042)) {
                        // Unknown or expired script token. remove token and try again
                        clearInterval(script_token_interval);
                        localStorage.removeItem('gd_indexer_script_token');
                        showLoginPopup();
                        verbose ? setTimeout(loginError('Expired script token. Please try using the link again.'), 1000) : null;
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

        function checkLogin() {
            // Check if grepodata access token or refresh token is in local storage and use it to verify
            // if not verified: loging required!
            getAccessToken().then(access_token => {
                if (access_token == false) {
                    showLoginPopup()
                } else {
                    console.log("GrepoData: Succesful authentication.");
                    checkPlayerNameVerificationStatus();
                }
            });
        }

        function checkPlayerNameVerificationStatus() {
            return null;
            try {
                let playername = Game.player_name;
                let playerid = Game.player_id;
                let server = Game.world_id.substring(0, 2);
                if (playername && playerid && server) {
                    // Get verification status from API
                    getAccessToken().then(access_token => {
                        if (access_token !== false) {
                            $.ajax({
                                url: backend_url + "/profile/addlinked",
                                data: {
                                    access_token: access_token,
                                    player_name: playername,
                                    player_id: playerid,
                                    server: server,
                                },
                                type: 'post',
                                crossDomain: true,
                                dataType: 'json',
                                success: function (data) {
                                    console.log(data);
                                    if (data.success && 'linked_account' in data) {
                                        if (data.linked_account.confirmed && data.linked_account.confirmed === true) {
                                            console.log('GrepoData: playername is verified.');
                                        } else if (data.linked_account.town_token) {
                                            console.log('GrepoData: playername needs verification.');
                                            let town_token = data.linked_account.town_token;
                                            showPlayernamePopup(town_token, playername);
                                        }
                                    } else {
                                        // Unable
                                    }
                                },
                                error: function (error, textStatus) {
                                    if (error.responseJSON.error_code && error.responseJSON.error_code === 3010) {
                                        // TODO: Email needs to be confirmed
                                    } else {
                                        // Unknown
                                    }
                                },
                                timeout: 30000
                            });
                        }
                    });
                }

                // If unverfied, show change town name popup (auth token generated by API)
            } catch (error) {
                errorHandling(error, "checkPlayerNameVerificationStatus");
            }
        }

        // Add command information to info panel
        function enhanceCommandInfoPanel(xhr, opt) {
            try {
                var window_id = GPWindowMgr.getFocusedWindow().getID();
                var gpwindow = $('#gpwnd_'+window_id);
                if (!window_id) return;

                var cmd_img = $(gpwindow).find('.command_icon_wrapper > img').get(0);
                if (!cmd_img || (
                    cmd_img.src.indexOf('/support.png')<0
                    && cmd_img.src.indexOf('/attack.png')<0
                    && cmd_img.src.indexOf('/attack_sea.png')<0
                )) return;

                var data = JSON.parse(xhr.responseText);
                if (!data.json.command_id) return;
                var command_id = data.json.command_id;

                var movement = false;
                let movements = Object.values(MM.getModels().MovementsUnits);
                movements.map(m => {
                    if (m.attributes.command_id == command_id) {
                        movement = m;
                    }
                });
                if (!movement) return;

                if (verbose) console.log(movement);

                var renderIntel = movement.isIncommingMovement();
                var town_id = movement.attributes.home_town_id;

                // parse source town
                var departureElem = document.getElementById('gd_departure_'+command_id+'_wnd_'+window_id);
                if (!departureElem && gd_settings.departure_time === true) {
                    var departureHtml = '<fieldset class="command_info_units">'+
                        '<legend>'+translate.DEPARTED_FROM+'</legend>';

                    departureHtml = departureHtml + '<div style="display: inline-block; padding: 0 14px;">'+
                        (movement.attributes.link_origin ? '<div style="display: inline-block; margin-top: 12px;"><img alt="" src="/images/game/icons/town.png" style="padding-right: 2px; vertical-align: top;"> ' + movement.attributes.link_origin + '</div>' : '');

                    if (renderIntel) {
                        departureHtml = departureHtml + '<div id="gd_cmd_view_intel_' + window_id + '" town_id="' + town_id + '" class="button_new gdtvcmd' + town_id + '" style="display: inline-block; margin-left: 25px;">' +
                            '<div class="left"></div>' + '<div class="right"></div>' +
                            '<div class="caption js-caption">' + translate.VIEW + '<div class="effect js-effect"></div></div></div>';
                    }

                    departureHtml = departureHtml + '</div></fieldset>';
                    $(gpwindow).find('.command_info_time').after(departureHtml);

                    if (renderIntel) {
                        $('#gd_cmd_view_intel_' + window_id).click(function () {
                            var town_name = movement.attributes.town_name_origin;
                            loadTownIntel(town_id, town_name, '');
                        });
                    }
                }

            } catch (error) {
                errorHandling(error, "enhanceCommandInfoPanel");
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

        function getHumanReadableTimestampDiff(end, start) {
            var diff = start - end;
            var hours = Math.floor(diff / 3600);
            var residual = diff % 3600;
            var minutes = Math.floor(residual / 60);
            var seconds = residual % 60;

            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            if (seconds < 10) {
                seconds = '0' + seconds;
            }
            return hours + ':' + minutes + ':' + seconds;
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

        // Enhance command overview
        var parsedCommands = {};
        var bParsingEnabledTemp = true;
        function enhanceCommandOverview() {
            try {
                // Add temp filter button to footer
                var gd_filter = document.getElementById('gd_cmd_filter');
                if ((!gd_filter) && (gd_settings.cmdoverview === true || gd_settings.departure_time === true)) {
                    var commandFilters = $('#command_filter').get(0);
                    var filterHtml = '<div id="gd_cmd_filter" class="support_filter" style="background-image: '+gd_icon+'; width: 26px; height: 26px; '+(bParsingEnabledTemp?'':'opacity: 0.3;')+'"></div>';
                    $(commandFilters).find('> div').append(filterHtml);

                    $('#gd_cmd_filter').click(function() {
                        bParsingEnabledTemp = !bParsingEnabledTemp;
                        if (!bParsingEnabledTemp) {
                            $('.gd_cmd_units').remove();
                            $('.gd_cmd_runtime').remove();
                            $(this).css({ opacity: 0.3 });
                        } else {
                            $(this).css({ opacity: 1 });
                            enhanceCommandOverview();
                        }
                    });
                }

                // Parse overview
                if (bParsingEnabledTemp && MM.getModels().MovementsUnits) {
                    var commandList = $('#command_overview');
                    var commands = $(commandList).find('li');
                    var parseLimit = 100; // Limit number of parsed commands
                    let movements = Object.values(MM.getModels().MovementsUnits);
                    commands.each(function (c) {
                        if (c>=parseLimit) {return}
                        try {
                            var command_id = this.id;
                            if (!command_id) {return}
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

                    $('.gd_cmd_units').tooltip('Town intel (GrepoData index)');

                    if (verbose) {
                        console.log("parsed commands: ", parsedCommands);
                    }
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
                if (!returnsElem && gd_settings.departure_time === true && cmd.movement_id > 0) {
                    var movement = MM.getModels().MovementsUnits[cmd.movement_id];

                    if (!movement.isIncommingMovement()) {
                        var runtimeHtml = '<span id="gd_runtime_'+id+'" class="troops_arrive_at gd_cmd_runtime gd_runtime_'+id+'" style="font-style: italic;">(';
                        var returnText = '';
                        var cancelText = '';
                        var bHasReturnTime = false;
                        var bHasCancelTime = false;
                        if (movement.attributes.hasOwnProperty('started_at') && movement.getType() != 'support') {
                            bHasReturnTime = true;
                            var returns = getReturnTimeFromMovement(movement);
                            returnText = translate.RUNTIME_RETURNS + ' '+returns.return_readable;
                        }
                        if (movement.attributes.hasOwnProperty('cancelable_until') && movement.attributes.cancelable_until != null) {
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

                // Insert intel
                var cmd_units = document.getElementById('gd_cmd_units_'+id);
                if ((!cmd_units || force) && gd_settings.cmdoverview === true && cmd.is_enemy === true) {
                    if (cmd_units && force) {
                        $('#gd_cmd_units_'+id).remove();
                    }
                    var intel = townIntelHistory[cmd.source_town.id];
                    if (typeof intel !== "undefined") {
                        // show town intel from memory
                        if ('u' in intel && Object.keys(intel.u).length > 0) {
                            var cmdInfoWidth = cmdInfoBox.width();
                            var freeSpace = 770 - cmdInfoWidth - 60; // cmdWidth - cmdTextWidth - margin
                            var numUnits = Object.keys(intel.u).length;
                            var unitSpace = numUnits * 29;
                            var bUnitsFit = freeSpace > unitSpace;
                            if (!bUnitsFit) {
                                $('#command_'+id).height(45);
                            }
                            var unitHtml = '<div id="gd_cmd_units_'+id+'" class="command_overview_units gd_cmd_units" style="'+(bUnitsFit?'bottom: 3px; ':'margin-top: 18px; ')+'cursor: pointer; position: absolute; right: 0;">';
                            for (var i = 0; i < numUnits; i++) {
                                var unit = intel.u[i];
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
                                unitHtml = unitHtml +
                                    '<div class="unit_icon25x25 ' + unit.name + '" style="overflow: unset; font-size: ' + size + 'px; text-shadow: 1px 1px 3px #000; color: #fff; font-weight: 700; border: 1px solid #626262; padding: 10px 0 0 0; line-height: 13px; height: 15px; text-align: right; margin-right: 2px;">' +
                                    unit.count + '</div>';
                            }
                            unitHtml = unitHtml + '</div>';
                            cmdInfoBox.after(unitHtml);
                        } else {
                            var units = '<div id="gd_cmd_units_'+id+'" class="command_overview_units gd_cmd_units" style="margin-top: 14px; cursor: pointer;"><span style="font-size: 10px;">No intel > </span></div>';
                            cmdInfoBox.after(units);
                        }

                    } else {
                        // show a shortcut to view town intel
                        var units = '<div id="gd_cmd_units_'+id+'" class="command_overview_units gd_cmd_units" style="margin-top: 14px;"><a id="gd_cmd_intel_'+id+'" style="font-size: 10px;">Check intel > </a></div>';
                        cmdInfoBox.after(units);
                    }

                    $('#gd_cmd_units_'+id).click(function () {
                        loadTownIntel(cmd.source_town.id, cmd.source_town.name, cmd.source_player.name, id);
                    });

                }

            } catch (error) {
                errorHandling(error, "enhanceCommand");
            }
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
        $('.gods_area').append('<div class="btn_settings circle_button gd_settings_icon" style="right: 0px; top: 95px; z-index: 10;">\n' +
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
        $('.gd_settings_icon').tooltip('GrepoData City Indexer ' + key);

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
        function addToIndexFromForum(reportId, reportElement, reportPoster, reportHash) {
            var reportJson = JSON.parse(mapDOM(reportElement, true));
            var reportText = reportElement.innerText;

            getAccessToken().then(access_token => {
                if (access_token == false) {
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
                        'report_poster': reportPoster,
                        'report_poster_id': gd_w.Game.player_id || 0
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
                        error: function (jqXHR, textStatus) {
                            console.log("error saving forum report");
                        },
                        timeout: 120000
                    });
                    pushForumHash(reportHash);
                    gd_indicator();
                }
            });
        }

        // Add the given inbox report to the index
        function addToIndexFromInbox(reportHash, reportElement) {
            var reportJson = JSON.parse(mapDOM(reportElement, true));
            var reportText = reportElement.innerText;

            getAccessToken().then(access_token => {
                if (access_token == false) {
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
                        'report_poster': gd_w.Game.player_name || 'undefined',
                        'report_poster_id': gd_w.Game.player_id || 0
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
                        error: function (jqXHR, textStatus) {
                            errorHandling(Error(jqXHR.responseText), 'ajaxIndexInboxReport');
                            var btn = document.getElementById("gd_index_rep_txt");
                            var btnC = document.getElementById("gd_index_rep_");
                            btnC.setAttribute('style', 'color: #ea6153; float: right;');
                            btn.innerText = translate.ERROR + ' ✗';
                            btn.setAttribute('title', 'Oops, something went wrong. Developers have been notified (if you enabled bug reports).');
                        },
                        timeout: 120000
                    });
                    pushInboxHash(reportHash);
                    gd_indicator();
                }
            });
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
                        if (globals && globals.reportsFoundInbox) {
                            for (var j = 0; j < globals.reportsFoundInbox.length; j++) {
                                if (globals.reportsFoundInbox[j] === reportHash) {
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
                                addToIndexFromInbox(reportHash, reportElement);
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
                                addToIndexFromInbox(reportHash, reportElement);

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

                        // Handle inbox keyboard shortcuts
                        document.removeEventListener('keyup', inboxNavShortcut);
                        document.addEventListener('keyup', inboxNavShortcut);
                    }

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

            addToIndexFromForum(reportId, reportElement, reportPoster, reportHash);
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
                            if (reportHash !== null && reportHash !== 0 && globals && globals.reportsFoundForum) {
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
                                        '    </ul><br/><input type="text" class="gd_copy_input_' + reportHash + '" value="' + `!gd report ${hashI}` + '"> <a href="#" class="gd_copy_command_' + reportHash + '">Copy to clipboard</a><span class="gd_copy_done_' + reportHash + '" style="display: none; float: right;"> Copied!</span>' +
                                        '    <br /><br /><small>Thank you for using <a href="https://grepodata.com" target="_blank">GrepoData</a>!</small>';

                                    Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG).setContent(content);
                                    addForumReportById($('#gd_index_f_' + reportId).attr('report_id'), $('#gd_index_f_' + reportId).attr('report_hash'));

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

            } catch (error) {
                errorHandling(error, "parseForumReport");
            }
        }

        function settings() {
            if (!$("#gd_indexer").get(0)) {
                $(".settings-menu ul:last").append('<li id="gd_li"><svg aria-hidden="true" data-prefix="fas" data-icon="university" class="svg-inline--fa fa-university fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: #2E4154;width: 16px;width: 15px;vertical-align: middle;margin-top: -2px;"><path fill="currentColor" d="M496 128v16a8 8 0 0 1-8 8h-24v12c0 6.627-5.373 12-12 12H60c-6.627 0-12-5.373-12-12v-12H24a8 8 0 0 1-8-8v-16a8 8 0 0 1 4.941-7.392l232-88a7.996 7.996 0 0 1 6.118 0l232 88A8 8 0 0 1 496 128zm-24 304H40c-13.255 0-24 10.745-24 24v16a8 8 0 0 0 8 8h464a8 8 0 0 0 8-8v-16c0-13.255-10.745-24-24-24zM96 192v192H60c-6.627 0-12 5.373-12 12v20h416v-20c0-6.627-5.373-12-12-12h-36V192h-64v192h-64V192h-64v192h-64V192H96z"></path></svg><a id="gd_indexer" href="#" style="    margin-left: 4px;">GrepoData City Indexer</a></li>');

                // Intro
                // var layoutUrl = 'https' + window.getComputedStyle(document.getElementsByClassName('icon')[0], null).background.split('("https')[1].split('"')[0];
                var settingsHtml = '<div id="gd_settings_container" style="display: none; position: absolute; top: 0; bottom: 0; right: 0; left: 232px; padding: 0px; overflow: auto;">\n' +
                    '    <div id="gd_settings" style="position: relative;">\n' +
                    '\t\t<div class="section">\n' +
                    '\t\t\t<div class="game_header bold" style="margin: -5px -10px 15px -10px; padding-left: 10px;">GrepoData city indexer settings</div>\n' +
                    '\t\t\t<p>' + translate.ABOUT + '.</p>' +
                    '\t\t\t<p id="gdsettingslogged_in">' + translate.INDEX_LOGGED_IN + ' ' + '<a id="gdsettingslogout" href="#">Logout</a>' +
                    '</p>' + (count > 0 ? '<p>' + translate.COUNT_1 + count + translate.COUNT_2 + '.</p>' : '') +
                    '<p id="gd_s_saved" style="display: none; position: absolute; left: 50px; margin: 0;"><strong>' + translate.SAVED + ' ✓</strong></p> ' +
                    '<br/>\n';

                settingsHtml = settingsHtml + '<div style="max-height: '+(count > 0 ? 340 : 360)+'px; overflow-y: scroll; background: #FFEECA; border: 2px solid #d0be97;">';

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

                // Command overview settings
                settingsHtml += '\t\t\t<p style="margin-bottom: 10px; margin-left: 10px;"><strong>' + translate.CMD_OVERVIEW_TITLE + '</strong></p>\n' +
                    '\t\t\t<div style="margin-left: 30px; margin-bottom: 10px;" class="checkbox_new departure_time_gd_enabled' + (gd_settings.departure_time === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.CMD_DEPARTURE_INFO + '</div>\n' +
                    '\t\t\t</div>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new cmdoverview_gd_enabled' + (gd_settings.cmdoverview === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption"><strong>BETA: </strong>' + translate.CMD_OVERVIEW_INFO + '</div>\n' +
                    '\t\t\t</div>\n' +
                    '\t\t\t<br><br><hr>\n';

                // Context menu
                settingsHtml += '\t\t\t<p style="margin-left: 10px; display: inline-flex; height: 14px;"><strong>' + translate.CONTEXT_TITLE + '</strong> <span style="background: '+gd_icon_intel+'; width: 50px; height: 50px; transform: scale(0.6); margin-top: -18px;"></span></p>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new context_gd_enabled' + (gd_settings.context === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.CONTEXT_INFO + '</div>\n' +
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
                    '\t\t\t<br/>';

                // Other
                settingsHtml += '\t\t\t<p style="margin-left: 10px; display: inline-flex; height: 14px;"><strong>'+translate.SETTINGS_OTHER+'</strong></p></br>\n' +
                    '\t\t\t<div style="margin-left: 30px;" class="checkbox_new bug_reports_gd_enabled' + (gd_settings.bug_reports === true ? ' checked' : '') + '">\n' +
                    '\t\t\t\t<div class="cbx_icon"></div><div class="cbx_caption">' + translate.BUG_REPORTS + '</div>\n' +
                    '\t\t\t</div>\n' +
                    '\t\t\t<br><br><hr>\n';

                // Footer
                settingsHtml += '</div>' +
                    '<a href="https://grepodata.com/message" target="_blank">Contact</a>' +
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
                $("#gdsettingslogout").click(function () {
                    $("#gdsettingslogged_in").hide();
                    localStorage.removeItem('gd_indexer_access_token');
                    localStorage.removeItem('gd_indexer_refresh_token');
                    HumanMessage.success('GrepoData logged out succesfully.');
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
                $(".cmdoverview_gd_enabled").click(function () {
                    settingsCbx('cmdoverview', !gd_settings.cmdoverview);
                });
                $(".departure_time_gd_enabled").click(function () {
                    settingsCbx('departure_time', !gd_settings.departure_time);
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
            localStorage.setItem('globals_s', encodeJsonToHash(JSON.stringify(gd_settings)));
        }

        var townIntelHistory = {}

        // Save town intel to local storage
        function saveIntelHistory() {
            try {
                var max_items_in_memory = 100;

                // Convert to list
                var items = Object.keys(townIntelHistory).map(function(key) {
                    return [key, townIntelHistory[key]];
                });

                // Order by time added desc
                items.sort(function(first, second) {
                    return second[1].t - first[1].t;
                });

                // Slice & save
                items = items.slice(0, max_items_in_memory);
                localStorage.setItem('globals_i', JSON.stringify(items));

            } catch (error) {
                errorHandling(error, "saveIntelHistory");
            }
        }

        // Load local town intel history
        function readIntelHistory() {
            try {
                var intelJson = localStorage.getItem('globals_i');
                if (intelJson != null) {
                    result = JSON.parse(intelJson);
                    var items = {}
                    result.forEach(function(e) {items[e[0]] = e[1]})
                    console.log("Loaded town intel from local storage: ", items);
                    townIntelHistory = items;
                }

            } catch (error) {
                errorHandling(error, "readIntelHistory");
            }
        }

        function addToTownHistory(id, units) {
            var stamp = new Date().getTime();
            townIntelHistory[id] = {u: units, t: stamp};
            if (gd_settings.cmdoverview === true) {
                saveIntelHistory();
            }
        }

        var openIntelWindows = {};
        function loadTownIntel(id, town_name, player_name, cmd_id=0) {
            try {

                getAccessToken().then(access_token => {
                    if (access_token == false) {
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
                        var intelUrl = 'https://grepodata.com/indexer/town/'+index_key+'/'+world+'/'+id;
                        var intel_window = Layout.wnd.Create(GPWindowMgr.TYPE_DIALOG,
                            '<a target="_blank" href="'+intelUrl+'" class="write_message" style="background: ' + gd_icon + '"></a>&nbsp;&nbsp;' + translate.TOWN_INTEL + ': ' + town_name + (player_name!=''?(' (' + player_name + ')'):''),
                            {position: ['center','center'], width: 600, height: 590, minimizable: true});
                        // intel_window.setWidth(600);
                        // intel_window.setHeight(590);
                        openIntelWindows[content_id] = intel_window;

                        // Window content
                        var content = '<div class="gdintel_'+content_id+'" style="width: 600px; height: 500px;"><div style="text-align: center">' +
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
                            renderTownIntelWindow(response, id, town_name, player_name, cmd_id, content_id);
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

        function renderTownIntelWindow(data, id, town_name, player_name, cmd_id, content_id) {
            var intelUrl = 'https://grepodata.com/indexer/'+index_key;
            try {
                console.log(data);
                intelUrl = 'https://grepodata.com/indexer/town/'+index_key+'/'+world+'/'+id;
                var unitHeight = 255;
                var notesHeight = 170;

                if (data.intel==null || data.intel.length <= 3) {
                    unitHeight = 150;
                    notesHeight = 275;
                    addToTownHistory(id, []);
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
                    '<a href="https://grepodata.com/indexer/' + index_key + '" class="gd_ext_ref" target="_blank" style="float: right;">Index: ' + index_key + '</a></div>';
                $('.gdintel_'+content_id).append(title);

                // Version check
                if (data.hasOwnProperty('latest_version') && data.latest_version != null && data.latest_version.toString() !== gd_version) {
                    var updateHtml =
                        '<div class="gd_update_available" style=" background: #b93b3b; color: #fff; text-align: center; border-radius: 10px; padding-bottom: 2px;">' +
                        'New userscript version available: ' +
                        '<a href="https://api.grepodata.com/userscript/cityindexer_' + index_hash + '.user.js" class="gd_ext_ref" target="_blank" ' +
                        'style="color: #ffffff; text-decoration: underline;">Update now!</a></div>';
                    $('.gdintel_'+content_id).append(updateHtml);
                    $('.gd_update_available').tooltip((data.hasOwnProperty('update_message') ? data.update_message : data.latest_version));
                    unitHeight -= 18;
                }

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
                            '<div class="unit_icon25x25 ' + unit.name + ' intel-unit-' + unit.name + '-' + id + '-' + j + '" style="overflow: unset; font-size: ' + size + 'px; text-shadow: 1px 1px 3px #000; color: #fff; font-weight: 700; border: 1px solid #626262; padding: 10px 0 0 0; line-height: 13px; height: 15px; text-align: right; margin-right: 2px;">' +
                            unit.count +
                            (unit.killed > 0 ? '   <div class="report_losts" style="position: absolute; margin: 4px 0 0 0; font-size: ' + (size - 1) + 'px; text-shadow: none;">-' + unit.killed + '</div>\n' : '') +
                            '</div>';

                        tooltips.push({id: 'intel-unit-' + unit.name + '-' + id + '-' + j, text: unit.count + ' ' + (unit.name=='unknown'?'unknown land units':unit.name.replace('_',' '))});
                    }
                    if (intel.hero != null) {
                        unitHtml = unitHtml +
                            '<div class="hero_icon_border golden_border intel-hero-' + id + '-' + j + '" style="display: inline-block;">\n' +
                            '    <div class="hero_icon_background">\n' +
                            '        <div class="hero_icon hero25x25 ' + intel.hero.toLowerCase() + '"></div>\n' +
                            '    </div>\n' +
                            '</div>';
                        tooltips.push({id: 'intel-hero-' + id + '-' + j, text: intel.hero.toLowerCase()});
                    }
                    row = row + '<div style="display: table-cell;"><div><div class="origin_town_units" style="padding-left: 30px; margin: 5px 0 5px 0; ' + (killed ? 'height: 37px;' : '') + '">' + unitHtml + '</div></div></div>';

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

                    var rowHeader = '<li class="' + (j % 2 === 0 ? 'odd' : 'even') + '" style="display: inherit; width: 100%; padding: 0 0 ' + (killed ? '0' : '4px') + ' 0;">';
                    table = table + rowHeader + row + '</li>\n';
                }
                addToTownHistory(id, maxCostUnits);

                if (bHasIntel == false) {
                    table = table + '<li class="even" style="display: inherit; width: 100%;"><div style="text-align: center;">' +
                        '<strong>No unit intelligence available</strong><br/>' +
                        'You have not yet indexed any reports about this town.<br/><br/>' +
                        '<span style="font-style: italic;">note: intel about your allies (index contributors) is hidden by default</span></div></li>\n';
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
                    (data.player_id != null && data.player_id != 0 ? '   <a href="https://grepodata.com/indexer/player/' + index_key + '/' + world + '/' + data.player_id + '" target="_blank" style="float: left;"><img alt="" src="/images/game/icons/player.png" style="float: left; padding-right: 2px;">'+translate.INTEL_SHOW_PLAYER+' (' + data.player_name + ')</a>' : '') +
                    (data.alliance_id != null && data.alliance_id != 0 ? '   <a href="https://grepodata.com/indexer/alliance/' + index_key + '/' + world + '/' + data.alliance_id + '" target="_blank" style="float: right;"><img alt="" src="/images/game/icons/ally.png" style="float: left; padding-right: 2px;">'+translate.INTEL_SHOW_ALLIANCE+'</a>' : '') +
                    '</div>';
                $('.gdintel_'+content_id).append(exthtml);
                $('.gd_ext_ref').tooltip('Opens in new tab');

                if (cmd_id != 0) {
                    setTimeout(function(){enhanceCommand(cmd_id, true)}, 10);
                }
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
                            $('#gd_new_note_'+content_id).after('<li class="even gd_note_error_msg" style="display: inherit; width: 100%;">'+
                                '<div style="display: table-cell; padding: 0 7px; color: #ce2508;"><strong>Error saving note.</strong> please try again later or contact us if this error persists.</div>' +
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
                            console.log("Note deleted: ", b);
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
                            var statsBtn = '<a target="_blank" href="https://grepodata.com/player?world=' + gd_w.Game.world_id + '&id=' + player_id + '" class="write_message" style="background: ' + gd_icon + '"></a>';
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
            } catch (error) {
                errorHandling(error, "enhanceTownInfoPanel");
            }
        }

        // Loads a list of report ids that have already been added. This is used to avoid duplicates
        function loadIndexHashlist(extendMode) {
            try {
                $.ajax({
                    method: "get",
                    url: "https://api.grepodata.com/indexer/getlatest?key=" + index_key + "&player_id=" + Game.player_id + "&filter=" + JSON.stringify(gd_settings)
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
            } catch (error) {
                errorHandling(error, "loadIndexHashlist");
            }
        }

        function getActiveIndexes(toString = true) {
            indexes = [index_key];
            if (globals.gdIndexScript.length > 1) {
                indexes = globals.gdIndexScript;
            }
            if (toString == true) {
                indexes = JSON.stringify(indexes);
            }
            return indexes;
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
        var errorSubmissions = [];
        function errorHandling(e, fn) {
            console.log("GD-ERROR stack ", e.stack);
            if (verbose) {
                HumanMessage.error("GD-ERROR: " + e.message);
            } else if (!(fn in errorSubmissions) && gd_settings.bug_reports) {
                errorSubmissions[fn] = true;
                try {
                    $.ajax({
                        type: "POST",
                        url: "https://api.grepodata.com/indexer/scripterror",
                        data: {error: e.stack.replace(/'/g, '"'), "function": fn, browser: getBrowser(), version: gd_version, world: world, index: index_key},
                        success: function (r) {}
                    });
                } catch (error) {
                    console.log("Error handling bug report", error);
                }
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
                        $('#userscript_version').append('<div id="script_version">' + gd_version + '</div>');
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
        if (gd_w.f0969b2b439fdb38b3adade00a45c40e === undefined) gd_w.f0969b2b439fdb38b3adade00a45c40e = {};
        enableCityIndex(index_key, gd_w.f0969b2b439fdb38b3adade00a45c40e);
    }
} catch(error) { console.error("GrepoData City Indexer crashed (please report a screenshot of this error to admin@grepodata.com): ", error); }
})();


// webhook_url = "https://discord.com/api/webhooks/a/b"
//
// function sendMessage() {
//     var request = new XMLHttpRequest()
//     request.open("POST", webhook_url)
//     request.setRequestHeader('Content-type', 'application/json')
//     var params = {
//         embeds: [{"color": parseInt("#ffffff".replace("#",""), 16), "title": "title text", "description": "description text"}]
//     }
//     request.send(JSON.stringify(params))
// }
<?php

require_once __DIR__ . '/config.php';

use Map\Infra\Html;

$search = $app->searchQuery($_GET);
$hasRequest = $search->hasRequest();
$requestHeading = '';
if ($hasRequest && $search->hasPhrase()) {
    $requestHeading = 'Запрос: <b>' . Html::e($search->phrase) . '</b>';
} elseif ($hasRequest) {
    $requestHeading = 'Адрес';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="ru">
    <head>
        <title>Карта.</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-1.9.1.js"></script>
        <script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>

        <link rel="stylesheet" type="text/css" href="/css/style_wind.css">
        <link rel="stylesheet" type="text/css" href="/css/show_img.css">
        <link rel="stylesheet" type="text/css" href="/css/style.css">

        <script src="/js/hash.js" type="text/javascript"></script>
        <script src="/js/script.js" type="text/javascript"></script>
        <link href="/css/jquery.autocomplete.css" rel="stylesheet"/>
        <link href="/css/styles.css" rel="stylesheet"/>
        <link href="/css/thickbox.css" rel="stylesheet"/>

        <!--Отловить размер окна меню-->
        <script type="text/javascript">
            function ResizeMenu()
            {
                if ($('#show_menu').outerWidth() > 1250) {
                    $('#show_menu_1').show(100);
                    $('#show_menu_2').hide(100);
                } else  {
                    $('#show_menu_1').hide(100);
                    $('#show_menu_2').show(100);
                }
                var w = Math.round($('.reklama').width()/2-60);
                $("#banner1").width(w);
                $("#banner2").width(w);
                $("#banner3").width(w);
                $("#banner4").width(w);
            }
            $(window).resize(function() {
                ResizeMenu();
            });
            $(window).ready(function() {
                ResizeMenu();
            });
        </script>
        <!--Отловить размер окна меню-->
    </head>
    <body>
<div id="content">
    <table>
        <tr>
            <td class="left_td">
                <div id="error"></div>
                <?php if ($requestHeading !== '') { ?>
                <div id="requestText"><?=$requestHeading?></div>
                <?php } ?>
                <div id="addrText" >Адреса</div>
                <div id="addrs" >
                    <img src="/images/indicator.gif" id="addrLoader" alt="" style="display:none">
                </div>

                <div id="orgsText">Недвижимость</div>
                <div id="realties" >
                    <img src="/images/indicator.gif" id="realtyLoader" alt="" style="display:none">
                </div>

                <div id="orgsText">Организации</div>
                <div id="orgs">
                    <img src="/images/indicator.gif" id="orgLoader" alt="" style="display:none">
                </div>

                <div id="stopsText">Остановки</div>
                <div id="stops">
                    <img src="/images/indicator.gif" id="stopLoader" alt="" style="display:none">
                </div>

                <script type="text/javascript">
                $(function () {
                    var q = window.location.search.replace(/^\?/, '');
                    if (!q) {
                        return;
                    }
                    var i, hash = hashParseString();
                    var po = 0, pr = 0, pa = 0, ps = 0;
                    for (i in hash) {
                        if (hash.hasOwnProperty(i)) {
                            if (i.substring(0,2) == 'po') {
                                po = hash[i];
                            }
                            if (i.substring(0,2) == 'pr') {
                                pr = hash[i];
                            }
                            if (i.substring(0,2) == 'pa') {
                                pa = hash[i];
                            }
                            if (i.substring(0,2) == 'ps') {
                                ps = hash[i];
                            }
                        }
                    }
                    getResultOrg(q, po, 0);
                    getResultRealty(q, pr, 0);
                    getResultAddress(q, pa, 0);
                    getResultStops(q, ps, 0);
                });
                </script>
            </td>
            <td class="right_td">
                <div id="searchDiv">
                    <form autocomplete="off" id='searchForm'>
                        <input type="text" value="Поиск" onfocus="changeValue()" id="searchLine" name="search" class="text_inp_ser map"
                               >
                        <input type="hidden" name="changeCount" value="0" id="changeVar">
                    </form>
                </div>
                <div id="search_l" style="width: 100%;height: 1px;"></div>
                <iframe id="mapFrame" src="about:blank"></iframe>
                <script type="application/javascript">
                    $(function () {
                        var w = $('#search_l').width() - 40;
                        var params = new URLSearchParams(window.location.search);
                        params.set('w', String(w));
                        $('#mapFrame').attr('src', 'map_frame.php?' + params.toString());
                    });
                </script>

            </td>
        </tr>
    </table>
</div>
    <script type="application/javascript">
        var w = $('#content').width();
        $('.top_block').width(w);
    </script>
</body>
</html>

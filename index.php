<?php

session_start();
require_once "config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="ru">
    <head>
        <title>Карта.</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" rel="stylesheet" />
        <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
        <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>

        <link rel="stylesheet" type="text/css" href="/css/style_wind.css">
        <link rel="stylesheet" type="text/css" href="/css/show_img.css">
        <link rel="stylesheet" type="text/css" href="/css/style.css">

        <script src="/js/scripts.js" type="text/javascript"></script>
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
        <?php

        require_once 'functions.php';


        ?>
    </head>
    <body>
    <?php
    ?>
<div id="content">
    <table>
        <tr>
            <td class="left_td">
                <div id="error"></div>
                <?php
                if ($street || $district || $house || $massive) {
                    $requestText = 'Адрес: <b>'.$streetName.', '.$houseName.'</b>';

                ?>
                <div id="requestText"><?=$requestText?></div>
                <div id="addrText" >Адреса</div>
                <div id="addrs" >
                    <img src="/images/indicator.gif" id=addrLoader>
                </div>

                <div id="orgsText">Недвижимость</div>
                <div id="realties" >
                    <img src="/images/indicator.gif" id=realtyLoader>
                </div>

                <div id="orgsText">Организации</div>
                <div id="orgs">
                    <img src="/images/indicator.gif" id=orgLoader>
                </div>

                <div id="stopsText" style="border: 0px solid blue; width:300px;float:left;">Остановки</div>
                <div id="stops">
                    <img src="/images/indicator.gif" id=stopLoader>
                </div>

                <script type="text/javascript">
                $(
                    function () {
                        var i, hash = hashParseString();
                        var po,pr,pa,ps = 0;
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
                        getResultOrg(location.search.slice(1),po,0);
                        getResultRealty(location.search.slice(1),pr,0);
                        getResultAddress(location.search.slice(1),pa,0);
                        getResultStops(location.search.slice(1),ps,0);
                    }
                );
                </script>
                <?php
                } else {
                ?>
                    <div id="addrText" >Адреса</div>
                    <div id="addrs"></div>

                    <div id="orgsText">Недвижимость</div>
                    <div id="realties"></div>

                    <div id="orgsText">Организации</div>
                    <div id="orgs"></div>

                    <div id="stopsText">Остановки</div>
                    <div id="stops"></div>

                <?php
                }
            /*  TODO:
              V 1. в редакторе зданий добавить X и Y
              V 2. убрать координаты, оставить только для админа (сделать админскую базу, с возможностью установки координат 4 полями (улица, район, массив, номер дома))
              V 3. в правое поле - вывести адреса (районы, массивы) + количество орг-й в каждом
              V 4. в балуун адрес (в скобках количество предложений, орг-й по данному адресу)
                4. при поиске по району рисовать ломаной район
              V 5. при поиске в строке искать и по адресам (районам, массивам)
              V 6. добавить поле описание в оргах, переделать индекс-крон (добавление слов из описания в поисковый индекс)
                7. добавить в map_frame поиск по адресам и объектам недвижимости (разным цветом)
                8. остановки
                9. улицы
                10. районы (области)
                11. возможно населенные пункты
            */
                ?>
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
                <script type="application/javascript">
                    var w = $('#search_l').width()-40;
                    //alert(w);
                    var q = '<?=$_SERVER['QUERY_STRING']?>';
                    //alert (q);
                    document.write('<iframe id="mapFrame"  src="map_frame.php?'+q+'&w='+w+'"></iframe>');
                </script>

            </td>
        </tr>
    </table>
</div>
    <script type="application/javascript">
        var w = $('#content').width();
        $('.top_block').width(w);
    </script>

    <?php
    ?>
</body>
</html>

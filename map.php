<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 14.09.13
 * Time: 13:45
 * To change this template use File | Settings | File Templates.
 */
require_once "config.php";
?>
<!DOCTYPE html>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Проверка координат.</title>
    <script src="http://api-maps.yandex.ru/2.0/?load=package.standard&lang=ru-RU" type="text/javascript"></script>
    <script src="/js/jquery-1.10.2.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        var myMap;
        // Дождёмся загрузки API и готовности DOM.
        ymaps.ready(init);
        function init () {
            // Создание экземпляра карты и его привязка к контейнеру с
            // заданным id ("map").
            myMap = new ymaps.Map('map', {
                // При инициализации карты обязательно нужно указать
                // её центр и коэффициент масштабирования.
                center:[56.496581,84.963502], // Tomsk
                zoom:16,
		behaviors: ['default', 'scrollZoom']
            }, {
                balloonMaxWidth: 200
            });
            myMap.controls
                .add('zoomControl', { left: 5, top: 5 })
                .add('typeSelector')
                .add('mapTools', { left: 35, top: 5 });
        <?php
/*
        $limit = $page*$perPage;
            $sql = "SELECT
                            h.k_shn_id,
                            h.centerX,
                            h.centerY,
                            s.k_s_name street,
                            h.k_shn_house_num house

                    FROM k_towns t
                    LEFT JOIN k_streets s ON s.k_s_town=t.k_t_id
                    LEFT JOIN k_streets_house_nums h ON h.k_shn_street_id = s.k_s_id
                    WHERE yandex_status=1
                    AND h.k_shn_id = 8931
                    ORDER BY RAND()
		    LIMIT 0, 50
		    ";
            $res = $mysqli->query($sql);
            $res->data_seek(0);
            while ($row = $res->fetch_assoc()) {
                ?>
                var place<?=$row['k_shn_id']?> = new ymaps.Placemark([<?=$row['centerX']?>, <?=$row['centerY']?>], {
                    hintContent: '<?=$row['street'].', д.'.$row['house']?>',
                    balloonContent: '<?=$row['street'].', д.'.$row['house']?>'
                });
                myMap.geoObjects.add(place<?=$row['k_shn_id']?>);
                <?php
            }
*/
        ?>
            /*
            var coords = [
            ]
            var myCollection = new ymaps.GeoObjectCollection();
            for (var i = 0; i<coords.length; i++) {
                myCollection.add(new ymaps.Placemark(coords[i]));
            }
            var aa, bb =0;
            myMap.geoObjects.add(myCollection);
            */
            var objCount  = 0;
            myMap.events.add('click', function (e) {
                myMap.balloon.close();
                    //if (!myMap.balloon.isOpen()) {
                        var coords = e.get('coordPosition');
                        /*
                        */
                        ymaps.geocode(coords).then(function (res) {
                            var names = [];
                            var res_arr = {};
                            var arr = {};
                            var rrr = {};
                            var kind, name;
                            res.geoObjects.each(function (obj) {
                                arr = obj.properties.get('metaDataProperty');
                                kind = arr['GeocoderMetaData'].kind;
                                name = obj.properties.get('name');
                                res_arr[kind] = name;
                                names.push(obj.properties.get('name'));
                            });
                            $.ajax({
                                //url: 'ajax_request.php?&c='+encodeURIComponent(JSON.stringify(res_arr)),             // указываем URL и
                                url: 'ajax_request.php?&a='+coords[0].toPrecision(6)+','+coords[1].toPrecision(6),             // указываем URL и
                                dataType : "json",                     // тип загружаемых данных
                                success: function (data) { // вешаем свой обработчик на функцию success
                                    objCount = data.count;
                                }
                            });
                            $('#res').text(objCount);
                            newCount = objCount + 1 - 2 + 1;


/*
                                myMap.geoObjects.add(new ymaps.Placemark(coords, {
                                    // В качестве контента иконки выведем
                                    // первый найденный объект.
                                    iconContent:names[0]+':'+newCount,
                                    // А в качестве контента балуна - подробности:
                                    // имена всех остальных найденных объектов.
                                    balloonContent:names.reverse().join(', ')
                                }, {
                                    preset:'twirl#redStretchyIcon',
                                    balloonMaxWidth:'250'
                                }));
*/

                                myMap.balloon.open(coords, {
                                    contentHeader:'',
                                    contentBody: '<p class=address>'+names[0]+'</p><p class=result>Найдено '+newCount+' совпадений</p>',
                                    contentFooter:[coords[0].toPrecision(8),coords[1].toPrecision(8)].join(', ')
                                });


                            });

                    //}
                    //else {
                    //    myMap.balloon.close();
                    //}
                });
            myMap.events.add('contextmenu', function (e) {
                    myMap.hint.show(e.get('coordPosition'), 'Данное событие недоступно');
                });

        }
    </script>
</head>
<body style="padding: 0px; margin: 0px;">
    <div id="map" style="width:800px; height:475px;float:left;"></div>
    <div id="res" style="border: 1px solid red; width:100px;float:left;">Тест
    <br>
    <ul></ul>
    </div>

</body>

</html>
<?php
/*
 * TODO на первый этап
 * 1. попробывать получать данные из базы с координат
 * 2. давать ссылкой разультаты поиска (по координатам или по адресу)
 * 3. выводить результаты поиска списокм и отмечать из на карте кружками
 * 4. при тыкании на объект выводить объекты недвижимости и организации
 * 5. ajax-поиск (по адресам, типам объектов (аренда, продажа и т.д.))
 *
 *
 * геокодинг улиц (районов)
 * http://api.yandex.ru/maps/doc/jsapi/2.x/dg/concepts/geocoding.xml
 *
 *
 */
?>

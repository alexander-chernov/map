<?php

namespace Map\Map;

use Map\Domain\MapState;

final class YandexMapView
{
    public function render(MapState $state, MapViewOptions $options): string
    {
        $json = json_encode(
            $state->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
        );
        $jquery = $options->admin
            ? 'https://code.jquery.com/jquery-1.9.1.js'
            : 'https://code.jquery.com/jquery-1.10.2.js';
        $extraHead = $options->lightbox
            ? '<link rel="stylesheet" href="/css/lightbox.css" media="screen"/>'
            . '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Karla:400,700">'
            . '<script src="/js/modernizr.custom.js"></script>'
            . '<script src="/js/lightbox-2.6.min.js"></script>'
            : '<link href="/css/thickbox.css" rel="stylesheet"/>';
        $behaviors = $state->scrollZoom ? "['default', 'scrollZoom']" : "['default']";
        $mapsApi = 'https://api-maps.yandex.ru/2.0/?load=package.full,package.geoObjects,package.editor&lang=ru-RU';
        $apiKey = defined('YANDEX_API_KEY') ? (string) YANDEX_API_KEY : '';
        if ($apiKey !== '' && strcasecmp($apiKey, 'API_KEY') !== 0) {
            $mapsApi .= '&apikey=' . rawurlencode($apiKey);
        }

        $html = <<<'HTML'
<!DOCTYPE HTML>
<html id="html">
<head>
<title>IP70</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link href="/css/jquery.autocomplete.css" rel="stylesheet"/>
<link href="/css/styles.css" rel="stylesheet"/>
__EXTRA_HEAD__
<script src="__MAPS_API__" type="text/javascript"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="__JQUERY__"></script>
<script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script src="/js/hash.js" type="text/javascript"></script>
<script src="/js/frame_script.js" type="text/javascript"></script>
<script type="text/javascript">
var MAP_STATE = __MAP_STATE__;
ymaps.ready(init);
function init() {
    var state = MAP_STATE;
    var myMap = new ymaps.Map("map", {
        center: state.center,
        propagateEvents: true,
        zoom: state.zoom,
        behaviors: __BEHAVIORS__
    });
    myMap.setType(state.mapType);
    myMap.controls
        .add('zoomControl', { left: 5, top: 5 })
        .add('typeSelector')
        .add('mapTools', { left: 35, top: 5 })
        .add('scaleLine', { right: 10, bottom: 55 })
        .add(new ymaps.control.MiniMap({type: 'yandex#map'}, {zoomOffset: 3}))
        .add(new ymaps.control.TrafficControl({providerKey: 'traffic#actual'}));

    (state.banners || []).forEach(function (banner) {
        var balloon = new ymaps.Balloon(myMap);
        balloon.options.setParent(myMap.options);
        balloon.open([banner.lat, banner.lon]);
        balloon.setData({content: banner.html});
    });

    myMap.events.add('click', function (e) {
        myMap.balloon.close();
        var coords = e.get('coordPosition');
        ymaps.geocode(coords).then(function (res) {
            var names = [];
            var resArr = {};
            res.geoObjects.each(function (obj) {
                var meta = obj.properties.get('metaDataProperty');
                resArr[meta.GeocoderMetaData.kind] = obj.properties.get('name');
                names.push(obj.properties.get('name'));
            });
            $.ajax({
                url: 'ajax_request.php?t=2&c=' + encodeURIComponent(JSON.stringify(resArr)) + '&a=' + coords[0].toPrecision(6) + ',' + coords[1].toPrecision(6),
                timeout: 3000,
                dataType: "json",
                success: function (data) {
                    openClickBalloon(myMap, coords, names, data, state.width, resArr);
                },
                error: function () {
                    $('#error').append('<p class=address>Ошибка 1</p>');
                }
            });
        });
    });

    if (state.contextMenuHint) {
        myMap.events.add('contextmenu', function (e) {
            myMap.hint.show(e.get('coordPosition'), 'Данное событие недоступно');
        });
    }

    (state.polylines || []).forEach(function (line) {
        (line.paths || []).forEach(function (path) {
            myMap.geoObjects.add(new ymaps.Polyline(path, {
                hintContent: line.name,
                balloonContent: line.name
            }, {
                strokeColor: line.color,
                strokeWidth: 6,
                opacity: 0.9,
                draggable: false
            }));
        });
    });

    var clusters = {
        address: 'twirl#invertedGreenClusterIcons',
        firm: 'twirl#invertedVioletClusterIcons',
        search: 'twirl#invertedBrownClusterIcons',
        street: 'twirl#invertedBlueClusterIcons',
        stop: 'twirl#invertedRedClusterIcons'
    };
    Object.keys(clusters).forEach(function (group) {
        var items = [];
        (state.pins || []).forEach(function (pin) {
            if (pin.group !== group) {
                return;
            }
            items.push(new ymaps.Placemark([pin.lat, pin.lon], {
                balloonContentHeader: pin.header,
                balloonContent: '',
                balloonContentBody: pin.body
            }, { preset: pin.preset }));
        });
        var cluster = new ymaps.Clusterer({
            clusterDisableClickZoom: true,
            preset: clusters[group]
        });
        cluster.add(items);
        myMap.geoObjects.add(cluster);
    });

    if (state.point) {
        var marker = new ymaps.GeoObject({
            geometry: { type: "Point", coordinates: state.point }
        }, { preset: 'twirl#greenStretchyIcon' });
        var pointCluster = new ymaps.Clusterer({clusterDisableClickZoom: true});
        pointCluster.add(marker);
        myMap.geoObjects.add(pointCluster);
        ymaps.geocode(state.point).then(function (res) {
            var names = [];
            var resArr = {};
            res.geoObjects.each(function (obj) {
                var meta = obj.properties.get('metaDataProperty');
                resArr[meta.GeocoderMetaData.kind] = obj.properties.get('name');
                names.push(obj.properties.get('name'));
            });
            $.ajax({
                url: 'ajax_request.php?t=2&c=' + encodeURIComponent(JSON.stringify(resArr)) + '&a=' + state.point[0] + ',' + state.point[1],
                timeout: 3000,
                dataType: "json",
                success: function (data) {
                    var a = state.point[0] + ',' + state.point[1];
                    var textBody = '<p class=address>Адрес: <b>' + (names[2] || '') + '</b>';
                    textBody += '<br><b>' + (names[0] || '') + ' </b>';
                    if (data.district) {
                        textBody += '<br>Район: <b>' + data.district + ' <a href="#" onclick="showRightAddressByLink(\'' + data.link + '\',0)">' + data.district_count + '</a></b>';
                    }
                    if (data.massive) {
                        textBody += '<br>Микрорайон: <b>' + data.massive + ' <a href="#" onclick="showRightAddressByLink(\'' + data.link + '\',0)">' + data.massive_count + '</a></b>';
                    }
                    textBody += '</p><p class=result>';
                    textBody += 'Предложений: <b><a href="#" onclick="showRightRealtyByLink(\'a=' + a + '\'+data.link,0)">' + data.count + '</a></b><br>';
                    textBody += 'Организаций: <b><a href="#" onclick="showRightOrgsByLink(\'a=' + a + '\'+data.link,0)">' + data.count_f + '</a></b></p>';
                    marker.properties.set({
                        clusterCaption: names[0],
                        balloonContent: (names[2] || '') + ', ' + (names[0] || ''),
                        balloonContentBody: textBody
                    });
                }
            });
        });
    }
}
function openClickBalloon(myMap, coords, names, data, width, resArr) {
    var w = width ? '&w=' + width : '';
    var streetPayload = encodeURIComponent(JSON.stringify(resArr || {})) + w;
    var textBody = '<p class=address>Адрес: <b>' + (names[2] || '') + '</b>';
    if (!data.house) {
        textBody += '<br><b>' + (names[0] || '') + '</b> <a onclick="showStreetByLink(\'' + streetPayload + '\',0)" href="#">Посмотреть улицу на карте</a>';
    } else {
        textBody += '<br><b>' + (names[0] || '') + ' </b>';
    }
    if (data.district) {
        textBody += '<br>Район: <b>' + data.district + ' <a href="#" onclick="showRightAddressByLink(\'' + data.link + w + '\',0)">' + data.district_count + '</a></b>';
    }
    if (data.massive) {
        textBody += '<br>Микрорайон: <b>' + data.massive + ' <a href="#" onclick="showRightAddressByLink(\'' + data.link + w + '\',0)">' + data.massive_count + '</a></b>';
    }
    textBody += '</p>';
    var link = 'a=' + coords[0].toPrecision(6) + ',' + coords[1].toPrecision(6) + data.link;
    textBody += '<p class=result>';
    if (!data.house) {
        textBody += 'Адресов: <b><a href="#" onclick="showRightAddressByLink(\'' + link + w + '\',0)">' + data.street_count + '</a></b><br>';
    }
    textBody += 'Предложений: <b><a href="#" onclick="showRightRealtyByLink(\'' + link + w + '\',0)">' + data.count + '</a></b><br>';
    textBody += 'Организаций: <b><a href="#" onclick="showRightOrgsByLink(\'' + link + w + '\',0)">' + data.count_f + '</a></b>';
    if (data.count > 0 || data.count_f > 0) {
        textBody += '<br><a id="closeBalloon" href="#">Посмотреть</a>';
    }
    if (data.foto) {
        textBody += '<br>' + data.foto;
    }
    textBody += '</p>';
    myMap.balloon.open(coords, { contentHeader: '', contentBody: textBody });
    $("#closeBalloon").click(function () {
        showRightMapByLink(data.link + '&a=' + coords[0].toPrecision(6) + ',' + coords[1].toPrecision(6) + w, 0);
        myMap.balloon.close();
    });
}
</script>
</head>
<body style="padding: 0px; margin: 0px;" id="body">
    <div id="error"></div>
    <div id="map" style="clear:left; width:__WIDTH__px; height:475px;float:left;"></div>
</body>
</html>
HTML;

        return str_replace(
            ['__EXTRA_HEAD__', '__JQUERY__', '__MAPS_API__', '__MAP_STATE__', '__BEHAVIORS__', '__WIDTH__'],
            [$extraHead, $jquery, $mapsApi, $json, $behaviors, (string) $state->width],
            $html
        );
    }
}

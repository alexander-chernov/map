/**
 * Created with JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 20.11.13
 * Time: 2:31
 * To change this template use File | Settings | File Templates.
 */
function hashParseString() {
    var i,
        h = window.location.hash,
        data = {},
        row;
    if (h) {
        h = h.substring(1).split('&');
    }

    for (i = 0; i < h.length; ++i) {
        row = h[i].split('=');
        data[row[0]] = row[1];
    }

    return data;
}
function hashBuildString(data) {
    var k, s = '', i = 0;
    for (k in data) {
        if (data.hasOwnProperty(k)) {
            if (i++) {
                s += '&';
            }
            s += k;
            if ('undefined' != typeof data[k]) {
                s += '=' + data[k];
            }
        }
    }
    window.location.hash = '#' + s;
}
function showStreetByLink(link){
    $('#mapFrame',top.document).attr('src', "map_frame.php?street=on&c="+link);
}
function showRightMapByLink(link,show){
    showRightAddressByLink(link,show);
    showRightRealtyByLink(link,show);
    showRightOrgsByLink(link,show);
    $('#mapFrame',top.document).attr('src', "map_frame.php?"+link);
}

function showRightOrgsByLink(link,show) {
    //alert (show);
    $('#orgLoader',top.document).show().animate(500);
    $('#orgs',top.document).load('ajax_result_org.php?' + link + '&page=0&show='+show,function(){
        $('#orgLoader',top.document).hide().animate(500);
    });
    return false;
}
function showRightRealtyByLink(link,show) {
    $('#realtyLoader',top.document).show().animate(500);
    $('#realties',top.document).load('ajax_result_realty.php?' + link + '&page=0&show='+show,function(){
        $('#realtyLoader',top.document).hide().animate(500);
    });
    return false;
}
function showRightAddressByLink(link,show) {
    $('#addrLoader',top.document).show().animate(500);
    $('#addrs',top.document).load('ajax_result_address.php?' + link + '&page=0&show='+show,function(){
        $('#addrLoader',top.document).hide().animate(500);
    });
    return false;
}
function reLocationMap(link) {
    var w = $('#search_l',top.document).width();
    $('#mapFrame',top.document).attr('src', "map_frame.php?w="+w+"&"+link);
}
function showRoute(route) {
    var w = $('#search_l',top.document).width();
    $('#mapFrame',top.document).attr('src', "map_frame.php?w="+w+"&street=on&rt="+route);
}


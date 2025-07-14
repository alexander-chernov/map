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
function getResultOrg(requestStr, page, show) {
    var hash = hashParseString();
    hash['po'] = page;
    hashBuildString(hash);
    $('#orgLoader').show().animate(500);
    $('#orgs').load('ajax_result_org.php?' + requestStr + '&page=' + page + '&show='+show,function(){
        $('#addrLoader').hide().animate(500);
    });
}
function getResultRealty(requestStr, page, show) {
    var hash = hashParseString();
    hash['pr'] = page;
    hashBuildString(hash);
    $('#realtyLoader').show().animate(500);
    $('#realties').load('ajax_result_realty.php?' + requestStr + '&page=' + page + '&show='+show,function(){
        $('#addrLoader').hide().animate(500);
    });

}
function getResultAddress(requestStr, page, show) {
    var hash = hashParseString();
    hash['pa'] = page;
    hashBuildString(hash);
    $('#addrLoader').show().animate(500);
    $('#addrs').load('ajax_result_address.php?' + requestStr + '&page=' + page + '&show='+show,function() {
        $('#addrLoader').hide().animate(500);
    });
}
function getResultStops(requestStr, page, show) {
    var hash = hashParseString();
    hash['ps'] = page;
    hashBuildString(hash);
    $('#stopLoader').show().animate(500);
    $('#stops').load('ajax_result_stop.php?' + requestStr + '&page=' + page + '&show='+show,function() {
        $('#stopLoader').hide().animate(500);
    });
}

function changeValue(){
    ch = $('#changeVar').val();
    if (ch==0) {
        $('#searchLine').val('');
        $('#changeVar').val('1');
    }
}
function showObject(coordX,coordY,query){
    //$('#error').append(coordX+','+coordY+'&'+query);
    var w = $('#search_l').width();
    $('#mapFrame').attr('src', "map_frame.php?noadv=1&w="+w+"&a="+coordX+','+coordY+'&'+query);
}

function showRoute(route) {
    var w = $('#search_l').width();
    $('#mapFrame').attr('src', "map_frame.php?w="+w+"&street=on&rt="+route);
}

$(function () {
    function split(val) {
        //return val.split(/\s*/);
        return val.split(' ');
    }

    function extractLast(term) {
        return split(term).pop();
    }
    function formRequest() {
        var requestStr = 'f='+encodeURIComponent(JSON.stringify($('#searchLine').val()));
        //var requestStr = 'f='+$('#searchLine').val();
        getResultOrg(requestStr, 0, 0);
        getResultAddress(requestStr, 0, 0);
        getResultRealty(requestStr, 0, 0);
        getResultStops(requestStr, 0, 0);
        var w = $('#search_l').width();
        $('#mapFrame',top.document).attr('src', "map_frame.php?w="+w+"&"+requestStr);
        return false;
    }
    function YandexPlaceCheck(){

    }

    $('#searchForm').submit (function() {
        return formRequest();
    });
    $("#searchLine").autocomplete({
        source: "ajax_search.php",
        minLength: 1,
        focus: function (event, ui) {
            $("#searchLine").val(ui.item.value);
            return false;
        },
        select: function (event, ui) {
            var w = $('#search_l').width();
            $("#searchLine").val(ui.item.value);
            var requestStr = 'f='+encodeURIComponent(JSON.stringify($('#searchLine').val()));
            getResultOrg(requestStr, 0, 0);
            getResultAddress(requestStr, 0, 0);
            getResultRealty(requestStr, 0, 0);
            getResultStops(requestStr, 0, 0);
            $('#mapFrame',top.document).attr('src', "map_frame.php?w="+w+"&"+requestStr);
            return false;
        }
    });
});

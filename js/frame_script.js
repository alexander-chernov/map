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


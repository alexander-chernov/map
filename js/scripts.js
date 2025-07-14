
function ImmovablePages(obj) {

    if ($(obj).attr('class') === 'left_arrow') {
        page = parseInt($(obj).attr('alt'), 10) - 1;
    }
    if ($(obj).attr('class') === 'right_arrow') {
        page = parseInt($(obj).attr('alt'), 10) + 1;
    }
    var div = $('.rubrika_nedvig').width();
    $('#vip_nedvig').fadeOut(250, function() {
        $('#vip_nedvig').html('<div class="realty_animate"><img style="position: absolute; top: 0%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30"></div>');
        $('.rubrika_nedvig').width(div);
    }).fadeIn(250);
    $.post("inc/ajax.php", {
        ImmovablePages: page
    }, function(data) {
        if ($(obj).attr('class') === 'left_arrow') {
            $('#realty .right_arrow').attr('alt', data.page_h);
            $('#realty .left_arrow').attr('alt', data.page_h);
        }
        if ($(obj).attr('class') === 'right_arrow') {
            $('#realty .right_arrow').attr('alt', data.page_h);
            $('#realty .left_arrow').attr('alt', data.page_h);
        }
        $('#vip_nedvig').fadeOut(250, function() {
            $('#vip_nedvig').html(data.block_h);
        }).fadeIn(500);
    }, "json");
}
$(function() {
    $('body').mouseover(function() {
        if ($('.photo_map_vsplivaet').css('display') === 'block') {
            $('.photo_map_vsplivaet').hide();
        }
    });
});
function ShowPhoto(obj) {
    if ($(obj).attr('src') === 'images/photo_1.png') {
        $(obj).attr('src', 'images/photo_2.png');
        $.ajax({
            type: "POST",
            url: "inc/ajax.php",
            data: "ShowPhoto=" + $(obj).attr('alt'),
            success: function(data) {
                if ($('.photo_map_vsplivaet img').css('display') !== 'block') {
                    $('.photo_map_vsplivaet img').attr('src', data);
                    $('.photo_map_vsplivaet').show();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                alert("\u0412озникла ошибка!");
            }
        });
    }
    $(document).mousemove(function(e) {
        var x = e.pageX;
        var y = e.pageY;
        $('.photo_map_vsplivaet').css('left', x + 2);
        $('.photo_map_vsplivaet').css('top', y + 2);
    });
}
function HidePhoto(obj) {
    $(obj).attr('src', 'images/photo_1.png');
}
function PhotoPages(obj) {
    if ($(obj).attr('class') === 'right_arrow') {
        page = parseInt($(obj).attr('alt'), 10) + 1;
    }
    if ($(obj).attr('class') === 'left_arrow') {
        page = parseInt($(obj).attr('alt'), 10) - 1;
    }
    var div_width = $('.rubrika_foto').width();
    $('#vip_photo_obiavl').fadeOut(250, function() {
        $('#vip_photo_obiavl').html('<div style="width:100%"><img style="position: absolute; top: 0%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30"></div>');
        $('.rubrika_foto').width(div_width);
    }).fadeIn(250);
    $.post("inc/ajax.php", {
        PhotoPages: page
    }, function(data) {
        if ($(obj).attr('class') === 'right_arrow') {
            $('#PhotoBlock .right_arrow').attr('alt', data.page_h);
            $('#PhotoBlock .left_arrow').attr('alt', data.page_h);
        }
        if ($(obj).attr('class') === 'left_arrow') {
            $('#PhotoBlock .right_arrow').attr('alt', data.page_h);
            $('#PhotoBlock .left_arrow').attr('alt', data.page_h);
        }
        $('#vip_photo_obiavl').fadeOut(250, function() {
            $('#vip_photo_obiavl').html(data.block_h);
        }).fadeIn(500);
        html = '';
        for (i = 1; i <= $('#photo_circles > div').length; i++) {
            if (i === data.page_h) {
                html += '<div class="krug_active"></div>';
            } else {
                html += '<div class="krug"></div>';
            }
        }
        $('#photo_circles').fadeOut(250, function() {
            $('#photo_circles').html(html);
        }).fadeIn(250);
    }, "json");

}
function ExpertPages(obj) {
    if ($(obj).attr('class') === 'top_button_exp') {
        page = parseInt($(obj).attr('alt'), 10) - 1;
    }
    if ($(obj).attr('class') === 'bottom_button_exp') {
        page = parseInt($(obj).attr('alt'), 10) + 1;
    }
    $('#vip_expert').fadeOut(250, function() {
        $('#vip_expert').html('<img style="position: absolute; top: 45%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30">');
    }).fadeIn(250);
    $.post("inc/ajax.php", {
        ExpertPages: page
    }, function(data) {
        if ($(obj).attr('class') === 'top_button_exp') {
            $('.top_button_exp').attr('alt', data.page_h);
            $('.bottom_button_exp').attr('alt', data.page_h);
        }
        if ($(obj).attr('class') === 'bottom_button_exp') {
            $('.top_button_exp').attr('alt', data.page_h);
            $('.bottom_button_exp').attr('alt', data.page_h);
        }
        $('#vip_expert').fadeOut(250, function() {
            $('#vip_expert').html(data.block_h);
        }).fadeIn(500);
    }, "json");

}
function BlogPages(obj) {
    if ($(obj).attr('class') === 'left_arrow') {
        page = parseInt($(obj).attr('alt'), 10) - 1;
    }
    if ($(obj).attr('class') === 'right_arrow') {
        page = parseInt($(obj).attr('alt'), 10) + 1;
    }
    $('#vip_blog').fadeOut(250, function() {
        $('#vip_blog').html('<img style="position: absolute; top: 20%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30">');
    }).fadeIn(250);
    $.post("inc/ajax.php", {
        BlogPages: page
    }, function(data) {
        if ($(obj).attr('class') === 'right_arrow') {
            $('#BlogPage .right_arrow').attr('alt', data.page_h);
            $('#BlogPage .left_arrow').attr('alt', data.page_h);
        }
        if ($(obj).attr('class') === 'left_arrow') {
            $('#BlogPage .right_arrow').attr('alt', data.page_h);
            $('#BlogPage .left_arrow').attr('alt', data.page_h);
        }
        $('#vip_blog').fadeOut(250, function() {
            $('#vip_blog').html(data.block_h);
        }).fadeIn(500);
    }, "json");

}
function JobPages(obj) {
    if ($(obj).attr('class') === 'right_arrow') {
        page = parseInt($(obj).attr('alt'), 10) + 1;
    }
    if ($(obj).attr('class') === 'left_arrow') {
        page = parseInt($(obj).attr('alt'), 10) - 1;
    }
    if ($('#job_need').attr('class') === 'menu_rab_active') {
        type = 2;
    }
    if ($('#job_seek').attr('class') === 'menu_rab_active') {
        type = 1;
    }
    $('#job_circles').fadeOut(250, function() {
        $('#vip_rabota').html('<img style="position: absolute; top: 20%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30">');
    }).fadeIn(250);
    $.post("inc/ajax.php", {
        JobPages: page,
        JobPagesType: type
    }, function(data) {
        if ($(obj).attr('class') === 'right_arrow') {
            $('#JobBlock .right_arrow').attr('alt', data.page_h);
            $('#JobBlock .left_arrow').attr('alt', data.page_h);
        }
        if ($(obj).attr('class') === 'left_arrow') {
            $('#JobBlock .right_arrow').attr('alt', data.page_h);
            $('#JobBlock .left_arrow').attr('alt', data.page_h);
        }
        $('#vip_rabota').fadeOut(250, function() {
            $('#vip_rabota').html(data.block_h);
        }).fadeIn(500);
        html = '';
        for (i = 1; i <= $('#job_circles > div').length; i++) {
            if (i === data.page_h) {
                html += '<div class="krug_active"></div>';
            } else {
                html += '<div class="krug"></div>';
            }
        }
        $('#job_circles').fadeOut(250, function() {
            $('#job_circles').html(html);
        }).fadeIn(250);
    }, "json");

}

function JobSwitch(obj) {
    //alert($(obj).attr('class'));

    //if ($(obj).attr('class') === 'shapka_bloka') {
        //$('#JobBlock .shapka_bloka').attr('class', 'menu_rab');
        //$(obj).attr('class', 'menu_rab_active');
        if ($(obj).attr('id') === 'job_need') {
            type = 2;
            $('#job_seek').attr('class', 'menu_rab');
            $('#job_need').attr('class', 'menu_rab_active');
        }
        if ($(obj).attr('id') === 'job_seek') {
            type = 1;
            $('#job_seek').attr('class', 'menu_rab_active');
            $('#job_need').attr('class', 'menu_rab');
        }
        //alert($(obj).attr('id'));
        $('#vip_rabota').fadeOut(250, function() {
            $('#vip_rabota').html('<img style="position: absolute; top: 45%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30">');
        }).fadeIn(250);
        $.post("inc/ajax.php", {
            JobPages: 1,
            JobPagesType: type
        }, function(data) {
            $('#JobBlock .right_button').attr('alt', '1');
            $('#JobBlock .left_button').attr('alt', '1');
            $('#vip_rabota').fadeOut(250, function() {
                $('#vip_rabota').html(data.block_h);
            }).fadeIn(500);
            html = '';
            for (i = 1; i <= $('#job_circles > div').length; i++) {
                if (i === data.page_h) {
                    html += '<div class="krug_active"></div>';
                } else {
                    html += '<div class="krug"></div>';
                }
            }
            $('#job_circles').fadeOut(250, function() {
                $('#job_circles').html(html);
            }).fadeIn(250);
        }, "json");
    //}
    /*
    if ($(obj).attr('class') === 'menu_rab') {
        $('#JobBlock .menu_rab_active').attr('class', 'menu_rab');
        $(obj).attr('class', 'menu_rab_active');
        if ($(obj).attr('id') === 'job_need') {
            type = 1;
        }
        if ($(obj).attr('id') === 'job_seek') {
            type = 2;
        }
        $('#vip_rabota').fadeOut(250, function() {
            $('#vip_rabota').html('<img style="position: absolute; top: 45%; left: 42%;" src="images/ajax-loader.gif" alt="">');
        }).fadeIn(250);
        $.post("inc/ajax.php", {
            JobPages: 1,
            JobPagesType: type
        }, function(data) {
            $('#JobBlock .right_button').attr('alt', '1');
            $('#JobBlock .left_button').attr('alt', '1');
            $('#vip_rabota').fadeOut(250, function() {
                $('#vip_rabota').html(data.block_h);
            }).fadeIn(500);
            html = '';
            for (i = 1; i <= $('#job_circles > div').length; i++) {
                if (i === data.page_h) {
                    html += '<div class="krug_active"></div>';
                } else {
                    html += '<div class="krug"></div>';
                }
            }
            $('#job_circles').fadeOut(250, function() {
                $('#job_circles').html(html);
            }).fadeIn(250);
        }, "json");
    }
    */
}

function CatalogSwitch(obj) {
    if ($(obj).attr('class') === 'menu_rab') {
        $('#CatalogBlock .menu_rab_active').attr('class', 'menu_rab');
        $(obj).attr('class', 'menu_rab_active');
        if ($(obj).attr('id') === 'catalog_c') {
            type = 1;
        }
        if ($(obj).attr('id') === 'catalog_s') {
            type = 2;
        }
        $('#vip_catalog').fadeOut(250, function() {
            $('#vip_catalog').html('<div class="catalog_animate"><img style="position: absolute; top: 45%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30"></div>');
        }).fadeIn(250);
        $.post("inc/ajax.php", {
            CatalogPages: 1,
            catalogPagesType: type
        }, function(data) {
            $('#CatalogBlock .right_button').attr('alt', '1');
            $('#CatalogBlock .left_button').attr('alt', '1');
            $('#vip_catalog').fadeOut(250, function() {
                $('#vip_catalog').html(data.block_h);
            }).fadeIn(500);
            html = '';
            for (i = 1; i <= $('#catalog_circles > div').length; i++) {
                if (i === data.page_h) {
                    html += '<div class="krug_active"></div>';
                } else {
                    html += '<div class="krug"></div>';
                }
            }
            $('#catalog_circles').fadeOut(250, function() {
                $('#catalog_circles').html(html);
            }).fadeIn(250);
        }, "json");
    }
}

function CatalogPages(obj) {
    if ($(obj).attr('class') === 'right_button') {
        page = parseInt($(obj).attr('alt'), 10) + 1;
    }
    if ($(obj).attr('class') === 'left_button') {
        page = parseInt($(obj).attr('alt'), 10) - 1;
    }
    if ($('#catalog_c').attr('class') === 'menu_rab_active') {
        type = 1;
    }
    if ($('#catalog_s').attr('class') === 'menu_rab_active') {
        type = 2;
    }
    $('#vip_catalog').fadeOut(250, function() {
        $('#vip_catalog').html('<div class="catalog_animate"><img style="position: absolute; top: 45%; left: 42%;" src="images/ajax-loader.gif" alt="" width="30"></div>');
    }).fadeIn(250);
    $.post("inc/ajax.php", {
        CatalogPages: page,
        catalogPagesType: type
    }, function(data) {
        if ($(obj).attr('class') === 'right_button') {
            $('#CatalogBlock .right_button').attr('alt', data.page_h);
            $('#CatalogBlock .left_button').attr('alt', data.page_h);
        }
        if ($(obj).attr('class') === 'left_button') {
            $('#CatalogBlock .right_button').attr('alt', data.page_h);
            $('#CatalogBlock .left_button').attr('alt', data.page_h);
        }
        $('#vip_catalog').fadeOut(250, function() {
            $('#vip_catalog').html(data.block_h);
        }).fadeIn(500);
        html = '';
        for (i = 1; i <= $('#catalog_circles > div').length; i++) {
            if (i === data.page_h) {
                html += '<div class="krug_active"></div>';
            } else {
                html += '<div class="krug"></div>';
            }
        }
        $('#catalog_circles').fadeOut(250, function() {
            $('#catalog_circles').html(html);
        }).fadeIn(250);
    }, "json");

}
function ShowLogin() {
    var br  = 0 ;
    var leftz = 1000;
    //alert($('#show_menu').outerWidth());
    if ($('#show_menu').outerWidth() < 1300) {
        br = $('#signin').position();
        leftz = parseInt(br.left - 300);
    }
    //alert(leftz);
    $('#vhod_block_gl').css("margin-left",leftz+"px");
    $('.vhod_block_gl').toggle(500);
}
function hashParseString() {
    var i,
        h = window.location.hash,
        data = {},
        row;
    if (h) {
        h = h.substring(1).split('&');
    } else {
        h = [];
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
            if ('undefined' !== typeof data[k]) {
                s += '=' + data[k];
            }
        }
    }
    window.location.hash = '#' + s;
}

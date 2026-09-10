//移植自xiuno
var le = {};
le.options = {}; // 全局配置
le.ceil = Math.ceil;
le.round = Math.round;
le.floor = Math.floor;
// 针对国内的山寨套壳浏览器检测不准确
le.is_ie = (!!document.all) ? true : false;// ie6789
le.is_ie_10 = navigator.userAgent.indexOf('Trident') != -1;
le.is_ff = navigator.userAgent.indexOf('Firefox') != -1;

// 标准的 urlencode()
le._urlencode = function(s) {
    s = encodeURIComponent(s);
    s = xn.strtolower(s);
    return s;
};

// 标准的 urldecode()
le._urldecode = function(s) {
    s = decodeURIComponent(s);
    return s;
};

//前台搜索，建议对搜索词使用该函数进行编码，服务端使用 xn_urldecode 函数解码
le.urlencode = function(s) {
    s = encodeURIComponent(s);
    s = s.replace(/_/g, "%5f");
    s = s.replace(/\-/g, "%2d");
    s = s.replace(/\./g, "%2e");
    s = s.replace(/\~/g, "%7e");
    s = s.replace(/\!/g, "%21");
    s = s.replace(/\*/g, "%2a");
    s = s.replace(/\(/g, "%28");
    s = s.replace(/\)/g, "%29");
    s = s.replace(/\%/g, "_");
    return s;
};

le.urldecode = function(s) {
    s = s.replace(/_/g, "%");
    s = decodeURIComponent(s);
    return s;
};

le.nl2br = function(s) {
    s = s.replace(/\r\n/g, "\n");
    s = s.replace(/\n/g, "<br>");
    s = s.replace(/\t/g, "&nbsp; &nbsp; &nbsp; &nbsp; ");
    return s;
};

le.time = function() {
    return le.intval(Date.now() / 1000);
};

le.intval = function(s) {
    var i = parseInt(s);
    return isNaN(i) ? 0 : i;
};

le.floatval = function(s) {
    if(!s) return 0;
    if(s.constructor === Array) {
        for(var i=0; i<s.length; i++) {
            s[i] = le.floatval(s[i]);
        }
        return s;
    }
    var r = parseFloat(s);
    return isNaN(r) ? 0 : r;
};

le.isset = function(k) {
    var t = typeof k;
    return t != 'undefined' && t != 'unknown';
};

le.empty = function(s) {
    if(s == '0') return true;
    if(!s) {
        return true;
    } else {
        if(s.constructor === Object) {
            return Object.keys(s).length == 0;
        } else if(s.constructor === Array) {
            return s.length == 0;
        }
        return false;
    }
};

le.f2y = function(i, callback) {
    if(!callback) callback = round;
    var r = i / 100;
    return callback(r);
};
le.y2f = function(s) {
    var r = le.round(le.intval(s) * 100);
    return r;
};

le.strtolower = function(s) {
    s += '';
    return s.toLowerCase();
};

le.strtoupper = function(s) {
    s += '';
    return s.toUpperCase();
};

le.rand = function(n) {
    var str = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
    var r = '';
    for (i = 0; i < n; i++) {
        r += str.charAt(Math.floor(Math.random() * str.length));
    }
    return r;
};

le.random = function(min, max) {
    var num = Math.random()*(max-min + 1) + min;
    var r = Math.ceil(num);
    return r;
};

le.is_mobile = function(s) {
    var r = /^\d{11}$/;
    if(!s) {
        return false;
    } else if(!r.test(s)) {
        return false;
    }
    return true;
};

le.is_email = function(s) {
    var r = /^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/i
    if(!s) {
        return false;
    } else if(!r.test(s)) {
        return false;
    }
    return true;
};

le.is_string = function(obj) {return Object.prototype.toString.apply(obj) == '[object String]';};
le.is_function = function(obj) {return Object.prototype.toString.apply(obj) == '[object Function]';};
le.is_array = function(obj) {return Object.prototype.toString.apply(obj) == '[object Array]';};
le.is_number = function(obj) {return Object.prototype.toString.apply(obj) == '[object Number]' || /^\d+$/.test(obj);};
le.is_regexp = function(obj) {return Object.prototype.toString.apply(obj) == '[object RegExp]';};
le.is_object = function(obj) {return Object.prototype.toString.apply(obj) == '[object Object]';};
le.is_element = function(obj) {return !!(obj && obj.nodeType === 1);};
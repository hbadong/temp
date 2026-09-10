function toJson(data) {
    if(typeof data === "object"){
        return data;
    }else{
        var json = {};
        try{
            json = eval("("+data+")");
        }catch(e){
            $("body").replaceWith(data);
        }
        return json;
    }
}

function time() {
    return (new Date).getTime();
}

function P(str) {
    return parent.$(str);
}

function I(str) {
    return document.getElementById(str);
}

//加载CSS
function loadCss(file) {
    // 不重复加载
    var tags = document.getElementsByTagName('link');
    for(var j=0; j<tags.length; j++) {
        if(tags[j].href.indexOf(file) != -1) {
            return false;
        }
    }

    var link = document.createElement("link");
    link.rel = "stylesheet";
    link.type = "text/css";
    link.href = file;
    document.getElementsByTagName('head')[0].appendChild(link);
}

//加载JS
function loadJs() {
    var args = arguments;

    //循环加载JS
    var load = function(i) {
        if(typeof args[i] == 'string') {
            var file = args[i];

            // 不重复加载
            var tags = document.getElementsByTagName('script');
            for(var j=0; j<tags.length; j++) {
                if(tags[j].src.indexOf(file) != -1) {
                    if(i < args.length) load(i+1);
                    return;
                }
            }

            var script = document.createElement("script");
            script.type = "text/javascript";
            script.src = file;

            // callback next
            if(i < args.length) {
                // Attach handlers for all browsers
                script.onload = script.onreadystatechange = function() {
                    if(!script.readyState || /loaded|complete/.test(script.readyState)) {
                        // Handle memory leak in IE
                        script.onload = script.onreadystatechange = null;

                        // Remove the script (取消移除，判断重复加载时需要读 script 标签)
                        //if(script.parentNode) { script.parentNode.removeChild(script); }

                        // Dereference the script
                        script = null;

                        load(i+1);
                    }
                };
            }
            document.getElementsByTagName('head')[0].appendChild(script);
        }else if(typeof args[i] == 'function') {
            args[i]();
            if(i < args.length) {
                load(i+1);
            }
        }
    }

    load(0);
}
window.isIE6 = window.VBArray && !window.XMLHttpRequest;
if(window.admin_lang == "en"){
    window.langs = ["Tips", "Confirm", "Cancel"];
}else{
    window.langs = ["提示", "确认", "取消"];
}
//先引入layer
window.adminAjax = {
    //提示框
    alert : function(data) {
        if(typeof data === "object"){
            var json = data;
        }else{
            var json = toJson(data);
        }
        window.adminData = json;
        var icon = 1;
        if( json.err == 1 ){
            icon = 5;
        }
        layer.msg(json.msg, {icon: icon});
        if(json.err==0) setTimeout(function(){ window.location.reload(); }, 1000);
    },
    //询问confirm
    confirm : function(msg, funcy, funcn, title){
        layer.confirm(msg, {
            btn: [window.langs[1], window.langs[2]] //按钮
            , title: !title ? window.langs[0] : title,
        }, function(indexy){
            !funcy ? layer.close(indexy) : funcy();
        }, function(indexn){
            !funcn ? layer.close(indexn) : funcn();
        });
    },
    //提交表单
    submit : function(selector, callback) {
        $(selector).submit(function(){
            //取消绑定submit事件，要不然err=1时，不刷新页面，下次提交会执行 2 3 ... N次
            $(selector).unbind("submit");

            adminAjax.postd($(this).attr("action"), $(this).serialize(), callback);
            return false;
        });
    },
    //提交数据(加强版，具有加载和提示框功能)
    postd : function(url, param, callback) {
        var loadingindex = layer.load();
        adminAjax.post(url, param, (!callback ? adminAjax.alert : callback));
        layer.close(loadingindex);
    },
    //提交数据
    post : function(url, param, callback) {
        $.ajax({
            type	: "POST",
            cache	: false,
            url		: url,
            data	: param,
            dataType: 'json',
            success	: callback,
            error	: function(html){
                layer.closeAll('loading');
                alert("提交数据失败，代码:"+ html.status +"，请稍候再试");
            }
        });
    },
    //提交form数据
    postform : function(formid, callback) {
        $.ajax({
            type	: "POST",
            cache	: false,
            url		: $(formid).attr("action"),
            data	: $(formid).serialize(),
            dataType: 'json',
            success	: (!callback ? adminAjax.alert : callback),
            error	: function(html){
                layer.closeAll('loading');
                alert("提交数据失败，代码:"+ html.status +"，请稍候再试");
            }
        });
        return false;
    },
    //获取数据
    get : function(url, callback) {
        $.ajax({
            type	: "GET",
            cache	: true,
            url		: url,
            success	: callback,
            error	: function(html){
                alert("获取数据失败，代码:"+ html.status +"，请稍候再试");
            }
        });
    },
    //layer open url
    open : function(title, url, width, height, callback){
        var w = !width ? '100%' : width;
        var h = !height ? '100%' : height;
        layer.open({
            type: 2,
            title: title,
            shadeClose: true,
            shade: 0.8,
            area: [w, h],
            content: url,
            cancel: function(){
                //location.reload()
            },
            end:function(){
                !callback ? location.reload() : callback;
            }
        });
    }
}
$.cookie = function(name, value, options) {
    if(typeof value != 'undefined') {
        options = options || {};
        if(value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if(options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if(typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            }else{
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        var path = options.path ? '; path=' + options.path : '';
        var domain = options.domain ? '; domain=' + options.domain : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    }else{
        var cookieValue = null;
        if(document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for(var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                if(cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
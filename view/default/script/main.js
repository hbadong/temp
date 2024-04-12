function intval(i) {
	i = parseInt(i);
	return isNaN(i) ? 0 : i;
}

function getBrowser() {
	var browser = {
			msie: false, firefox: false, opera: false, safari: false,
			chrome: false, netscape: false, appname: '未知', version: ''
		},
		userAgent = window.navigator.userAgent.toLowerCase();
	if (/(msie|firefox|opera|chrome|netscape)\D+(\d[\d.]*)/.test(userAgent)){
		browser[RegExp.$1] = true;
		browser.appname = RegExp.$1;
		browser.version = RegExp.$2;
	}else if(/version\D+(\d[\d.]*).*safari/.test(userAgent)){
		browser.safari = true;
		browser.appname = 'safari';
		browser.version = RegExp.$2;
	}
	return browser;
}

//jQuery的cookie扩展
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


// 悬浮导航
function topNavScroll(){
	//获取当前窗口滚动条顶部所在的像素值 并取整
	var topScroll = Math.floor($(window).scrollTop());
	//定义滚动条只要大于0 背景透明度就变成1 并且增加转换时间为1s
	if(topScroll>0){
		$('.th_header').css('opacity',0.9);
		$('.th_header').css('transition','1s');
	}
	else{
		$('.th_header').css('opacity',1);
	}
}

$(document).ready(function(){

	//搜索
	$("#search_form,#search_form2").submit(function(){
		var keyword = $(this).find("input[name='keyword']").val();
		if ($("input[name='mid']").length > 0) {
			var mid = $(this).find("input[name='mid']").val();
		}else{
			var mid = 2;
		}

		if(keyword == ''){
			alert('请输入搜索关键词');
			return false;
		}

		if( _LE.parseurl == 1 ){
			if(mid > 2){
				window.location.href = _LE.uri+"search/mid_"+mid+"/"+encodeURIComponent(keyword)+"/";
			}else{
				window.location.href = _LE.uri+"search/"+encodeURIComponent(keyword)+"/";
			}
		}else{
			if(mid > 2){
				window.location.href = _LE.uri+"index.php?search--mid-"+mid+"-keyword-"+encodeURIComponent(keyword);
			}else{
				window.location.href = _LE.uri+"index.php?search--keyword-"+encodeURIComponent(keyword);
			}
		}
		return false;
	});

	// 评论提交
	window.ctf_form_one = false;
	$("#ctf_form").submit(function(){
		if(window.ctf_form_one) return false;
		window.ctf_form_one = true;

		var browser = getBrowser();
		var author_v = $("input[name='author']").val();
		var cont = $("#ctf_content").val();
		if(author_v == ''){
			window.ctf_form_one = false;
			pxmu.fail('请填写昵称！');
			return false;
		}
		if($("#ctf_vcode").length > 0){
			var vcode = $("#ctf_vcode").val();
			if(vcode == ''){
				window.ctf_form_one = false;
				pxmu.fail('请填写验证码！');
				return false;
			}
		}
		if(cont == ''){
			window.ctf_form_one = false;
			pxmu.fail('请填写评论内容！');
			return false;
		}

		if(!browser.firefox) $("#ctf_submit").attr("disabled", "disabled");
		setTimeout(function(){
			if(!browser.firefox) $("#ctf_submit").removeAttr("disabled");
			window.ctf_form_one = false;
		}, 2000);

		var _this = $(this);
		$.post(_this.attr("action"), _this.serialize(), function(data){
			window.ctf_form_one = false;
			try{
				var json = eval("("+data+")");
				if(json.status) {
					pxmu.success({msg: json.message, time: 1000});
					setTimeout(function(){
						var Uarr = location.href.split('#');
						location.href = Uarr[0] + "#ctf";
						location.reload();
					}, 1500);
				}else{
					if(json.error) {
						pxmu.fail(json.error);
					}else{
						pxmu.fail(json.message);
					}
				}
			}catch(e){
				alert(data);
			}
		});

		return false;
	});

	$(window).on('scroll',function() {
		topNavScroll();
	});

	$("#go_top").click(function () {
		$('body,html').animate({ scrollTop: 0 }, 500);
		return false;
	});

	$("#guan").click(function () {
		$(this).toggleClass("isnight")
		$("body").toggleClass("style-night")
	});

	$(".wap_headerclick").click(function(){
		$(".wap_display").slideToggle("slow");
		$(this).toggleClass("isnavicon")
	});

	$('.thhotnews_con dl dd p').replaceWith(function(){
		return $("<span />", {html: $(this).html(), class:$(this).attr('class')});
	});

	$('.detail-zhaiyao p').replaceWith(function(){
		return $("<span />", {html: $(this).html(), class:$(this).attr('class')});
	});

	//判断手机端
	var ua = navigator.userAgent;
	var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
		isIphone =!ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
		isAndroid = ua.match(/(Android)\s+([\d.]+)/),
		isMobile = isIphone || isAndroid;

	//判断
	if(isMobile){
		$(".detail-con img").css({"width":"100%"});
	}

	$(".wap_headerclick").click(function () {
		$(".child").slideToggle("slow");
	});

	$(".dot").click(function () {
		$(this).next().slideToggle();
		$(this).parent().siblings().children("ul").slideUp();
	});
});






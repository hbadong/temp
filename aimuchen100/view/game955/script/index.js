/* 955游戏网模板交互脚本（参考网站原生交互逻辑） */
$(function(){

	// ===== 焦点图手风琴轮播（kwicks，参考网站原生） =====
	if($('#focusSlide .bd ul').length){
		$('#focusSlide .bd ul').kwicks({
			max: 396,
			spacing: 0,
			behavior: 'slideshow',
			autoPlay: true,
			interval: 5000,
			interactive: true,
			duration: 500
		});
	}

	// ===== Tab 切换（.js-tab-head → .js-tab-cont） =====
	$('.js-tab-head').each(function(){
		var $head = $(this);
		var $tab = $head.closest('.js-tab');
		var $cont = $tab.find('.js-tab-cont');
		if(!$cont.length) return;
		$head.find('li').on('click', function(){
			var i = $(this).index();
			$head.find('li').removeClass('on');
			$(this).addClass('on');
			$cont.removeClass('on').eq(i).addClass('on');
		});
	});

	// ===== 排行榜 hover 展开（js-hover-list） =====
	$('.js-hover-list .item').on('mouseenter', function(){
		$(this).addClass('on').siblings().removeClass('on');
	});

	// ===== 换一换（js-change） =====
	$('.js-change .btn-change').on('click', function(){
		var $box = $(this).closest('.js-change');
		var $list = $box.find('.js-tab-cont');
		if($list.length < 2) return;
		var cur = $list.filter('.on').index();
		var next = (cur + 1) % $list.length;
		$list.removeClass('on').eq(next).addClass('on');
	});

	// ===== 首页焦点新闻双列表轮换（index-focus-tabs .bd） =====
	(function(){
		var $bd = $('#focusTabsBd');
		if(!$bd.length) return;
		var $uls = $bd.find('ul');
		if($uls.length < 2) return;
		var cur = 0;
		var timer = null;
		var start = function(){
			timer = setInterval(function(){
				cur = (cur + 1) % $uls.length;
				$uls.removeClass('on').eq(cur).addClass('on');
			}, 5000);
		};
		var stop = function(){ clearInterval(timer); };
		$bd.hover(stop, start);
		start();
	})();

	// ===== 详情页 Tab（softDetailTabs） =====
	$('#softDetailTabs li').on('click', function(){
		$(this).addClass('on').siblings().removeClass('on');
		var target = $(this).attr('for');
		if(target && $(target).length){
			$('html,body').animate({ scrollTop: $(target).offset().top - 70 }, 300);
		}
	});

	// ===== 懒加载（_src 属性替换为 src） =====
	$('img[_src]').each(function(){
		var src = $(this).attr('_src');
		if(src){
			$(this).attr('src', src).removeAttr('_src');
		}
	});

	// ===== 返回顶部（back-top） =====
	var $backTop = $('#backTop');
	var onScroll = function(){
		if($(window).scrollTop() > 300){
			$backTop.addClass('enter');
		}else{
			$backTop.removeClass('enter');
		}
	};
	$(window).on('scroll', onScroll);
	onScroll();
	$backTop.on('click', function(){
		$('body,html').animate({ scrollTop: 0 }, 500);
	});

	// ===== 右侧栏智能定位（smartFloat） =====
	if($('#contentMain').length && $('.content-r').length){
		$('.content-r').smartFloat({
			area: '#contentMain',
			backTop: '#backTop',
			placeholder: true
		});
	}

});

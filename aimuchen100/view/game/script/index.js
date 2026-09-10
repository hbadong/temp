$(function(){
	// tab切换
	$('.tab_menu').find('li').on('click', function() {
		var times = $(this).index();
		$(this).addClass('current').siblings().removeClass('current');
		$(this).parents('.tab_box').find('.sub_box').eq(times).show().siblings().hide();
	})
	// 首页tab切换
	$('.tab_menu').find('span').on('click', function() {
		var times = $(this).index();
		$(this).addClass('current').siblings().removeClass('current');
		$(this).parents('.tab_box').find('.sub_box').eq(times).show().siblings().hide();
	})

	$('.itabMenu').find('span').on('click', function () {
		var times = $(this).index();
		$(this).addClass('current').siblings().removeClass('current');
		$(this).parents('.itabBox').find('ul').eq(times).show().siblings().hide();
	})
	
	// tfBox
	$('.tfBox').find('li').hover(function() {
		$(this).find('.tBox').removeClass('hide').siblings('.fBox').addClass('hide');
		$(this).siblings().find('.tBox').addClass('hide').siblings('.fBox').removeClass('hide');
	});

	$('.tab_menus').find('li').on('click', function() {
		var times = $(this).index();
		$(this).addClass('current').siblings().removeClass('current');
		$(this).parents('.tab_boxs').find('.sub_boxs').eq(times).show().siblings().hide();
	})
	// tfBox
	$('.tfBoxs').find('li').hover(function() {
		$(this).find('.tBoxs').removeClass('hide').siblings('.fBoxs').addClass('hide');
		$(this).siblings().find('.tBoxs').addClass('hide').siblings('.fBoxs').removeClass('hide');
	});
	// tabBoxs
	$('.tabBoxs .label').find('span').hover(function() {
		var times = $(this).index();
		$(this).addClass('current').siblings().removeClass('current');
		$(this).parents('.tabBoxs').find('.subBoxs').eq(times).show().siblings().hide();
	});
	
	// 鼠标hover切换
	$(".stra_list ul").find('li').on('mouseover', function() {
		$(this).children("a").css('borderColor', '#fff7f3');
		$(this).prev().children("a").css('borderColor', '#fff7f3');
	});
	$(".stra_list ul").find('li').on('mouseleave', function() {
		$(this).children("a").css('borderColor', '#e0e7eb');
		$(this).prev().children("a").css('borderColor', '#e0e7eb');
	});
	
	$(".yxAList ul").find('li').on('mouseover', function() {
		$(this).children("a").css('borderColor', '#fff7f3');
		$(this).prev().children("a").css('borderColor', '#fff7f3');
	});
	$(".yxAList ul").find('li').on('mouseleave', function() {
		$(this).children("a").css('borderColor', '#e0e7eb');
		$(this).prev().children("a").css('borderColor', '#e0e7eb');
	});
	
	$(".yxdAList ul").find('li').on('mouseover', function() {
		$(this).children("a").css('borderColor', '#fff7f3');
		$(this).prev().children("a").css('borderColor', '#fff7f3');
	});
	$(".yxdAList ul").find('li').on('mouseleave', function() {
		$(this).children("a").css('borderColor', '#e0e7eb');
		$(this).prev().children("a").css('borderColor', '#e0e7eb');
	});
	// slider
	if($(".totalAsk").length>0){ aSlider($(".totalAsk .aSlider"));}
	// like
	// var baseUrl = $('#base_url').val();
	// $('.supAsk').one('click', function(event) {
	// 	var obj = $(this);
	// 	if ($(this).hasClass('current')) {
	// 	    event.preventDefault();
	// 	    return;
	// 	} else { 
	// 	var original = parseInt($(this).text()),
	// 		nowNum;
	// 	if (!isNaN(original)) {
	// 		nowNum = original + 1;
	// 	} else {
	// 		original = 0;
	// 		nowNum = original + 1;
	// 	}
	// 	$(this).addClass('current');
	// 	var id = $(this).attr('data-id');
	// 	var type = $(this).attr('data-type');
	// 	$.ajax({
	// 		url: baseUrl + 'downs/count/',
	// 		type: 'post',
	// 		data: {
	// 			id: id,
	// 			type: type
	// 		},
	// 		success: function(txt) {
	// 			var res = txt.split('|');
	// 			if (res[0] == 1) {
	// 				obj.html('<i class="ico"></i>' + nowNum + '人');
	// 			} else {
					
	// 			}
	// 		}
	// 	});
		
	// 	}
	// });	
	// col
	$('.sAgame span').on('click', function() { AddFavorite();$(this).addClass("current") })
	function AddFavorite(sUrl, sTitle) {
		if (document.all) {
			try {
				window.external.addFavorite(sUrl, sTitle);
			} catch (e1) {
				try {window.external.addToFavoritesBar(sUrl, sTitle);} catch (e2) {alert("请使用Ctrl+D添加收藏");}
			}
		} else if (window.sidebar && window.sidebar.addPanel) {
			window.sidebar.addPanel(sTitle, sUrl, '');
		} else {alert("请使用Ctrl+D添加收藏");}
	};	
	
	$('.serCol').on('click', function() { AddFavorite();$(this).addClass("current") })
	function AddFavorite(sUrl, sTitle) {
		if (document.all) {
			try {
				window.external.addFavorite(sUrl, sTitle);
			} catch (e1) {
				try {window.external.addToFavoritesBar(sUrl, sTitle);} catch (e2) {alert("请使用Ctrl+D添加收藏");}
			}
		} else if (window.sidebar && window.sidebar.addPanel) {
			window.sidebar.addPanel(sTitle, sUrl, '');
		} else {alert("请使用Ctrl+D添加收藏");}
	};	
	$('.ztCol').on('click', function() { AddFavorite();$(this).addClass("current") })
	function AddFavorite(sUrl, sTitle) {
		if (document.all) {
			try {
				window.external.addFavorite(sUrl, sTitle);
			} catch (e1) {
				try {window.external.addToFavoritesBar(sUrl, sTitle);} catch (e2) {alert("请使用Ctrl+D添加收藏");}
			}
		} else if (window.sidebar && window.sidebar.addPanel) {
			window.sidebar.addPanel(sTitle, sUrl, '');
		} else {alert("请使用Ctrl+D添加收藏");}
	};
	
	$('.ztZan').one('click', function (event) {
	    var obj = $(this);
	    if ($(this).hasClass('current')) {
	      event.preventDefault();
		  alert("一天一个赞^^");
	      return;
	    } else {
	      $(this).addClass('current');
	      var id = $(this).attr('data-id');
	      $.ajax({
	        url: '/downs/count/',
	        type: 'post',
	        data: { id: id, type: 1 },
	        success: function (txt) {
	          var res = txt.split('|');
	          if (res[0] == 1) {
				 alert("点赞成功^^")
	          } else {
				 alert("已经点过赞啦^^")
	          }
	        }
	      });
	    }
	  });
	
	// 边框
	if($(".circleList").length > 0){
		$(".circleList ul").each(function(){
			var circleHeight = $(this).height();
			var liHeights = $(".circleList ul li").height();
			var circleHeights = $(this).children(".line").height();
			circleHeights = circleHeight - liHeights;
			$(this).children(".line").css('height',circleHeights);	
		})	
	}
	if($(".hotAsk .list").length > 0){
		var circleHeight = $(".hotAsk .list").height();
		var liHeights = $(".hotAsk .list li").height();
		var circleHeights = $(".hotAsk .list").children(".line").height();
		circleHeights = circleHeight - liHeights;
		$(".hotAsk .list").children(".line").css('height',circleHeights);		
	}
	if($(".relAsk ul").length > 0){
		var circleHeight = $(".relAsk ul").height();
		var liHeights = $(".relAsk ul li").height();
		var circleHeights = $(".relAsk ul").children(".line").height();
		circleHeights = circleHeight - liHeights;
		$(".relAsk ul").children(".line").css('height',circleHeights);		
	}
	// 首页轮播
	var sliderLength = $('.slider').find('li').length,sliderWidth = $('.slider').find('li').width(),dot = 0,dotCont = ' ',slider = '';
	$('.slider').find('ul').css({'width':sliderWidth*sliderLength});
	for(dot ; dot < sliderLength ; dot++){
		dotCont += '<i></i>';
	}
	$('.slider').find('.dot').append(dotCont);
	$('.slider').find('.dot i').first().addClass('current');
	$('.slider').find('.dot').on('click','i',function(){
		slider = $(this).index();
		sliderMove();
	});
	$('.slider').find('.dot_img dd').hover(function(){
		slider = $(this).index();
		sliderMove();
	});
	var zidong = setInterval( run , 3000 );
	function run(){
		slider++;
		if( slider > sliderLength-1){
			slider = 0;
		};
		sliderMove();
	};
	$('.slider').hover(function(){
		clearInterval(zidong);
	},function(){
		zidong = setInterval( run , 3000 );
	});
	function sliderMove(){
		$('.slider').find('.dot i').eq(slider).addClass('current').siblings().removeClass('current');
		$('.slider').find('.dot_img dd').eq(slider).addClass('current').siblings().removeClass('current');
		$('.slider').find('ul').stop().animate({'left': -sliderWidth*slider},500);
	}	
		
	// 多级切换
		$('.hotTab .tab_menu li:nth-child(11n)').after('<li style="opacity:0"></li>'); //添加一个空白div
		$('.hotTab .tab_box .sub_box:nth-child(11n)').after('<div class="sub_box"></div>'); //添加一个空白div
		var smallPre1 = 0;
		var smallPre2 = 0;
		var smallMenuLength1 = Math.ceil($('.tabOne').find('li').length / 12);
		var smallMenuLength2 = Math.ceil($('.tabTwo').find('li').length / 12);
		for (var i = 1; i < smallMenuLength1; i++) {
			$('.dotTabOne').append('<i></i>');
		}
		for (var i = 1; i < smallMenuLength2; i++) {
			$('.dotTabTwo').append('<i></i>');
		}
		$('.dotTabBox').find('i:first').addClass('current'); //绿点
		
		// 左右切换
		$('.tabOne').find('.next').on('click', function() {
			smallPre1++;
			if (smallPre1 >= Math.ceil($(this).parents('.hotTab .tab_menu').find('li').length / 12)) {
				smallPre1 = 0;
			}
			$(this).parents('.hotTab .tab_menu').find('ul').css({
				'top': -196 * smallPre1
			});
			$(this).parents('.hotTab .tab_menu').find('.dotTabBox i').eq(smallPre1).addClass('current').siblings().removeClass(
				'current');
		})
		$('.tabTwo').find('.next').on('click', function() {
			smallPre2++;
			if (smallPre2 >= Math.ceil($(this).parents('.hotTab .tab_menu').find('li').length / 12)) {
				smallPre2 = 0;
			}
			$(this).parents('.hotTab .tab_menu').find('ul').css({
				'top': -196 * smallPre2
			});
			$(this).parents('.hotTab .tab_menu').find('.dotTabBox i').eq(smallPre2).addClass('current').siblings().removeClass(
				'current');
		})
		$('.tabOne').find('.pre').on('click', function() {
			smallPre1--;
			if (smallPre1 < 0) {
				smallPre1 = Math.ceil($(this).parents('.hotTab .tab_menu').find('li').length / 12) - 1;
			}
			$(this).parents('.hotTab .tab_menu').find('ul').css({
				'top': -196 * smallPre1
			});
			$(this).parents('.hotTab .tab_menu').find('.dotTabBox i').eq(smallPre1).addClass('current').siblings().removeClass(
				'current');
		});
		$('.tabTwo').find('.pre').on('click', function() {
			smallPre2--;
			if (smallPre2 < 0) {
				smallPre2 = Math.ceil($(this).parents('.hotTab .tab_menu').find('li').length / 12) - 1;
			}
			$(this).parents('.hotTab .tab_menu').find('ul').css({
				'top': -196 * smallPre2
			});
			$(this).parents('.hotTab .tab_menu').find('.dotTabBox i').eq(smallPre2).addClass('current').siblings().removeClass(
				'current');
		});
		
		if($('.index_four').length>0){
			function moveAnimated(moveElement, targetLeft) {
				clearInterval(moveElement.timeId);
				moveElement.timeId = setInterval(function() {
					var currentLeft = moveElement.offsetLeft;
					var step = 10;
					step = currentLeft < targetLeft ? step : -step;
					currentLeft += step;
					if (Math.abs(targetLeft - currentLeft) > Math.abs(step)) {
						moveElement.style.left = currentLeft + "px";
					} else {
						clearInterval(moveElement.timeId);
						moveElement.style.left = targetLeft + "px";
					}
				}, 8)
			}
			
			var imgWidth = $(".caroList div").width();
			var circleIndex = 0;
			$(".caroList div").each(function(i) {
				$(".caroDot").append("<span></span>");
				$(".caroDot>span:last").attr("index", i);
			})
			$(".caroDot>span:first").attr("class", "on");
			$(".caroList div:first").clone(true).appendTo($(".caroList"))
			$(".caroPre").on("click", function() {
				if (circleIndex == 0) {
					circleIndex = $(".caroDot>span").length;
					$(".caroList").css("left", -circleIndex * imgWidth + "px");
				}
				circleIndex--;
				moveAnimated($(".caroList")[0], -circleIndex * imgWidth)
				$(".caroDot>span").attr("class", "")
				$(".caroDot span:eq(" + circleIndex + ")").attr("class", "on");
			
			})
			
			function clickRight() {
				if (circleIndex == $(".caroList").children().length - 1) {
					circleIndex = 0;
					$(".caroList").css("left", "0px");
				}
				circleIndex++;
				moveAnimated($(".caroList")[0], -circleIndex * imgWidth)
				if (circleIndex == $(".caroList").children().length - 1) {
					$(".caroDot>span:first").attr("class", "on");
					$(".caroDot>span:last").attr("class", "");
				} else {
					$(".caroDot>span").attr("class", "")
					$(".caroDot span:eq(" + circleIndex + ")").attr("class", "on");
				}
			}
			$(".caroNext").on("click", clickRight)	
		}
		
})

function aSlider(param){
	var sliderLength = param.find('li').length,sliderWidth = param.find('li').width(),dot = 0,dotCont = ' ',slider = '';
	param.find('ul').css({'width':sliderWidth*sliderLength});
	for(dot ; dot < sliderLength ; dot++){
		dotCont += '<i></i>';
	}	
	param.find('.aDot').append(dotCont);
	param.find('.aDot i').first().addClass('current');
	param.find('.aDot').on('click','i',function(){
		slider = $(this).index();
		sliderMove();
	});
	var zidong = setInterval( run , 3000 );
	function run(){
		slider++;
		if( slider > sliderLength-1){
			slider = 0;
		};
		sliderMove();
	};
	param.hover(function(){
		clearInterval(zidong);
	},function(){
		zidong = setInterval( run , 3000 );
	});
	function sliderMove(){
		param.find('.aDot i').eq(slider).addClass('current').siblings().removeClass('current');
		param.find('ul').stop().animate({'left': -sliderWidth*slider},500);
	}	
}

	

$(function(){
	var httphost	= window.location.host;
	var protocol	= window.location.protocol;
	var reportUrl = protocol+"//"+httphost+'/';
// 上线修改
	var baseUrl = protocol+"//"+httphost+'/';
//下载按钮
if ($('.downbtn').length > 0) {
    var obj = $('.downbtn');
    var id   = obj.attr('id');
    var type = obj.attr('type');

    $.get(baseUrl + 'downs/detail/'+id+'/'+type+'/', function(res){
        var result = $.parseJSON(res);
        if (result.code == 1) {
            //view report
            $.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+
                '&url='+encodeURIComponent(window.location.href));
			if (returnCitySN.cname.indexOf('上海') >= 0 && (window.location.href.indexOf('/game/') != -1 || window.location.href.indexOf('/app/') != -1  )) {//&&window.location.href.indexOf('/game/')!=-1
					obj.html('<button class="pull">暂无下载</button>');
					$('.down').addClass('dismount').html('暂无下载');
			}else{
				if (result.data.and_url) {
					obj.find('.btnAnd').show().click(function(){
						$.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+'&sys=and');
						location.href = result.data.and_url;
					});
				}
				if (result.data.ios_url) {
					obj.find('.btnIos').show().click(function(){
						$.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+'&sys=ios');
						location.href = result.data.ios_url;
					});
				}
				if (result.data.pc_url) {
					//$(".downbtn .pc").html('<img src="https://www.youxibao.com/public/img/xg_cq.png" alt="996传奇盒子" /> ')
					obj.find('.pc').show().click(function(){
						$.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+'&sys=pc');
						location.href = result.data.pc_url;
					});
				}
			}

            // if (result.data.and_url) {
            //     obj.find('.btnAnd').show().click(function(){
            //         $.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+'&sys=and');
            //         location.href = result.data.and_url;
            //     });
            // }
            // if (result.data.ios_url) {
            //     obj.find('.btnIos').show().click(function(){
            //         $.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+'&sys=ios');
			// 		window.open( result.data.ios_url);
            //     });
            // }
            // if (result.data.pc_url) {
            //     obj.find('.pc').show().click(function(){
            //         $.getJSON(reportUrl + 'home/?callback=?&data='+ encodeURIComponent(JSON.stringify(result.data))+'&sys=pc');
            //         location.href = result.data.pc_url;
            //     });
            // }

        }
    }); 
}

// ztbtn
if ($('.ztxq_btn').length > 0) {
	$('.ztxq_btn').on('click', function () {
	        var obj = $(this);
	        id = obj.attr('data_id');
	        type = obj.attr('type');
	        if (returnCitySN.cname.indexOf('上海') >= 0) {
				if (type==1){
					location.href = baseUrl + 'game/' + id + '.html';
				}else{
					location.href = baseUrl + 'app/' + id + '.html';
				}

	        } else {
	            $.get(baseUrl + 'downs/detail/' + id + '/' + type+'/', function (res) {
	                var result = JSON.parse(res);
	                if (result.code == 1) {
	                    if (result.data['state'] != 2) {
	                        if (result.data['and_url']) {
	                            $.getJSON(reportUrl + 'home/?callback=?&data=' + encodeURIComponent(JSON.stringify(result.data)) + '&sys=and');
	                            location.href = result.data['and_url'];
	                        }
	                    }
	                }
	            });
	        }
	    });
	}    

})

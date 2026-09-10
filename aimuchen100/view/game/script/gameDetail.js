$(function () {
  if ($('.contHidden').find('.cont').length > 0) {
    $('.contHidden').css('height', 'auto');
    $('.openDetail .lop').remove();
    $('.contHidden .linear').remove();
  };

  function adjustImageSizes(imageSelector) {  
    if ($(imageSelector).length > 0) {  
        $(imageSelector).each(function() {  
            let $img = $(this);  
            let widthImg = $img.width();  
            let heightImg = $img.height();  
            
            if (widthImg > 0 && heightImg > 0) {  
                $img.css({  
                	"margin": "10px auto",
                    "height": "auto",  
                    "width": "auto",  
                    "max-width": "100%",  
                    "max-height": widthImg < heightImg ? "660px" : "460px"  
                });  
            }  
        });  
    }  
}  
 
adjustImageSizes('.ga_content .cont img');
adjustImageSizes('.straDeCont .cont img');

  
  lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'showImageNumberLabel': false
  })

  $(".ga_intro ul li").on('click', function () {
    $('html,body').animate({
      scrollTop: $(".ga_same").offset().top
    }, 1000);
    var index = $(this).data('index');
    $(".ga_same .tab_menu li").each(function (k, v) {
      if (k == index) {
        $(this).addClass('current').siblings().removeClass('current');
      }
    });
    $(".ga_same .tab_cont .sub_box").each(function (k, v) {
      var key = $(v).data('index');
      if (key == index) {
        $(this).removeClass('hide').siblings().addClass('hide');
      }
    });
  });

  $(".ga_intro .down").on('click', function () {
    $('html,body').animate({
      scrollTop: $(".ga_address").offset().top
    }, 800);
  });
  $(".ga_intro .no").on('click', function () {
    $('html,body').animate({
      scrollTop: $(".ga_address").offset().top
    }, 800);
  });

  // 权限
  var ruleHtml = '';
  ruleHtml += '<div class="gRule hide">'
  ruleHtml += '<div class="ruleTxt">'
  ruleHtml += '<div class="yxbqxbg icon">'
  ruleHtml += '<p>应用权限</p><i class="icon qxclose" id="closebtn"></i>'
  ruleHtml += '</div>'
  ruleHtml += '<div class="ruleMain">'
  ruleHtml += '<p>此应用程序需要访问以下内容</p><strong>写入外部存储</strong>'
  ruleHtml += '<p>允许程序写入外部存储，如SD卡上写文件</p><strong>完全的网络访问权限</strong>'
  ruleHtml += '<p>允许该应用创建网络套接字和使用自定义网络协议。浏览器和其他某些应用提供了向互联网发送数据的途径，因此应用无需该权限即可向互联网发送数据</p><strong>拍摄照片和视频</strong>'
  ruleHtml += '<p>允许访问摄像头进行拍照或录制视频</p><strong>读取手机状态和身份</strong>'
  ruleHtml += '<p>允许应用访问设备的电话功能。此权限可让应用确定本机号码和设备ID、是否正处于通话状态以及拨打的号码。</p><strong>查看网络状态</strong>'
  ruleHtml += '<p>允许应用程序查看所有网络的状态。例如存在和连接的网络</p><strong>查看WLAN状态</strong>'
  ruleHtml += '<p>允许程序访问WLAN网络状态信息</p><strong>控制震动</strong>'
  ruleHtml += '<p>允许应用控制振动设备</p><strong>拨打电话</strong>'
  ruleHtml += '<p>允许一个程序初始化一个电话拨号不需通过拨号用户界面需要用户确认，应用程序执行可能需要您付费</p>'
  ruleHtml += '</div>'
  ruleHtml += '</div>'
  ruleHtml += '</div>'
  $('footer').after(ruleHtml);


  $(".qs").on('click', function () {
    let classify = $("input[name='type_info']").val()
    let common_id = $("input[name='common_id']").val()
    let md5 = $("input[name='md5']").val()
    let id = $("input[name='game_id']").val()
    let pcUrl = $("input[name='pcUrl']").val()

    if (!common_id && !md5){
      $(".gRule").show();
    }

    $.ajax({
      url: pcUrl+'api/permission/',
      type: 'POST',
      data: {
        'id': id,
        'md5': md5,
        'common_id': common_id,
        'classify': classify,
      },
      success: function (data) {
        res = $.parseJSON(data);
        if (res.code == 200 && res.data) {
          if (res.data.permission){
            $(".gRule").html('')
            let strArray  = res.data.permission.split(',')

            var cmslist= permission;

            let permissionHtml = '<div class="ruleTxt">';
            permissionHtml += '<div class="yxbqxbg icon"><p>应用权限</p><i class="icon qxclose" id="closebtn"></i></div>';
            permissionHtml += '  <div class="ruleMain">';
            for ( i=0;i<cmslist.length;i++){
              if (strArray.indexOf(cmslist[i]["name"]) >= 0) {
                permissionHtml += '  <strong class="m-permission-'+cmslist[i]["level"]+'">'+cmslist[i]["explain"]+'</strong>';
                permissionHtml += '  <p>'+cmslist[i]["description"]+'</p>';
              }
            }
            permissionHtml += '  </div>';

            console.log(permissionHtml)
            $(".gRule").html(permissionHtml)
            $(".gRule").show();
            $(".qxclose").on('click', function () { $(".gRule").hide();});
            $(".gSure").on('click', function () { $(".gRule").hide();});
          }else{
            $(".gRule").show();
          }
        } else {
          $(".gRule").show();
          return;
        }
      }
    })
  });


  $('.qxclose').on('click', function () { $('.gRule').hide(); })

  // 隐私
  var ysHtml = '';
  ysHtml += '<div class="yszcbox hide">'
  ysHtml += '<div class="ysTxt">'
  ysHtml += '<div class="yxbysbg icon">'
  ysHtml += '<p>隐私声明</p><i class="icon ysclose" id="closebtn"></i>'
  ysHtml += '</div>'
  ysHtml += '<div class="ysMain">'
  ysHtml += '<p>严格遵守法律法规，遵循以下隐私保护原则，为您提供更加安全、可靠的服务：</p><strong>1、安全可靠：</strong>'
  ysHtml += '<p>我们竭尽全力通过合理有效的信息安全技术及管理流程，防止您的信息泄露、损毁、丢失。</p><strong>2、自主选择：</strong>'
  ysHtml += '<p>我们为您提供便利的信息管理选项，以便您做出合适的选择，管理您的个人信息</p><strong>3、保护通信秘密：</strong>'
  ysHtml += '<p>我们严格遵照法律法规，保护您的通信秘密，为您提供安全的通信服务。</p><strong>4、合理必要：</strong>'
  ysHtml += '<p>为了向您和其他用户提供更好的服务，我们仅收集必要的信息。</p><strong>5、清晰透明：</strong>'
  ysHtml += '<p>我们努力使用简明易懂的表述，向您介绍隐私政策，以便您清晰地了解我们的信息处理方式。</p><strong>6、将隐私保护融入产品设计：</strong>'
  ysHtml += '<p>我们在产品和服务研发、运营的各个环节，融入隐私保护的理念。</p><strong>本《隐私政策》主要向您说明：</strong>'
  ysHtml += '<p>我们收集哪些信息 我们收集信息的用途 您所享有的权利</p><strong>希望您仔细阅读《隐私政策》</strong>'
  ysHtml += '<p>'
  ysHtml += '为了让您有更好的体验、改善我们的服务或经您同意的其他用途，在符合相关法律法规的前提下，我们可能将通过某些服务所收集的信息用于我们的其他服务。例如，将您在使用我们某项服务时的信息，用于另一项服务中向您展示个性化的内容或广告、用于用户研究分析与统计等服务。'
  ysHtml += '</p>'
  ysHtml += '<p>若您使用服务，即表示您认同我们在本政策中所述内容。除另有约定外，本政策所用术语与《服务协议》中的术语具有相同的涵义。</p>'
  ysHtml += '<p>如您有问题，请联系我们。</p>'
  ysHtml += '</div><span class="ysSure">确定阅读完毕</span>'
  ysHtml += '</div>'
  ysHtml += '</div>'
  $('footer').after(ysHtml);
  $(".ys").on('click', function () { $(".yszcbox").show(); });
  $('.ysclose').on('click', function () { $('.yszcbox').hide(); })
  
  // feedback
//   var feBaHtml = '';
//   feBaHtml +=`<div class="feedBack hide">
// 			<div class="feBaBox">
// 			<div class="feBaClose"><i class="ico"></i></div>
// 			<div class="feHead">游戏反馈</div>
// 			<div class="feBack">
//             <p>反馈原因</p>
//             <div class="info">       
//             <div class='checkbox'> <input type='checkbox' id='checkbox1' data-value="1" name='checkbox[]'> <label for='checkbox1'>有色情、暴力、反动等不良内容</label> </div>
//             <div class='checkbox'> <input type='checkbox' id='checkbox2' data-value="2" name='checkbox[]'> <label for='checkbox2'>有抄袭、侵权嫌疑</label> </div>
//             <div class='checkbox'> <input type='checkbox' id='checkbox3' data-value="3" name='checkbox[]'> <label for='checkbox3'>广告很多、含有不良插件</label> </div>
//             <div class='checkbox'> <input type='checkbox' id='checkbox4' data-value="4" name='checkbox[]'> <label for='checkbox4'>无法正常安装或进入游戏</label> </div>
//             </div>
//             <p>其他原因</p>
//             <textarea name="remake" placeholder="请输入补充说明"></textarea>
// 			</div>
// 			<div class="h20"></div>
// 			<div class="telBox"> <span>联系方式</span> <input type="tel" name="tel"  placeholder="请输入手机号码"> </div>
// 			<div class="feSubmit"> <input type="button" class="submit" name="submit" value="提交反馈"> </div>    
// 			</div>
// 			</div>`
// 	if($(".feBaBtn").length>0){
// 		$("body").append(feBaHtml);
// 	}
	
// 	$(".feBaBtn").on('click', function () { $(".feedBack").show();});
// 	$(".feBaClose").on('click', function (e) {
// 	  let ev = e || window.event;
// 	  if(ev && ev.stopPropagation){
// 	    ev.stopPropagation(); 
// 	  }else{
// 	    ev.cancelBubble = true; 
// 	  }
// 	  $(".feedBack").hide();
// 	});
// 	$(".submit").click(function () {
// 	    let game_id = $.trim($("input[name='game_id']").val());
// 	    let game_name = $.trim($("input[name='game_name']").val());
// 	    let type_info = $.trim($("input[name='system_version']").val());
// 		let telTxt    = $.trim($(".telBox input[name='tel']").val())
// 	    let reason = "";
// 	    $(".feBack input[name='checkbox[]']:checked").each(function (k, v) {
// 	        reason += ',' + $(v).data('value')
// 	    })
			
// 	    if (!reason) {
// 	        alert('请选择反馈原因！');
// 	        return;
// 	    }
				
// 	    let tel = isPoneAvailable(telTxt);
// 	    if (!tel) {
// 	        alert('请输入手机号码!');
// 	        return;
// 	    }
			
// 	    let remake = $.trim($(".feBack textarea[name='remake']").val());
			
			
// 	    $.ajax({
// 	         url: '/downs/feedback/',
// 	        type: 'POST',
// 	        data: {
// 	            'game_id': game_id,
// 	            'reason': reason,
// 	            'tel': telTxt ,
// 	            'remake': remake,
//               'system': 1,
// 	            'type': type_info, //1-下载
// 	            'game_name': game_name,
// 	        },
// 	        success: function (data) {
//              res = $.parseJSON(data);
// 	            if (res.code == 200) {
// 	                alert('反馈成功,谢谢您！');
// 	                $(".feedBack").hide();
//                   return false;
// 	            } else {
// 	                alert(res.msg);
//                   $(".feedBack").hide();
//                   return false;
// 	            }
			
// 	        }
// 	    });
// 	})

// 预约
  var yyHtml = '';
  yyHtml += '<div class="orderList hide">'
  yyHtml += '<div class="orderBox">'
  yyHtml += '<div class="oClose"></div>'
  yyHtml += '<div class="oOrder ">'
  yyHtml += '<div class="oTitle"><i class="ico"></i>'
  yyHtml += '<p>游戏预约</p>'
  yyHtml += '</div>'
  yyHtml += '<form action="" method="post">'
  yyHtml += '<input type="text" name="tel" placeholder="请填写你的手机号码" class="tel " />'
  yyHtml += '<span class="msg"></span>'
  yyHtml += '<p>游戏正式上线前，我们将通过<em>免费预约短信</em>提醒您</p>'
  yyHtml += '<input type="button" name="btn" value="立即预约" class="oBtn" id="oBtn" />'
  yyHtml += '</form>'
  yyHtml += '</div>'
  yyHtml += '<div class="oWin hide">'
  yyHtml += '<div class="yxbbg"></div>'
  yyHtml += '<div class="winbg"></div>'
  yyHtml += '<p>预约成功</p>'
  yyHtml += '<p>我们将通过<em>免费预约短信</em>通知您</p>'
  yyHtml += '<span class="oKnow">知道了</span>'
  yyHtml += '</div>'
  yyHtml += '<div class="oLose hide">'
  yyHtml += '<p>当前人数众多，预约失败！</p><i class="ico"></i><span class="oKnow">知道了</span>'
  yyHtml += '</div>'
  yyHtml += '<div class="oAgin hide">'
  yyHtml += '<p>您已预约，请等待通知！</p><i class="ico"></i><span class="oKnow">知道了</span>'
  yyHtml += '</div>'
  yyHtml += '</div>'
  yyHtml += '</div>'

  $('.wraper').after(yyHtml);
  $(".order").on('click', function () { $(".orderList").show(); });
  $('.oClose').on('click', function () { $('.orderList').hide(); })
  $('.oKnow').on('click', function () { $('.orderList').hide(); })
  // 预约
  $('.ga_intro').find('.order').on('click', function () {
    $(".orderList").show();
  });
  $('.orderList').find('.oClose').on('click', function () {
    $(".orderList").hide();
  });
  $('.orderList').find('.oKnow').on('click', function () {
    $(".orderList").hide();
  });

 
 
 
 
  
  
})
$(".showImg").html($(".hideImg").html())


// 正则：判断手机号码
function isPoneAvailable(tel) {
  var myreg = /^[1][3,4,5,6,7,8,9][0-9]{9}$/;
  if (!myreg.test(tel)) {
    return false;
  } else {
    return true;
  }
}
// 判断手机号是否正确
function telResult(telText) {
  var telText = $('.tel').val(), result = isPoneAvailable(telText);//判断手机是否正确
  if (result === false) {
    $('.tel').css({ 'color': '#fe684d', 'border-color': '#fe684d' });
    $('.msg').text("请填写正确的手机号")
    $('.msg').css({ 'content': '"请填写正确的手机号"', 'color': '#fe684d', 'font-size': '14px', 'text-align': 'left', 'display': 'block', 'width': '392px', 'margin': '0 auto' });
    return false;
  } else {
    $('.tel').css({ 'color': '#333', 'border-color': '#c2c2c2' });
    $('.msg').text("")
    return true;
  }
}

$(document).on("click",".oBtn", function () {
  var telText = $('.tel').val();
  let game_id = $("input[name='game_id']").val().trim();

  let game_name = $("input[name='game_name']").val().trim();

  let system = $("input[name='system_version']").val().trim();

  if (game_id == '') {
    alert('游戏id不能为空'); return false;
  }

  telResult(telText);

  $.ajax({
    url: "/downs/reservation",
    type: 'post',
    data: {
      'phone': telText,
      'game_id': game_id,
      'game_name': game_name,
      'system': system
    },
    success: function (res) {
      let result = JSON.parse(res); //由JSON字符串转换为JSON对象
      if (result.code == 0) {
        $('.msg').text(result.msg)
        $('.msg').css({ 'content': '"+result.msg+"', 'color': '#fe684d', 'font-size': '14px', 'text-align': 'left', 'display': 'block', 'width': '392px', 'margin': '0 auto' });
        return false;
      } else if (result.code == 200) {
        //预约成功
        $(".oOrder").hide();
        $(".oWin").show();
      } else if (result.code == 300) {
        //已预约
        $(".oOrder").hide();
        $(".oAgin").show();

      } else if (result.code == 400) {
        //预约失败
        $(".oOrder").hide();
        $(".oLose").show();
      }
    }
  })
})
$('body').delegate('.tel', 'blur', function () {
  telResult();
});

function hscroll2(id, flag, min, move, childlevel, time) {
  min = min || 2;
  move = move || 1;
  time = time || 300;
  childlevel = childlevel || 1;
  var parent = $("#" + id + ":not(:animated)");
  if (childlevel == 1) {
    var kids = parent.children();
  } else {
    var kids = parent.children().eq(0).children();
  }

  if (kids.length < min) return false;
  var kid = kids.eq(0);
  var kidWidth = kid.width() + parseInt(kid.css("paddingLeft")) + parseInt(kid.css("paddingRight")) + parseInt(kid.css(
    "marginLeft")) + parseInt(kid.css("marginRight"));
  var margin = (kidWidth * move);
  if (flag == "left") {
    var s = parent.scrollLeft() + margin;
    parent.animate({
      'scrollLeft': s
    }, time);
  } else {
    var s = parent.scrollLeft() - margin;
    parent.animate({
      'scrollLeft': s
    }, time);
  }
  return false;
}


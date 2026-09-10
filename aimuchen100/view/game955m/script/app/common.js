define(function(require, exports, module) {

    // 公用初始化
    exports.init = function() {

        // 头部搜索
        $('#topSearchForm').each(function(){
            var $form = $(this);
            $form.on('submit', function(){
                var val = $form.find('.search-input').val();
                if(val && val.replace(/\s/g, '')){
                    return true;
                }
                return false;
            })
        })

        // 头部菜单-显示/隐藏
        var $body = $('html');
        $('#topMenuTap').on('click', function(){
            $body.toggleClass('top-menu-cover');
        })

        $('#topMenu').each(function(){
            var $menu = $(this);
            $menu.find('.hd-item').on('click', function(){
                var $this = $(this), index = $this.index();
                $this.addClass('on').siblings().removeClass('on');
                $menu.find('.bd-item').removeClass('on').eq(index).addClass('on');
            })
            $menu.find('.top-menu-overlay').on('click', function(){
                $body.removeClass('top-menu-cover');
            })
        })

        // 回到顶部
        exports.goToTop();

        // 顶部搜索
        exports.searchBar();
    }

    exports.searchBar = function(){

        var vm = new Vue({
            el: '#taptapSearch',
            data: {
                searchInput: '',
                searchOpen: false,
                hotwordData: []
            },
            methods: {
                touchmove: function(e){
                    e.preventDefault();
                },
                closeTap: function(){
                    this.searchOpen = false;
                },
                inputClear: function(){
                    this.searchInput = '';
                    this.$refs.searchInput.focus()
                },
                onSubmit: function(e){
                    var val = this.searchInput.replace(/^\s+|\s+$/g, '');
                    if(!val){
                        e.preventDefault();
                    }
                }
            },
            created: function(){
                var that = this;
                var hotwordData = sessionStorage.getItem("searchHotwordData");

                // 热门关键词
                if(hotwordData){
                    that.hotwordData = JSON.parse(hotwordData);
                }
                else{

                    console.log(window);

                    //window.PC_PATH +'index.php?m=content&c=index&a=get_hotsearchkey'
                    //window.MURL +'/index.php?m=content&c=index&a=get_hotsearchkey'

                    // $.ajax({
                    //     url: window.MURL +'/hotsearchkey/',
                    //     dataType: 'json',
                    //     success: function(res){
                    //         var data = res && res.data;
                    //         if(data){
                    //             that.hotwordData = data;
                    //             sessionStorage.setItem("searchHotwordData", JSON.stringify(data));
                    //         }
                    //
                    //     }
                    // })
                }
            }
        })

        $('#searchTap').on('click', function(){
            // console.log(vm.searchOpen)
            vm.searchOpen = true;
            setTimeout(function(){
                vm.$refs.searchInput.focus();
            }, 20)
        })
    }

    // 首页
    exports.index = function() {
        $('#iFocus').each(function(){
            var $focus = $(this);
            TouchSlide({
                slideCell: "#iFocus",
                titCell: ".hd",
                mainCell: '.bd',
                effect: 'leftLoop',
                interTime: 5000,
                autoPage: true,
                autoPlay: false,
                startFun: function(i){
                    // console.log(i);
                }
            });
        })

        $('#specialTopic').each(function(){
            var $focus = $(this);
            TouchSlide({
                slideCell: "#specialTopic",
                titCell: ".hd ul",
                mainCell: '.bd ul',
                effect: 'leftLoop',
                interTime: 5000,
                autoPage: true,
                autoPlay: false
            });
        })

        // 友情链接
        $('#friendLink').each(function(){
            var $cont = $(this).find('.section-bd .list');
            var $item = $(this).find('.section-bd li');
            var offsetY = 0;
            var lineNum = 0;
            var htmlStr = '';
            // 重构html结构，用于轮播滚动
            $item.each(function(){
                if(this.offsetTop > offsetY){
                    if(offsetY){
                        htmlStr += '</ul><ul>';
                    }
                    lineNum++;
                    offsetY = this.offsetTop;
                }
                htmlStr += this.outerHTML;
            });
            htmlStr = '<ul>'+ htmlStr +'</ul>';
            $cont.html(htmlStr);
            // console.log(lineNum);
            if($item.length && lineNum > 1){
                TouchSlide({
                    slideCell: "#friendLink",
                    mainCell: '.list',
                    effect: 'topLoop',
                    interTime: 3000,
                    autoPlay: true
                });
            }
        });

        // tab切换
        $('.js-tabs .section-hd .name').on('click', function() {
            var t = $(this),
                s = t.parents('.js-tabs'),
                i = t.index();

            t.addClass('on').siblings().removeClass('on');
            s.find('.section-bd-item').removeClass('on').eq(i).addClass('on');
        });

        // 精选分类tab切换
        $('.section-category .section-hd  .links  a').on('click', function() {
            var t = $(this),
                s = t.parents('.section-category'),
                i = t.index();
            t.addClass('on').siblings().removeClass('on');
            if (i == 0) {
                s.find('.category-list').addClass('none').eq(i).removeClass('none');
            } else {
                s.find('.category-list').addClass('none').eq(i - 1).removeClass('none');
            }
        })
    }

    // 导航
    exports.navbar = function() {
        $('#navMore').on('click', function() {
            $('body').toggleClass('category-menu-cover');
        })
        $('.category-menu-overlay').on('click', function() {
            $('body').removeClass('category-menu-cover');
        })

        // 最新/最热 切换
        $('#navTab .name').on('click', function() {
            var target = $(this).attr('for');

            $('#navTab .name').removeClass('on');
            $(this).addClass('on');

            $('.soft-list').attr('id', '').parent().addClass('hide');
            $(target).attr('id', 'softList').parent().removeClass('hide');
        })
    }

    // 软件列表
    exports.softlist = function() {
        var loader = this.pullToLoading({
            url: '/index.php?m=content&c=index&a=sj_app_ajax',
            data: {
                page: 2,
                pageSize: 25,
                type: 1
            }
        });

        $('#softContent .list-head .btn-gray').on('click', function(){
            var $this = $(this);
            var dataType = $this.attr('data-type');

            if($this.hasClass('on')){
                return false;
            }
            $this.addClass('on').siblings().removeClass('on');
            loader.resetData({
                type: dataType,
                page: 1,
                pageSize: 25
            })
        })
    }

    // pc软件列表
    exports.pcsoftlist = function(option) {
        $('#softContent .btn-dropdown').on('click', function(){
            $(this).parent().toggleClass('open');
        })

        var loader = this.pullToLoading({
            url: '/index.php?m=content&c=index&a=pc_app_ajax',
            data: {
                catid:option.catid,
                type: 1,
                page: 2,
                pageSize: 25
            }
        });

        $('#softContent .list-head .btn-gray').on('click', function(){
            var $this = $(this);
            var dataType = $this.attr('data-type');

            if($this.hasClass('on')){
                return false;
            }
            $this.addClass('on').siblings().removeClass('on');
            loader.resetData({
                catid:option.catid,
                type: dataType,
                page: 1,
                pageSize: 25
            })
        })

    }


    // 搜索列表
    exports.searchlist = function(option) {
        var loader = this.pullToLoading({
            url: option.search_domain+'/api/search_ajax',

            data: {
                search_keywords:option.search_keywords,
                type: 1,
                page: 2,
                pageSize: 10
            }
        });
        var loaderStore = $.extend({}, {getHtml:loader.getHTML});
        var getHtml = loaderStore.getHtml;


        $('.list-tabs .item').on('click', function(){
            var $this = $(this);
            var dataType = $this.attr('data-type');

            if($this.hasClass('on')){ return false }
            $this.addClass('on').siblings().removeClass('on');


            var $list = $('#listContent');
            var config = {
                search_keywords:option.search_keywords,
                type: dataType,
                page: 1,
                pageSize: 10
            }
            if(dataType == 2){
                $list.parent().addClass('article-list');
                loader.resetData(config, function(data){
                    var baseUrl = window.MURL || '';
                    var arr = [];
                    $.each(data, function(i, v){
                        var item = '<a class="item" href="'+v.url +'">'+
                            '<img class="pic lazy" src="'+ v.thumb +'" data-src="'+v.thumb+'" alt="'+ v.title +'">'+
                            '<div class="con">'+
                                '<h3 class="tit">'+ v.s_title +'</h3>'+
                                '<p class="time">'+ v.updatetime +'</p>'+
                            '</div>'+
                        '</a>';
                        arr.push(item);
                    })
                    return arr.join('');
                })
            }else{
                $list.parent().removeClass('article-list');
                loader.resetData(config, getHtml);
            }
        })
    }

    // 咨询列表
    exports.newslist = function(option) {
        var loader = this.pullToLoading({
            url: '/api/news_ajax',
            data: {
                catid:option.catid,
                type: 1,
                page: 2,
                pageSize: 20
            },
            getHTML: function(data){
                var baseUrl = window.MURL || '';
                var arr = [];
                $.each(data, function(i, v){
                    var item = '<a class="item" href="'+ baseUrl+v.url +'">'+
                        '<img class="pic lazy" src="'+ v.thumb +'" data-src="'+v.thumb+'" alt="'+ v.title +'">'+
                        '<div class="con">'+
                            '<h3 class="tit">'+ v.title +'</h3>'+
                            '<p class="time">'+ v.updatetime +'</p>'+
                        '</div>'+
                    '</a>';
                    arr.push(item);
                })
                return arr.join('');
            }

        });
    }

    //专题列表
    exports.speciallist = function(option) {
        console.log(option)
        var loader = this.pullToLoading({
            url: '/index.php?m=content&c=sj_api&a=get_special_lists2',
            data: {
                format:option.format,
                pagesize:option.pagesize,
                zttype:option.zttype,
                type: 1,
                page: 2,
                pageSize: 12
            },
            getHTML: function(data){
                // var baseUrl = window.MURL || '';
                var arr = [];
                $.each(data, function(i, v){
                    // var item ='<a class="item" href="'+ v.url +'">'
                    // + '<img class="pic lazy" src="//6188.129w.com/statics/mobile/images/blank.png"  data-src="'+ v.thumb +'" alt="'+ v.title_sort +'">'
                    // + '<div class="tit">'+ v.title_sort +'</div></a>';
                    // arr.push(item);
                    var item ='<div class="collection_item"><a class="img" href="'+ v.url +'">'
                    + '<img class="pic lazy" src="//6188.129w.com/statics/mobile/images/blank.png"  data-src="'+ v.thumb +'" alt="'+ v.title_sort +'"></a>'
                    + '<div class="info"><a class="game" href="'+ v.url +'">'
                    + '<img class="pic lazy" src="//6188.129w.com/statics/mobile/images/blank.png"  data-src="'+ v.thumb2 +'" alt="'+ v.title2 +'"></a>'
                    + '<p>'+ v.title2 +'</p>'
                    + '<a class="btn" href="'+ v.url +'">查看</a></div>'
                    + '<div class="desc">'+ v.description +'</div></div>'
                    arr.push(item);


                })
                return arr.join('');
            }

        });
    }

    // 游戏列表
    exports.gamelist = function(option) {

        $('#softContent .btn-dropdown').on('click', function(){
            $(this).parent().toggleClass('open');
        })

       // console.log(option);


        var loader = this.pullToLoading({
            url: '/api/sj_game_ajax',
            data: {
                app_classtype:option.app_classtype,
                app_liangwang:option.app_liangwang,
                current_catid:option.current_catid,
                type: 1,
                page: 2,
                pageSize: 25
            }
        });

        $('#softContent .list-head .btn-gray').on('click', function(){
            var $this = $(this);
            var dataType = $this.attr('data-type');

            if($this.hasClass('on')){
                return false;
            }
            $this.addClass('on').siblings().removeClass('on');
            loader.resetData({
                app_classtype:option.app_classtype,
                app_liangwang:option.app_liangwang,
                current_catid:option.current_catid,
                type: dataType,
                page: 1,
                pageSize: 25
            })
        })
    }
    // 首页游戏加载
    exports.gamelist2 = function(option) {

        var loader = this.pullToLoading({
            url: '/api/sj_game_ajax',
            data: {
                app_classtype:option.app_classtype,
                app_liangwang:option.app_liangwang,
                current_catid:option.current_catid,
                type: 2,
                page: 2,
                pageSize: 10
            }
        });

    }


    // 软件详情
    exports.softDetail = function() {

        // 软件截图
        TouchSlide({
            slideCell: "#softFocus",
            titCell: ".hd ul",
            mainCell: ".bd ul",
            autoPage: true //自动分页
        });

        // 软件截图预览弹窗
        // exports.imageView();


        // 其他版本
        $('#softVersionList').each(function(){
            var $list = $(this);
            if($list.find('.item').length > 5){
                $list.addClass('list-more');
            }
            $list.find('.btn-more').on('click', function(){
                $list.addClass('list-show');
            })
        })

        // 手游排行榜
        exports.mobileGamesList();


        // 图片懒加载
        exports.imgLazyLoad({auto: false});

        exports.clickimg('#softFocus')
        exports.clickimg('#softRemarkText .text')

    }


    // 排行榜
    exports.toplist = function(option) {
        var $topList = $('#topList'),
            $drops = $('#topList .dropdown');

        $topList.find('.btn-dropdown').on('click', function(){
            var $drop = $(this).parent();
            if(!$drop.hasClass('open')){
                $drops.removeClass('open');
                $drop.addClass('open');
            }
            else{
                $drops.removeClass('open');
            }
        })

        // var loader = this.pullToLoading({
        //     url: '/index.php?m=content&c=index&a=sj_xzph_ajax&pc_hash=WrCDxe',
        //     data: {
        //         lianwang:1,
        //         catid:562,
        //         page: 2,
        //         pageSize: 25
        //     }
        // });

        $('.wyph').on('click', function(){
            var $this = $(this);
            var lianwang = $this.attr('data-type');
            var catid = $this.attr('data');

            $.ajax({
                    url: '/index.php?m=content&c=index&a=sj_xzph_ajax',
                    type: 'get',
                    data: {
                        lianwang:lianwang,
                        catid:catid,
                        app_classtype:option.app_classtype,
                    },
                    dataType: 'json',
                    success: function(res) {
                        var data = res.data;

                        var topArr = [];
                        var listArr = [];


                                var item = '<a class="col c1" href="'+data[1]['url']+'">'+
                                '<div class="num n2">2</div>'+'<img class="pic" src="'+data[1]['thumb']+'" alt="'+data[1]['title']+'">'+
                                '<div class="tit">'+data[1]['title']+'</div>'+'<div class="m-star star'+data[1]['stars']+'"></div></a>'
                                +'<a class="col c2" href="'+data[0]['url']+'">'+
                                '<div class="num n1">1</div>'+'<img class="pic" src="'+data[0]['thumb']+'" alt="'+data[0]['title']+'">'+
                                '<div class="tit">'+data[0]['title']+'</div>'+'<div class="m-star star'+data[0]['stars']+'"></div></a>'
                                +'<a class="col c3" href="'+data[2]['url']+'">'+
                                '<div class="num n3">3</div>'+'<img class="pic" src="'+data[2]['thumb']+'" alt="'+data[2]['title']+'">'+
                                '<div class="tit">'+data[2]['title']+'</div>'+'<div class="m-star star'+data[2]['stars']+'"></div></a>'
                                ;
                                topArr.push(item);
                           $.each(data, function(i, v){
                            if(i>2){
                                var ss=i+1;
                                var item = '<a class="list-item flex" href="'+ v.url +'">'+
                                    '<div class="num">'+ss+'</div>'+
                                    '<div class="col"><img class="pic" src="'+ v.thumb +'" alt="'+ v.title +'"></div>'+
                                    '<div class="con flex-item">'+
                                        '<div class="tit">'+ v.title +'</div>'+
                                        '<div class="star star'+ v.stars +'"></div>'+
                                        '<div class="txt">'+
                                            '<span class="attr">'+ v.catname +'</span>'+
                                            '<span class="attr">大小:'+ v.filesize2 +'</span>'+
                                        '</div>'+
                                    '</div>'+
                                    '<div class="col">'+
                                        '<span class="btn btn-download">查&nbsp;看</span>'+
                                    '</div>'+
                                '</a>';
                                listArr.push(item);
                            }

                        })
                        $topList.find('.top-list-body .list-hd').html(topArr.join(''));
                        $topList.find('.top-list-body .list').html(listArr.join(''));
                    },
                    error: function(data){
                    }
            })
            $this.addClass('on').parent().siblings().find('a').removeClass('on');
            $this.parents('.dropdown').addClass('on')
            .removeClass('open')
            .siblings().removeClass('open on')
            .find('a').removeClass('on');
        })

    }

    // 游戏列表页
    exports.game = function(){
        $('#navMore').on('click', function(){
            $('body').toggleClass('category-menu-cover');
        })
        $('.category-menu-overlay').on('click', function(){
            $('body').removeClass('category-menu-cover');
        })

        // 根据设备类型判断跳转
        var device = window.device; //当前系统设备
        var deviceStr = window.deviceGameId == 2 ? 'ios' : 'android'; // 当前页面类型1.安卓列表页，2.iOS列表页

        // console.log(device, deviceStr)
        if(!device || device === deviceStr){
            return false;
        }

        if(device === 'android'){
            window.location.href = window.deviceGameUrl;
        }else{
            window.location.href = window.deviceGameIosUrl;
        }
    }

    // 资讯详情
    exports.newsDetail = function() {
        exports.imgLazyLoad();
        exports.mobileGamesList();

        var filterImage = $('.news-detail .soft-list2 img').attr('src');
        exports.clickimg('.news-detail .text', filterImage)

        var id = $("#address").attr('data-id');
        var modelid = $("#address").attr('data-modelid');

        $.ajax({
            type: "GET",
            url: "/index.php?m=content&c=index&a=chain_ajax&id="+id,
            datatype: "json",
            success: function (res) {
                if(res.code==200){
                    var data = res.data;
                    var head = '';
                    var bottom = '';
                    if(data.bl_title){

                        head = '<div class="block-chain"><div class="chain-box"><img class="chain-img"src="'+data.bl_icon+'"><div class="chain-tit">'+data.bl_title+'</div><div class="chain-txt">'+data.bl_desc+'</div><div class="chain-btn"><a href="'+data.bl_gfurl+'">官方注册</a><a href="'+data.bl_appurl+'">APP&nbsp;下&nbsp;载</a></div></div></div>';

                        bottom = '<div class="block-chain2"><div class="chain-box"><img class="chain-img"src="'+data.bl_icon+'"><div class="chain-tit">'+data.bl_title+'</div><div class="chain-txt">'+data.bl_desc+'</div><div class="chain-btn"><a href="'+data.bl_gfurl+'">官方注册</a><a href="'+data.bl_appurl+'">APP&nbsp;下&nbsp;载</a></div></div><div class="chain-tip"><b>本站提醒：</b>'+data.bl_tips+'</div></div>';

                        $("#chain_head").replaceWith(head);
                        $("#chain_bottom").replaceWith(bottom);
                    }
                }
            }
        });
    }

    exports.clickimg = function(el, filterImage){
        // $('.page-footer').before('<script src="https://6188.129w.com/statics/mobile/js/app/photo-browser.js"></script>')
        require('photo-browser');
        console.log('photo-browser');
        $(el).each(function(){
            var $cont = $(this);
            var imgArr = [];
            $cont.find('img').each(function(i){
                var imgsrc = $(this).attr('src')
                if(imgsrc != filterImage){
                    imgArr.push(imgsrc);
                }
            })
            $cont.on('click', 'img', function(){
                var index = $(this).index();
                var imgsrc = $(this).attr('src');
                var index = getIndexBySrc(imgsrc);
                window.photoBrowser.init({
                    lazyLoading: true,
                    lazyLoadingInPrevNext: true,
                    initialSlide: index || 0,
                    maxZoom: 1.8,
                    photos: [
                        { url: imgArr.join(',') }
                    ]
                }).open();
            })
            function getIndexBySrc(imgsrc){
                var index = 0;
                $.each(imgArr, function(i, v){
                    if(imgsrc == v){
                        index = i;
                    }
                })
                return index;
            }
        })
    }

    // 手游排行榜
    exports.mobileGamesList = function(){
        $('#mobileGamesList').each(function(){
            var $list = $(this);
            $list.find('.tab-cell li').on('click', function(){
                $this = $(this);
                $this.addClass('on').siblings().removeClass('on');
                $list.find('.tab-content').removeClass('on').eq($this.index()).addClass('on');
            })

            // 点击查看更多
            $list.find('.btn-more').on('click', function(){
                var count = 4;
                var $cont = $(this).parents('.tab-content');
                var $item = $cont.find('.list-item');
                var index = $cont.find('.list-item.hide').index();

                $item.each(function(i){
                    if(i >= index && i < index + count){
                        $(this).removeClass('hide');
                    }
                })
                if(index+count >= $item.length){
                    $cont.find('.section-ft').hide();
                }
            });
        })
    }

    // 上拉加载
    exports.pullToLoading = function(params) {
        var $list = $('#listContent');
        var $listLoader = $('#listLoader');
        var $win = $(window);
        var body = document.body;
        var time = 0;

        // console.log($list.children().length, params.pageSize, params.pageSize || 10,params);
        if($list.children().length < (params.pageSize || 10)){
            $listLoader.html('没有更多了');
        }
        var loader = {
            type: params.type || 'get',
            ajaxUrl: params.url || '',
            ajaxData: params.data || {},
            ajaxing: false,
            callback: params.success,
            onScroll: function(){
                var that = loader;
                clearTimeout(time);
                time = setTimeout(function() {
                    var scrollHeight = body.scrollHeight;
                    var offsetTop = $win.height() + $win.scrollTop();

                    if (offsetTop + 500 > scrollHeight && !that.ajaxing && $list.children().length <= 30) {
                        that.onLoad();
                    }else{
                        $listLoader.html('没有更多了');
                    }
                }, 100)
            },
            onLoad: function(){
                var that = loader;
                var page = that.ajaxData.page;
                var size = that.ajaxData.pageSize || 10;
                that.ajaxing = true;

                var url = that.ajaxUrl + "/";
                for (i in that.ajaxData){
                    url +=  that.ajaxData[i] + "/";
                }


                $.ajax({
                    url: url,
                    type: that.type,
                    dataType: 'json',
                    success: function(res) {
                        var data = res.data;
                        if(typeof that.callback === 'function'){
                            that.callback(data);
                        }
                        else{
                            var html = that.getHTML(data);
                            $list.append(html);
                        }
                        console.log(data.length, size)
                        if(data.length < size){
                            $listLoader.html('没有更多了');
                        }else{
                            page++;
                            that.ajaxData.page = page;
                            that.ajaxing = false;
                        }
                    },
                    error: function(data){
                        console.log(data);
                        // ajaxing = false;
                    }
                })
            },
            getHTML: function(data){
                if(typeof params.getHTML === 'function'){
                    return params.getHTML(data);
                }

                var arr = [];
                $.each(data, function(i, v){
                    if(v.typed=='game' || v.typed=='pcrj') {
                        var frontmark = v.catname;
                        var tittle = v.title;
                    }else if(v.typed=='searched') {
                        var frontmark = v.catname;
                        var tittle = v.s_title;
                    }else {
                        var frontmark = '手机软件';
                        var tittle = v.title;
                    }
                    var btn_tips = v.catid==593?'开&nbsp;始':'查&nbsp;看';
                    var item = '<a class="list-item flex" href="'+ v.url +'">'+
                        '<div class="col"><img class="pic" src="'+ v.thumb +'" alt="'+ v.title +'"></div>'+
                        '<div class="con flex-item">'+
                            '<div class="tit">'+ tittle +'</div>'+
                            '<div class="star star'+ v.stars +'"></div>'+
                            '<div class="txt">'+
                                '<span class="attr">'+ frontmark +'</span>'+
                                '<span class="attr">大小:'+ v.filesize2 +'</span>'+
                            '</div>'+
                        '</div>'+
                        '<div class="col">'+
                            '<span class="btn btn-download">'+btn_tips+'</span>'+
                        '</div>'+
                    '</a>';
                    arr.push(item);
                })
                return arr.join('');
            },
            resetData: function(data, getHTML){
                $list.html('');
                $listLoader.html('<span class="preloader"></span>');
                this.ajaxing = false;
                this.ajaxData = $.extend({}, data);
                this.onLoad();
                if(typeof getHTML === 'function'){
                    this.getHTML = getHTML;
                }
            },
            init: function(){
                if($list.length && $listLoader.length){
                    $win.on('scroll', this.onScroll);
                }
            }
        }
        loader.init();

        return loader;
    }


    // 图片延迟加载
    exports.imgLazyLoad = function(options) {
        // 判断图片是否可加载
        var win = $(window),
            height = win.height(),
            distance = 200,
            timer = 0;

        $.loadable = function(el) {
            return win.scrollTop() + height + distance > $(el).offset().top;
        };

        $.lazyload = function() {
            $('.lazy').not('.loaded').each(function(i) {
                var $this = $(this), src = $this.attr('data-src');
                if ($.loadable(this)) {
                    !!src && $this.attr('src', src)
                    $this.addClass('loaded');
                }
            });
        };

        if(!(options && !options.auto)){
            //$.lazyload();
        }
        win.on('scroll', function() {
            // console.log('scroll');
            // clearTimeout(timer);
            // timer = setTimeout(function() {
                //$.lazyload();
            // }, 100);
        });
    }

    // 回到顶部
    exports.goToTop = function() {
        var $totop = $('#totop'),
            $win = $(window),
            timer = 0;

        // 回到顶部 - 隐藏/显示
        $win.scroll(function() {
            clearTimeout(timer)
            timer = setTimeout(function() {
                ($win.scrollTop() > 500) ? $totop.addClass('fadein'): $totop.removeClass('fadein');
            }, 100)
        });
        $totop.on('click', function() {
            $totop.removeClass('fadein');
            $win.scrollTop(0);
        })
    }

    // 详情 - 图片放大
    exports.imageView = function(){
        var maxHeight = $(window).height() * 0.75;
        $('.images-preview img').css('max-height', maxHeight+ 'px');
        $('.images-preview').each(function (i) {
            $(this).attr('id', 'imagesPreview'+i);
            TouchSlide({
                slideCell: "#" + $(this).attr('id'),
                titCell: ".hd ul", //开启自动分页 autoPage:true ，此时设置 titCell 为导航元素包裹层
                mainCell: ".bd ul",
                effect: "leftLoop",
                interTime: 5000,
                autoPage: true,//自动分页
                autoPlay: false, //自动播放
                endFn: function(e,i){
                    console.log(e,i);
                }
            });
        })
        var preventDefault = function(e){ e.preventDefault(); }
        $(document).on('click', '.images-preview', function () {
            $('body').removeClass('images-preview-cover');
            document.removeEventListener('touchmove', preventDefault, false);
        })
        $('#softFocus').on('click', 'li', function () {
            $('.images-preview .hd li').eq($(this).index()).click();
            $('body').addClass('images-preview-cover');
            document.addEventListener('touchmove', preventDefault, false);
        })
    }

    // 详情
    exports.detail = function() {
        TouchSlide({ slideCell: "#softFocus1" });
        TouchSlide({ slideCell: "#softFocus2" });
        exports.imageView();
        exports.imgLazyLoad();
    }
})

/* MacFk 手机端主题交互脚本 */
(function () {
    'use strict';

    /* 1. 移动菜单（mobileMenuButton → topnav） */
    var menuBtn = document.querySelector('.windows-homepage-module__n3qHYq__mobileMenuButton');
    var nav = document.querySelector('.windows-homepage-module__n3qHYq__topnav');
    if (menuBtn && nav) {
        menuBtn.addEventListener('click', function () {
            nav.classList.toggle('windows-homepage-module__n3qHYq__topnavOpen');
        });
    }

    /* 2. hero 主卡自动轮播（heroDots + heroPrimary） */
    var heroPrimary = document.querySelector('.windows-homepage-module__n3qHYq__heroPrimary');
    var dotsWrap = document.querySelector('.windows-homepage-module__n3qHYq__heroDots');
    var cards = [];
    if (heroPrimary && dotsWrap) {
        // 收集所有 hero 卡片（主卡 + 副卡），dots 数量对应
        var primaryWrap = document.querySelector('.windows-homepage-module__n3qHYq__heroPrimarySlot');
        cards = primaryWrap ? primaryWrap.querySelectorAll('.windows-homepage-module__n3qHYq__heroPrimaryLayer') : [];
    }
    // 简单处理：dots 存在则按 dot 数量轮播主卡背景（原站主卡仅一张时无轮播）
    var dotList = [];
    if (dotsWrap) dotList = dotsWrap.querySelectorAll('.windows-homepage-module__n3qHYq__heroDot');
    if (dotList.length > 1) {
        var cur = 0;
        // 若有多张主卡（heroPrimaryLayer）则轮播卡片本身
        var setSlide = function (i) {
            cur = i;
            for (var k = 0; k < dotList.length; k++) {
                dotList[k].classList.toggle('windows-homepage-module__n3qHYq__heroDotActive', k === i);
            }
            if (cards && cards.length > 1) {
                for (var c = 0; c < cards.length; c++) {
                    var show = c === i;
                    cards[c].style.display = show ? '' : 'none';
                    cards[c].classList.toggle('windows-homepage-module__n3qHYq__heroPrimaryCurrent', show);
                    cards[c].classList.toggle('windows-homepage-module__n3qHYq__heroPrimaryEnterNext', show);
                }
            }
        };
        for (var d = 0; d < dotList.length; d++) {
            (function (idx) {
                dotList[idx].addEventListener('click', function () { setSlide(idx); });
            })(d);
        }
        setInterval(function () { setSlide((cur + 1) % dotList.length); }, 5000);
    }

    /* 3. 排行面板翻页（rankingPanelPrev/Next + rankingPanelPage） */
    var panels = document.querySelectorAll('.windows-homepage-module__n3qHYq__rankingPanel');
    for (var p = 0; p < panels.length; p++) {
        (function (panel) {
            var viewport = panel.querySelector('.windows-homepage-module__n3qHYq__rankingPanelViewport');
            var track = panel.querySelector('.windows-homepage-module__n3qHYq__rankingPanelTrack');
            var pages = panel.querySelectorAll('.windows-homepage-module__n3qHYq__rankingPanelPage');
            var info = panel.querySelector('.windows-homepage-module__n3qHYq__rankingPanelPageInfo');
            var prevBtn = panel.querySelector('.windows-homepage-module__n3qHYq__rankingPanelNavPrev');
            var nextBtn = panel.querySelector('.windows-homepage-module__n3qHYq__rankingPanelNavNext');
            if (!track || pages.length < 2) return;
            var idx = 0;
            var total = pages.length;
            var render = function () {
                if (track) track.style.transform = 'translateX(-' + (idx * 100) + '%)';
                if (info) info.textContent = (idx + 1) + ' / ' + total;
                if (prevBtn) prevBtn.disabled = idx === 0;
                if (nextBtn) nextBtn.disabled = idx === total - 1;
            };
            if (prevBtn) prevBtn.addEventListener('click', function () { if (idx > 0) { idx--; render(); } });
            if (nextBtn) nextBtn.addEventListener('click', function () { if (idx < total - 1) { idx++; render(); } });
            render();
        })(panels[p]);
    }

    /* 4. 详情页分享按钮（下载按钮原为弹层，此处直接跳转下载链接） */
    var dlBtns = document.querySelectorAll('.page-module__CBPw8q__shareButton, .page-module__CBPw8q__compactShareButton');
    for (var b = 0; b < dlBtns.length; b++) {
        (function (btn) {
            btn.addEventListener('click', function () {
                var link = btn.getAttribute('data-download');
                if (link) { window.location.href = link; }
            });
        })(dlBtns[b]);
    }

    /* 5. 软件申请按钮（打开链接） */
    var requestBtns = document.querySelectorAll('.windows-homepage-module__n3qHYq__topnavLinkSoftwareRequest');
    for (var r = 0; r < requestBtns.length; r++) {
        (function (btn) {
            btn.addEventListener('click', function () {
                var href = btn.getAttribute('data-href');
                if (href) { window.location.href = href; }
            });
        })(requestBtns[r]);
    }

    /* 6. 详情页浮动下载条（compactActionCard：滚动 >360px 显示，对齐屏幕截图卡位置） */
    var compactCard = document.getElementById('compactActionCard');
    if (compactCard) {
        var stickyAnchor = document.querySelector('[data-sticky-anchor="true"]');
        var compactVisible = function () {
            compactCard.classList.toggle('page-module__CBPw8q__compactActionCardVisible', window.scrollY > 360);
        };
        var compactAlign = function () {
            if (!stickyAnchor) return;
            var rect = stickyAnchor.getBoundingClientRect();
            compactCard.style.left = rect.left + 'px';
            compactCard.style.width = rect.width + 'px';
        };
        compactVisible();
        compactAlign();
        window.addEventListener('scroll', compactVisible, { passive: true });
        window.addEventListener('scroll', compactAlign, { passive: true });
        window.addEventListener('resize', compactAlign);
        if (window.ResizeObserver) {
            var resizeObserver = new ResizeObserver(compactAlign);
            resizeObserver.observe(stickyAnchor);
        }
    }
})();

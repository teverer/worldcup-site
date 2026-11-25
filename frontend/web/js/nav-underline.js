/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261125
   模块:界面设计 —— 主导航当前项下划线：同一元素在导航项之间滑动。

   整页跳转时浏览器会重建 DOM，所以用 sessionStorage 记住“从哪一项离开”，
   新页面先把下划线放到旧项，再滑到新项；鼠标悬停时先滑到悬停项，
   离开导航再回到当前页。没有 JS / 窄屏时退回每个 a 自己的 box-shadow。 */
(function () {
    'use strict';

    var nav = document.querySelector('.site-nav');
    var list = nav ? nav.querySelector('[data-site-nav]') : null;
    var track = list ? list.querySelector('.nav-underline-track') : null;
    if (!nav || !list || !track) {
        return;
    }

    var FROM_KEY = 'worldcup:nav-from';
    var reduced = !!(window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function isDesktop() {
        return !window.matchMedia || window.matchMedia('(min-width: 768px)').matches;
    }

    function itemAt(index) {
        return list.querySelector('li[data-nav-index="' + index + '"]');
    }

    function linkAt(index) {
        var item = itemAt(index);
        return item ? item.querySelector('a') : null;
    }

    function activeIndex() {
        var item = list.querySelector('li[data-nav-index].active');
        if (!item) {
            return -1;
        }
        var index = parseInt(item.getAttribute('data-nav-index'), 10);
        return isNaN(index) ? -1 : index;
    }

    function place(index, animate) {
        var link = linkAt(index);
        if (!link || !isDesktop()) {
            track.style.opacity = '0';
            return;
        }

        var listRect = list.getBoundingClientRect();
        var linkRect = link.getBoundingClientRect();
        var left = linkRect.left - listRect.left;

        if (!animate || reduced) {
            track.style.transition = 'none';
        }
        track.style.width = linkRect.width + 'px';
        track.style.transform = 'translateX(' + left + 'px)';
        track.style.opacity = '1';

        if (!animate || reduced) {
            // 先让浏览器记住“无动画”的起点，再恢复 CSS 过渡。
            void track.offsetWidth;
            track.style.transition = '';
        }
    }

    function readFrom() {
        try {
            var raw = window.sessionStorage.getItem(FROM_KEY);
            if (raw === null) {
                return -1;
            }
            window.sessionStorage.removeItem(FROM_KEY);
            var value = parseInt(raw, 10);
            return isNaN(value) ? -1 : value;
        } catch (e) {
            return -1;
        }
    }

    function start() {
        var current = activeIndex();
        var from = readFrom();

        if (from >= 0 && from !== current && linkAt(from) && current >= 0) {
            place(from, false);
            nav.classList.add('nav-underline-ready');
            // 两帧后再移动：第一帧让旧位置落定，第二帧触发过渡。
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    place(current, true);
                });
            });
            return;
        }

        place(current, false);
        nav.classList.add('nav-underline-ready');
    }

    // 离开当前页时记住当前项；新页面据此把下划线从旧项滑过来。
    list.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a') : null;
        if (!link || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        var item = link.closest('li[data-nav-index]');
        if (!item) {
            return;
        }
        try {
            window.sessionStorage.setItem(FROM_KEY, String(activeIndex()));
        } catch (e) {
            // 隐私模式禁用 sessionStorage 时只是没有滑入动画，不影响导航。
        }
    });

    // 桌面端悬停预览：先滑到指针下的入口，离开导航再回当前页。
    list.addEventListener('pointerover', function (event) {
        if (!isDesktop()) {
            return;
        }
        var link = event.target.closest ? event.target.closest('a') : null;
        var item = link ? link.closest('li[data-nav-index]') : null;
        if (!item) {
            return;
        }
        place(parseInt(item.getAttribute('data-nav-index'), 10), true);
    });

    list.addEventListener('pointerleave', function () {
        place(activeIndex(), true);
    });

    var resizeTimer = null;
    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            place(activeIndex(), false);
        }, 120);
    });

    // 只有从 bfcache 返回时才需要重新定位；首次 pageshow 不能把滑入动画直接打断。
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            place(activeIndex(), false);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());

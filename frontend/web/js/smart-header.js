/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261124
   模块:界面设计 —— 滚动导航：向下滚动收起页头，向上滚动或回到顶部时滑回。
   页头高度同步到 --site-header-h，让届次 tab 等二级粘性条跟着让位。 */
(function () {
    'use strict';

    var header = document.querySelector('.site-header');
    if (!header) {
        return;
    }

    var root = document.documentElement;
    var reduced = !!(window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    var lastY = window.pageYOffset || 0;
    var headerHeight = header.offsetHeight || 65;
    var hidden = false;
    var ticking = false;
    var HIDE_AFTER = 120;
    var DELTA = 6;

    function setHeightVar() {
        root.style.setProperty('--site-header-h', (hidden ? 0 : headerHeight) + 'px');
    }

    function measure() {
        // transform 不影响布局高度，隐藏时也能量到真实页头高度。
        headerHeight = header.offsetHeight || 65;
        if (!hidden) {
            setHeightVar();
        }
    }

    function show() {
        if (!hidden) {
            return;
        }
        hidden = false;
        header.classList.remove('is-header-hidden');
        setHeightVar();
    }

    function hide() {
        if (hidden) {
            return;
        }
        hidden = true;
        header.classList.add('is-header-hidden');
        setHeightVar();
    }

    function menuIsOpen() {
        return !!document.querySelector('.site-nav .navbar-collapse.in');
    }

    function onScroll() {
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        var dy = y - lastY;

        if (Math.abs(dy) < DELTA) {
            // 慢慢滚动时累积差值；到阈值再判断方向。
            header.classList.toggle('is-stuck', y > 8);
            return;
        }

        lastY = y;
        header.classList.toggle('is-stuck', y > 8);

        if (y <= 24 || dy < 0 || menuIsOpen()) {
            show();
        } else if (dy > 0 && y > HIDE_AFTER) {
            hide();
        }
    }

    function schedule() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            ticking = false;
            onScroll();
        });
    }

    // 减少动效：页头保持常驻，只量高度供二级粘性条使用。
    measure();
    if (reduced) {
        setHeightVar();
        return;
    }

    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', function () {
        measure();
        schedule();
    });

    // 移动端菜单展开会改变页头高度；页头被聚焦时保持可见。
    header.addEventListener('focusin', show);
    if (typeof window.ResizeObserver === 'function') {
        new ResizeObserver(function () {
            measure();
            if (menuIsOpen()) {
                show();
            }
        }).observe(header);
    }

    // 锚点/页内 tab 点击时先把页头叫回来，避免落点被盖住。
    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }
        if (target.closest('.td-tabs a, .stats-history-nav a, .chart-data summary')) {
            show();
        }
    });
}());

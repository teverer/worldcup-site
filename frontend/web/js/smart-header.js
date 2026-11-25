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
    /* 页内锚点跳转也会触发 scroll:跳完先别收页头,否则页头让开后
       届次 tab 还没到吸顶位置,两者之间会露出一段页面。 */
    var suppressHide = false;
    var suppressTimer = null;

    function holdHeader(ms) {
        suppressHide = true;
        window.clearTimeout(suppressTimer);
        suppressTimer = window.setTimeout(function () {
            suppressHide = false;
        }, ms);
    }

    function releaseHeader() {
        suppressHide = false;
        window.clearTimeout(suppressTimer);
    }

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
        if (hidden || suppressHide) {
            return;
        }
        hidden = true;
        header.classList.add('is-header-hidden');
        setHeightVar();
    }

    function menuIsOpen() {
        return !!document.querySelector('.site-nav .navbar-collapse.in');
    }

    var menuToggle = document.querySelector('.site-nav .navbar-toggle');
    var menuToggleSr = menuToggle ? menuToggle.querySelector('.sr-only') : null;

    function setMenuLabel(open) {
        var label = open ? '收起导航' : '展开导航';
        if (menuToggleSr) {
            menuToggleSr.textContent = label;
        }
        if (menuToggle) {
            menuToggle.setAttribute('aria-label', label);
        }
    }

    function closeMenu() {
        var nav = document.querySelector('.site-nav .navbar-collapse.in');
        if (!nav) {
            return;
        }
        if (window.jQuery && window.jQuery.fn.collapse) {
            window.jQuery(nav).collapse('hide');
        } else {
            nav.classList.remove('in');
        }
        setMenuLabel(false);
        if (menuToggle) {
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.focus();
        }
    }

    // Bootstrap 3 用 jQuery 触发 collapse 事件，原生 document 监听不到；
    // 点击后按实际开合状态更新按钮可访问名（展开导航 / 收起导航）。
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            window.setTimeout(function () {
                setMenuLabel(menuIsOpen());
            }, 380);
        });
    }

    // 非 Bootstrap 路径（或未来换成原生事件）也同步。
    document.addEventListener('shown.bs.collapse', function (event) {
        if (event.target && event.target.id === 'site-nav') {
            setMenuLabel(true);
        }
    });
    document.addEventListener('hidden.bs.collapse', function (event) {
        if (event.target && event.target.id === 'site-nav') {
            setMenuLabel(false);
        }
    });

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

    // 减少动效：不注册“下滑收起”，页头保持常驻；菜单、焦点、高度测量照常。
    measure();
    if (reduced) {
        setHeightVar();
        // 不收起页头，但仍要在离开顶部后给它实底，避免内容从透明页头下透出。
        window.addEventListener('scroll', function () {
            header.classList.toggle('is-stuck', (window.pageYOffset || 0) > 8);
        }, { passive: true });
    } else {
        window.addEventListener('scroll', schedule, { passive: true });
    }
    window.addEventListener('resize', function () {
        measure();
        if (!reduced) {
            schedule();
        }
    });

    // 用户真正开始滚动时恢复“下滑收起”，锚点跳转本身不算。
    ['wheel', 'touchstart'].forEach(function (name) {
        window.addEventListener(name, releaseHeader, { passive: true });
    });
    window.addEventListener('keydown', function (event) {
        var key = event.key;
        if (key === 'Escape' && menuIsOpen()) {
            closeMenu();
            return;
        }
        if (key === 'PageDown' || key === 'PageUp' || key === 'ArrowDown'
            || key === 'ArrowUp' || key === 'Home' || key === 'End' || key === ' ') {
            releaseHeader();
        }
    });

    // 带 #hash 直接打开时也保留页头,让读者先看到“这是哪一届”。
    if (window.location.hash) {
        holdHeader(2000);
    }

    // 浏览器前进 / 后退到某个锚点时同样先把页头叫回来。
    window.addEventListener('hashchange', function () {
        show();
        holdHeader(1600);
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
            holdHeader(1600);
        }
    });
}());

/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261125
   模块:球队与球员 —— 球员姓名中文名 / 原名切换。

   参考成熟站点的偏好开关：点击只改当前页文字与 cookie，不发请求、不刷新、
   不改变滚动位置；下次打开页面由 PHP 按同一 cookie 直接渲染。
   没有 JS 时，_name_lang.php 里的链接仍会走 PreferenceController 回跳。 */
(function () {
    'use strict';

    var COOKIE = 'wc_player_name_v2';
    var root = document.documentElement;

    function currentMode() {
        return /(?:^|;\s*)wc_player_name_v2=zh(?:;|$)/.test(document.cookie) ? 'zh' : 'orig';
    }

    function displayText(el, mode) {
        var original = el.getAttribute('data-name-orig');
        var chinese = el.getAttribute('data-name-zh');
        if (!chinese) {
            return original;
        }
        if (el.getAttribute('data-name-always-zh') === '1') {
            return chinese;
        }
        return mode === 'zh' ? chinese : original;
    }

    function updateNames() {
        var mode = currentMode();

        Array.prototype.forEach.call(document.querySelectorAll('[data-name-orig]'), function (el) {
            var text = displayText(el, mode);
            if (el.textContent !== text) {
                el.textContent = text;
            }
        });

        // 球员详情：面包屑与浏览器标题也跟随，避免 h1 已换名、旁边仍是旧名。
        var page = document.querySelector('[data-page-name-orig]');
        if (page) {
            var pageOrig = page.getAttribute('data-page-name-orig') || '';
            var pageZh = page.getAttribute('data-page-name-zh') || '';
            var pageName = (pageZh && mode === 'zh') ? pageZh : pageOrig;
            var crumb = document.querySelector('.breadcrumb > li.active');
            if (crumb && (crumb.textContent === pageOrig || crumb.textContent === pageZh)) {
                crumb.textContent = pageName;
            }
            if (document.title) {
                if (pageOrig && pageOrig !== pageName) {
                    document.title = document.title.replace(pageOrig, pageName);
                }
                if (pageZh && pageZh !== pageName) {
                    document.title = document.title.replace(pageZh, pageName);
                }
            }
        }

        Array.prototype.forEach.call(document.querySelectorAll('[data-name-lang]'), function (opt) {
            var active = opt.getAttribute('data-name-lang') === mode;
            opt.classList.toggle('is-active', active);
            if (active) {
                opt.setAttribute('aria-current', 'true');
            } else {
                opt.removeAttribute('aria-current');
            }
        });

        root.setAttribute('data-name-lang-mode', mode);
    }

    function setMode(mode) {
        var next = mode === 'zh' ? 'zh' : 'orig';
        document.cookie = COOKIE + '=' + next
            + '; path=/; max-age=31536000; samesite=Lax';
        updateNames();
        window.dispatchEvent(new CustomEvent('worldcup:name-lang-changed', {
            detail: {mode: next}
        }));
    }

    document.addEventListener('click', function (event) {
        var opt = event.target.closest ? event.target.closest('[data-name-lang]') : null;
        if (!opt || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        if (opt.getAttribute('data-name-lang') === currentMode()) {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        setMode(opt.getAttribute('data-name-lang'));
    });

    // 从缓存返回（bfcache）时，服务端 HTML 可能还是切换前的文字，按 cookie 再同步一次。
    window.addEventListener('pageshow', updateNames);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateNames);
    } else {
        updateNames();
    }
}());

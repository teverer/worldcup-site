/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261125
   模块:球队与球员 —— 球队详情「参赛球员」按届次 / 翻页的无刷新切换。

   点击年份或分页时只替换 [data-roster-root] 这一块，更新 URL 和当前项，
   不整页刷新、不回到顶部；失败才回退到普通跳转。
   参考现代站点对同页筛选的 progressive enhancement 做法。 */
(function () {
    'use strict';

    var root = document.querySelector('[data-roster-root]');
    if (!root) {
        return;
    }

    var busy = false;

    function isLoading() {
        return busy;
    }

    function setBusy(value) {
        busy = value;
        var current = document.querySelector('[data-roster-root]');
        if (current) {
            if (value) {
                current.setAttribute('aria-busy', 'true');
            } else {
                current.removeAttribute('aria-busy');
            }
        }
    }

    function focusAfterReplace(next, href) {
        var target = null;
        var links = next.querySelectorAll('a[data-roster-nav]');
        Array.prototype.some.call(links, function (link) {
            if (link.getAttribute('href') === href || link.href === href) {
                target = link;
                return true;
            }
            return false;
        });

        if (!target) {
            target = next.querySelector('.td-edition.is-active')
                || next.querySelector('a[data-roster-nav]');
        }
        if (target && typeof target.focus === 'function') {
            target.focus({preventScroll: true});
        }
    }

    function announce(next) {
        var status = next.querySelector('[data-roster-status]');
        if (!status) {
            return;
        }
        var active = next.querySelector('.td-edition.is-active');
        status.textContent = active
            ? active.textContent.trim() + ' 名单已更新'
            : '名单已更新';
    }

    function load(url, push, focusHref) {
        if (busy) {
            return;
        }
        if (!window.fetch || !window.DOMParser) {
            window.location.href = url;
            return;
        }
        setBusy(true);

        window.fetch(url, {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        }).then(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var next = doc.querySelector('[data-roster-root]');
            var current = document.querySelector('[data-roster-root]');
            if (!next || !current) {
                throw new Error('roster not found');
            }

            current.replaceWith(next);
            if (push) {
                window.history.pushState({roster: true}, '', url);
            }
            focusAfterReplace(next, focusHref);
            announce(next);
        }).catch(function () {
            // 网络或结构异常时老实整页跳转，至少不丢功能。
            window.location.href = url;
        }).then(function () {
            setBusy(false);
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a[data-roster-nav]') : null;
        if (!link || !link.closest('[data-roster-root]')) {
            return;
        }
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        if (link.classList.contains('is-active')) {
            event.preventDefault();
            return;
        }
        if (isLoading()) {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        load(link.href, true, link.getAttribute('href'));
    });

    window.addEventListener('popstate', function () {
        if (document.querySelector('[data-roster-root]')) {
            load(window.location.href, false, null);
        }
    });
}());

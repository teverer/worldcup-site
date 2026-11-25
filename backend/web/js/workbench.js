/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261123
   模块:界面设计 —— 后台工作间导轨开关

   窄屏时把左侧导轨收成抽屉;桌面端始终展开,不参与交互。 */
(function () {
    'use strict';

    var body = document.body;
    var toggle = document.querySelector('[data-rail-toggle]');
    var rail = document.getElementById('wb-rail');

    if (!toggle || !rail) {
        return;
    }

    function isOpen() {
        return body.classList.contains('wb-rail-open');
    }

    function setOpen(open) {
        body.classList.toggle('wb-rail-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function () {
        setOpen(!isOpen());
    });

    document.querySelectorAll('[data-rail-close]').forEach(function (node) {
        node.addEventListener('click', function () {
            setOpen(false);
        });
    });

    rail.addEventListener('click', function (event) {
        if (event.target.closest('.wb-item') && window.matchMedia('(max-width: 900px)').matches) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen()) {
            setOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900 && isOpen()) {
            setOpen(false);
        }
    });

    // 分组折叠：借 AdminLTE 2 treeview 的行为（当前组默认展开，状态记在本地）。
    // 不用 jQuery slide，避免和导轨抽屉的 transform 抢动画。
    var storageKey = 'wcAdminGroup:';
    var groups = document.querySelectorAll('[data-wb-group]');

    Array.prototype.forEach.call(groups, function (group) {
        var toggle = group.querySelector('.wb-group-toggle');
        var items = group.querySelector('.wb-group-items');
        if (!toggle || !items) {
            return;
        }

        var key = storageKey + (group.getAttribute('data-wb-group') || '');
        var stored = null;
        try {
            stored = window.localStorage.getItem(key);
        } catch (e) {
            stored = null;
        }

        // 当前路由所在组必须展开；其余组沿用上次的选择。
        var open = group.classList.contains('is-current') || stored !== 'closed';

        function apply(value) {
            open = value;
            group.classList.toggle('is-collapsed', !value);
            toggle.setAttribute('aria-expanded', value ? 'true' : 'false');
            items.hidden = !value;
        }

        apply(open);

        toggle.addEventListener('click', function () {
            apply(!open);
            try {
                window.localStorage.setItem(key, open ? 'open' : 'closed');
            } catch (e) {
                // 隐私模式下 localStorage 可能不可写，折叠本身仍然生效。
            }
        });
    });
}());

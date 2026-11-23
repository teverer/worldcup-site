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
}());

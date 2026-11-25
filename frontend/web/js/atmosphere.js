/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261123
   模块:界面设计 —— 夜场氛围交互

   只做三件事,不接管页面逻辑:
   1. 记分牌上的光跟随指针;
   2. 悬停某一届时,整片夜场的色温切成那一届的年代色;
   3. prefers-reduced-motion 下全部跳过。 */
(function () {
    'use strict';

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    // 每届的年代光色:1986 琥珀、1990 冷青、1994 暖红、1998 海蓝、
    // 2002 青绿、2006 金、2010 琥珀、2014 冷青、2018 暖褐、2022 海蓝、2026 金。
    var ERA_GLOW = {
        '1986': '216, 170, 112',
        '1990': '128, 176, 178',
        '1994': '206, 122, 96',
        '1998': '110, 158, 198',
        '2002': '122, 182, 156',
        '2006': '206, 172, 108',
        '2010': '220, 150, 88',
        '2014': '112, 188, 166',
        '2018': '186, 132, 96',
        '2022': '116, 166, 198',
        '2026': '224, 180, 87'
    };

    var rootWall = document.querySelector('.root-wall');
    var atmo = document.querySelector('.atmo');

    if (rootWall && atmo) {
        rootWall.addEventListener('pointermove', function (event) {
            var board = event.target.closest('.wall-board');
            if (!board) {
                return;
            }
            var rect = board.getBoundingClientRect();
            board.style.setProperty('--mx', ((event.clientX - rect.left) / rect.width * 100) + '%');
            board.style.setProperty('--my', ((event.clientY - rect.top) / rect.height * 100) + '%');
        });

        rootWall.addEventListener('pointerover', function (event) {
            var board = event.target.closest('.wall-board');
            if (!board) {
                return;
            }
            var yearNode = board.querySelector('.board-year');
            var glow = yearNode ? ERA_GLOW[yearNode.textContent.trim()] : null;
            if (glow) {
                atmo.style.setProperty('--era-glow', glow);
            }
        });

        rootWall.addEventListener('pointerleave', function () {
            atmo.style.removeProperty('--era-glow');
        });
    }

    // 只有一处低调的键盘等价:焦点进入某一届时,同样切光。
    document.querySelectorAll('.wall-board').forEach(function (board) {
        board.addEventListener('focus', function () {
            var yearNode = board.querySelector('.board-year');
            var glow = yearNode ? ERA_GLOW[yearNode.textContent.trim()] : null;
            if (glow && atmo) {
                atmo.style.setProperty('--era-glow', glow);
            }
        });
        board.addEventListener('blur', function () {
            if (atmo) {
                atmo.style.removeProperty('--era-glow');
            }
        });
    });
}());

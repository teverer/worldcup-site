/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 淘汰赛晋级图渐进增强
 *
 * 仅做交互增强:悬停 / 键盘聚焦时把节点内隐藏的详情模板渲染为
 * 挂在 body 上的 role="tooltip" 浮层(避免被横向滚动容器裁切),
 * 触屏通过「详情」按钮点击展开,Esc 或点击外部关闭。
 * 不依赖任何 JS 库;脚本失效时比分与胜者仍完整可见。
 */
(function () {
    'use strict';

    function init() {
        var root = document.querySelector('[data-bracket]');
        if (!root) {
            return;
        }

        var viewport = root.querySelector('[data-bracket-viewport]');
        var nodes = Array.prototype.slice.call(root.querySelectorAll('[data-bracket-match]'));
        if (!nodes.length) {
            return;
        }

        root.classList.add('is-enhanced');

        var tooltip = document.createElement('div');
        tooltip.className = 'bracket-tooltip';
        tooltip.id = 'bracket-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.hidden = true;
        document.body.appendChild(tooltip);

        var current = null;
        var pinned = false;
        var frame = 0;
        var showTimer = 0;
        var hoverCapable = window.matchMedia
            ? window.matchMedia('(hover: hover) and (pointer: fine)').matches
            : true;

        function getParts(node) {
            return {
                link: node.querySelector('.bracket-match__link'),
                button: node.querySelector('.bracket-match__more'),
                template: node.querySelector('.bracket-tip')
            };
        }

        function setDescribed(node, on) {
            var parts = getParts(node);
            [parts.link, parts.button].forEach(function (element) {
                if (!element) {
                    return;
                }
                if (on) {
                    element.setAttribute('aria-describedby', 'bracket-tooltip');
                } else {
                    element.removeAttribute('aria-describedby');
                }
            });
        }

        // 固定定位 + 视口钳制:优先放在节点上方,空间不足时翻到下方,
        // 并按可用空间收缩高度,保证卡片不越出视口、不压住所描述的节点。
        function place(node) {
            var rect = node.getBoundingClientRect();
            var margin = 10;
            var cap = 420;
            var width = tooltip.offsetWidth;

            tooltip.style.maxHeight = '';
            // +2px 余量:避免短卡片因亚像素取整出现无意义的滚动条
            var natural = Math.min(tooltip.scrollHeight + 2, cap);
            var aboveSpace = rect.top - margin * 2;
            var belowSpace = window.innerHeight - rect.bottom - margin * 2;
            var above = aboveSpace >= natural || aboveSpace >= belowSpace;
            var available = Math.max(160, above ? aboveSpace : belowSpace);
            var height = Math.min(natural, available);

            tooltip.style.maxHeight = height + 'px';
            height = tooltip.offsetHeight;

            var top = above
                ? Math.max(margin, rect.top - height - margin)
                : Math.min(rect.bottom + margin, window.innerHeight - height - margin);

            var center = rect.left + rect.width / 2;
            var left = Math.round(center - width / 2);
            left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));

            tooltip.style.left = left + 'px';
            tooltip.style.top = Math.round(top) + 'px';
            tooltip.classList.toggle('bracket-tooltip--below', !above);
        }

        function close() {
            if (showTimer) {
                window.clearTimeout(showTimer);
                showTimer = 0;
            }
            if (!current) {
                tooltip.hidden = true;
                return;
            }

            var node = current;
            var button = getParts(node).button;
            setDescribed(node, false);
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
            node.classList.remove('is-open');

            current = null;
            pinned = false;
            tooltip.hidden = true;
            tooltip.classList.remove('is-pinned');
            tooltip.innerHTML = '';
        }

        function open(node, pin) {
            var parts = getParts(node);
            if (!parts.template) {
                return;
            }
            if (showTimer) {
                window.clearTimeout(showTimer);
                showTimer = 0;
            }

            if (current && current !== node) {
                close();
            }

            current = node;
            pinned = Boolean(pin) || pinned;

            tooltip.innerHTML = parts.template.innerHTML;
            tooltip.hidden = false;
            tooltip.classList.toggle('is-pinned', pinned);
            node.classList.add('is-open');
            setDescribed(node, true);
            if (parts.button) {
                parts.button.setAttribute('aria-expanded', pinned ? 'true' : 'false');
            }
            place(node);
        }

        function reposition() {
            if (!current) {
                return;
            }
            // 保持跟随:页面平滑滚动(含 focus 自动滚动)期间只更新位置,
            // 不因节点暂时离开视口而关闭,避免键盘聚焦时浮层立即消失。
            place(current);
        }

        function onScroll() {
            if (!current || frame) {
                return;
            }
            frame = window.requestAnimationFrame(function () {
                frame = 0;
                reposition();
            });
        }

        function updateScrollable() {
            if (!viewport) {
                return;
            }
            var scrollable = viewport.scrollWidth > viewport.clientWidth + 2;
            root.classList.toggle('is-scrollable', scrollable);
        }

        // 用实测的比赛卡中心精修汇流线位置;CSS 百分比是它的无脚本回退。
        // 只修正同一列内两场本轮的 y 坐标,不改变配对与冠军路径语义。
        function layoutLinks() {
            var links = root.querySelectorAll('.bracket-link');
            if (!links.length) {
                return;
            }
            Array.prototype.forEach.call(links, function (link) {
                var round = link.closest('.bracket-round');
                var pairIndex = parseInt(link.getAttribute('data-pair'), 10);
                if (!round || isNaN(pairIndex)) {
                    return;
                }
                var matches = round.querySelectorAll('.bracket-round__body > .bracket-match');
                var topMatch = matches[pairIndex * 2];
                var bottomMatch = matches[pairIndex * 2 + 1];
                if (!topMatch || !bottomMatch) {
                    return;
                }

                var overlay = link.parentNode;
                var baseTop = overlay.getBoundingClientRect().top;
                var topRect = topMatch.getBoundingClientRect();
                var bottomRect = bottomMatch.getBoundingClientRect();
                var y1 = topRect.top + topRect.height / 2 - baseTop;
                var y2 = bottomRect.top + bottomRect.height / 2 - baseTop;

                link.style.setProperty('--y1', y1 + 'px');
                link.style.setProperty('--y2', y2 + 'px');
                link.style.setProperty('--ymid', (y1 + y2) / 2 + 'px');
                link.style.setProperty('--half', Math.abs(y2 - y1) / 2 + 'px');
            });
        }

        nodes.forEach(function (node) {
            var parts = getParts(node);

            if (hoverCapable) {
                // 轻微延迟,避免指针扫过整列时浮层连续闪烁;键盘聚焦不延迟。
                node.addEventListener('mouseenter', function () {
                    if (pinned || showTimer) {
                        return;
                    }
                    showTimer = window.setTimeout(function () {
                        showTimer = 0;
                        if (!pinned) {
                            open(node, false);
                        }
                    }, 120);
                });
                node.addEventListener('mouseleave', function () {
                    if (showTimer) {
                        window.clearTimeout(showTimer);
                        showTimer = 0;
                    }
                    if (!pinned && current === node) {
                        close();
                    }
                });
            }

            node.addEventListener('focusin', function () {
                if (current !== node) {
                    open(node, false);
                }
            });

            node.addEventListener('focusout', function (event) {
                if (event.relatedTarget && node.contains(event.relatedTarget)) {
                    return;
                }
                if (!pinned) {
                    close();
                }
            });

            if (parts.button) {
                parts.button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (current === node && pinned) {
                        close();
                    } else {
                        open(node, true);
                    }
                });
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && current) {
                close();
            }
        });

        document.addEventListener('click', function (event) {
            if (!current) {
                return;
            }
            if (current.contains(event.target) || tooltip.contains(event.target)) {
                return;
            }
            close();
        });

        window.addEventListener('scroll', onScroll, true);
        window.addEventListener('resize', function () {
            updateScrollable();
            layoutLinks();
            if (current) {
                place(current);
            }
        });

        if (typeof window.ResizeObserver !== 'undefined' && viewport) {
            new window.ResizeObserver(function () {
                updateScrollable();
                layoutLinks();
            }).observe(viewport);
        }

        updateScrollable();
        window.requestAnimationFrame(layoutLinks);
        window.setTimeout(updateScrollable, 300);
        window.setTimeout(layoutLinks, 300);
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(layoutLinks);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());

/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 留言簿的输入、折叠定位与空态起点。
 *
 * 只做减法：
 * - 输入框随内容长高，接近 5000 字时才提示剩余字数；
 * - Ctrl / Cmd + Enter 走原生提交，保留 required / maxlength 校验；
 * - 空态的三个起点直接填进输入框；
 * - 锚点落在折叠回复里时，先展开再定位。
 */
(function () {
    'use strict';

    if (window.worldcupLedgerReady) {
        return;
    }
    window.worldcupLedgerReady = true;

    var maxLength = 5000;

    function setupInput(input) {
        var hint = input.parentElement ? input.parentElement.querySelector('.composer-hint') : null;
        var baseHint = hint ? hint.textContent.replace(/\s+/g, ' ').trim() : '';
        var grow = function () {
            // 回复框是紧凑输入卡，交给 CSS min-height / 原生 resize 控制；
            // 只有主输入框随内容长高，避免窄宽测量把回复框撑到 260px。
            if (input.closest('.ledger-reply-box')) {
                input.style.height = '';
                return;
            }
            // 折叠区里的输入框在页面加载时不可见，量不到真实高度；等它展开后再长高。
            if (!input.getClientRects().length) {
                return;
            }
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 260) + 'px';
        };
        var updateHint = function () {
            if (!hint) {
                return;
            }
            var left = maxLength - input.value.length;
            hint.textContent = left <= 500 ? '还可以写 ' + left + ' 字' : baseHint;
        };

        input.addEventListener('input', function () {
            grow();
            updateHint();
        });
        input.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                if (!input.form) {
                    return;
                }
                if (input.form.requestSubmit) {
                    input.form.requestSubmit();
                } else {
                    input.form.submit();
                }
            }
        });

        grow();
        updateHint();
    }

    function initLedger(ledger) {
        var mainInput = ledger.querySelector('.ledger-compose-card [data-ledger-input], .ledger-compose [data-ledger-input]');

        Array.prototype.forEach.call(ledger.querySelectorAll('[data-ledger-input]'), setupInput);

        // 留言区有内滚时，底部给一条渐隐提示；滑到底自动消失。
        Array.prototype.forEach.call(ledger.querySelectorAll('.ledger-list-scroll'), function (scroller) {
            var panel = scroller.closest('.ledger-discussion');
            if (!panel) {
                return;
            }
            var update = function () {
                var overflowing = scroller.scrollHeight > scroller.clientHeight + 4;
                var atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 4;
                panel.classList.toggle('is-scrollable', overflowing && !atBottom);
            };
            scroller.addEventListener('scroll', update, { passive: true });

            var hint = panel.querySelector('.ledger-scroll-hint');
            if (hint) {
                hint.addEventListener('click', function () {
                    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    scroller.scrollBy({
                        top: Math.max(180, Math.round(scroller.clientHeight * 0.85)),
                        behavior: reduce ? 'auto' : 'smooth'
                    });
                });
            }

            update();
        });

        Array.prototype.forEach.call(ledger.querySelectorAll('[data-ledger-starter]'), function (button) {
            button.addEventListener('click', function () {
                if (!mainInput) {
                    return;
                }
                mainInput.value = button.getAttribute('data-ledger-starter') || '';
                mainInput.focus();
                mainInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    }

    // 点“回复”展开后，把焦点放到回复框；程序化展开时不抢焦点。
    document.addEventListener('click', function (event) {
        var summary = event.target.closest ? event.target.closest('.ledger-reply-box > summary') : null;
        if (!summary) {
            return;
        }
        var box = summary.parentElement;
        if (box.open) {
            return;
        }
        window.setTimeout(function () {
            var input = box.querySelector('[data-ledger-input]');
            if (input) {
                input.focus({ preventScroll: true });
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }, 0);
    }, true);

    // 直达 #comment-N 时，如果它在折叠区里，先展开所有祖先 details 再滚动。
    function openTarget() {
        var target = document.querySelector(':target');
        if (!target) {
            return;
        }
        var details = target.closest('details');
        var changed = false;
        while (details) {
            if (!details.open) {
                details.open = true;
                changed = true;
            }
            details = details.parentElement ? details.parentElement.closest('details') : null;
        }
        if (changed) {
            window.setTimeout(function () {
                target.scrollIntoView({ block: 'start' });
            }, 0);
        }
    }

    window.addEventListener('hashchange', openTarget);
    openTarget();

    // 留言簿组件；首页收场区用 [data-ledger-scope] 复用同一套输入行为。
    Array.prototype.forEach.call(
        document.querySelectorAll('.ledger, [data-ledger-scope]'),
        initLedger
    );
})();

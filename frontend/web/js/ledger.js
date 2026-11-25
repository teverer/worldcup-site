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
        if (input.__ledgerReady) {
            return;
        }
        input.__ledgerReady = true;
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

    function showFormError(form, message) {
        var error = form.__ledgerError;
        if (!error || !error.parentNode) {
            error = document.createElement('p');
            error.className = 'ledger-form-error';
            error.setAttribute('role', 'alert');
            form.parentNode.insertBefore(error, form.nextSibling);
            form.__ledgerError = error;
        }
        error.textContent = message;
    }

    function clearFormError(form) {
        if (form.__ledgerError && form.__ledgerError.parentNode) {
            form.__ledgerError.parentNode.removeChild(form.__ledgerError);
        }
        form.__ledgerError = null;
    }

    function updateCount(ledger, total, participants) {
        var head = ledger.querySelector('.ledger-head');
        if (!head) {
            return;
        }
        var count = head.querySelector('.ledger-count');
        if (!count) {
            count = document.createElement('span');
            count.className = 'ledger-count';
            head.appendChild(count);
        }
        count.textContent = '';
        var totalNode = document.createElement('b');
        totalNode.textContent = String(total);
        count.appendChild(totalNode);
        count.appendChild(document.createTextNode(' 条留言'));
        if (participants > 0) {
            count.appendChild(document.createTextNode(' · '));
            var people = document.createElement('b');
            people.textContent = String(participants);
            count.appendChild(people);
            count.appendChild(document.createTextNode(' 人'));
        }

        // 届次页顶部「讨论 N」也是同一个总数；发表后一起更新，不让两处数字打架。
        var tab = document.querySelector('.td-tabs-comments');
        if (tab) {
            var tabCount = tab.querySelector('span');
            if (total > 0) {
                if (!tabCount) {
                    tabCount = document.createElement('span');
                    tab.appendChild(document.createTextNode(' '));
                    tab.appendChild(tabCount);
                }
                tabCount.textContent = String(total);
            } else if (tabCount) {
                tabCount.parentNode.removeChild(tabCount);
            }
        }
    }

    // 不整页刷新：新留言由服务端渲染成同一条记录，插到列表最上方。
    function insertComment(ledger, data) {
        var discussion = ledger.querySelector('.ledger-discussion');
        if (!discussion || !data || !data.html) {
            return;
        }

        var empty = discussion.querySelector('.ledger-empty');
        if (empty) {
            empty.parentNode.removeChild(empty);
        }

        var scroller = discussion.querySelector('.ledger-list-scroll');
        if (!scroller) {
            scroller = document.createElement('div');
            scroller.className = 'ledger-list-scroll';
            scroller.tabIndex = 0;
            scroller.setAttribute('role', 'region');
            scroller.setAttribute('aria-label', '留言列表，可滚动');
            var list = document.createElement('div');
            list.className = 'ledger-list';
            scroller.appendChild(list);
            discussion.appendChild(scroller);
        }

        var list = scroller.querySelector('.ledger-list');
        if (!list) {
            return;
        }
        list.insertAdjacentHTML('afterbegin', data.html);
        scroller.scrollTop = 0;

        var first = list.firstElementChild;
        if (first) {
            first.classList.add('is-new');
            window.setTimeout(function () {
                first.classList.remove('is-new');
            }, 1800);
        }
        updateCount(ledger, data.total, data.participants);
        // 新记录里的“回复”输入框也要接上长高 / 提示；initLedger 内部都有重复绑定保护。
        initLedger(ledger);
    }

    // 回复插到所属主留言的回复列表末尾；有折叠的回复就先展开，读得到自己刚发的。
    function insertReply(ledger, data, form) {
        var root = document.getElementById('comment-' + data.rootId);
        if (!root || !data.html) {
            return;
        }
        var body = root.querySelector('.ledger-body') || root;
        var replies = body.querySelector('.ledger-replies');
        if (!replies) {
            replies = document.createElement('div');
            replies.className = 'ledger-replies';
            body.appendChild(replies);
        }

        var more = replies.querySelector('.ledger-more');
        if (more && !more.open) {
            more.open = true;
        }
        var host = more || replies;
        host.insertAdjacentHTML('beforeend', data.html);

        var added = document.getElementById('comment-' + data.id);
        if (added) {
            added.classList.add('is-new');
            window.setTimeout(function () {
                added.classList.remove('is-new');
            }, 1800);
        }

        if (more) {
            var label = more.querySelector('.ledger-more-open');
            if (label) {
                label.textContent = '查看全部 ' + data.replyCount + ' 条回复';
            }
        }

        var box = form.closest('.ledger-reply-box');
        if (box) {
            box.open = false;
        }
        updateCount(ledger, data.total, data.participants);
        // 新回复自己也能继续被回复；接上它的输入框和 AJAX 表单。
        initLedger(ledger);
    }

    function setupForm(ledger, form) {
        if (form.__ledgerAjax || !window.fetch || !window.FormData) {
            return;
        }
        form.__ledgerAjax = true;

        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }
            event.preventDefault();
            if (form.__ledgerSending) {
                return;
            }
            form.__ledgerSending = true;

            var button = form.querySelector('[type="submit"]');
            clearFormError(form);
            if (button) {
                button.disabled = true;
                button.classList.add('is-busy');
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () {
                    return {ok: false, error: '服务器返回了无法识别的内容，请刷新后重试。'};
                }).then(function (data) {
                    return {ok: response.ok && data.ok, data: data};
                });
            }).then(function (result) {
                if (!result.ok) {
                    showFormError(form, result.data.error || '留言没有发出去，请稍后重试。');
                    return;
                }
                if (result.data.isReply) {
                    insertReply(ledger, result.data, form);
                } else {
                    insertComment(ledger, result.data);
                }
                var input = form.querySelector('[data-ledger-input]');
                if (input) {
                    input.value = '';
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                }
            }).catch(function () {
                showFormError(form, '网络连接失败，留言没有发出去。');
            }).then(function () {
                form.__ledgerSending = false;
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-busy');
                }
            });
        });
    }

    // 同感也走 AJAX：只更新这一枚按钮的数字，不把页面送回去重新加载。
    function setupLikeForms(ledger) {
        Array.prototype.forEach.call(ledger.querySelectorAll('form[data-ledger-like]'), function (form) {
            if (form.__ledgerLike || !window.fetch || !window.FormData) {
                return;
            }
            form.__ledgerLike = true;
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (form.__ledgerLiking) {
                    return;
                }
                form.__ledgerLiking = true;
                var button = form.querySelector('[type="submit"]');
                if (button) {
                    button.disabled = true;
                }

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().catch(function () {
                        return {ok: false};
                    }).then(function (data) {
                        return {ok: response.ok && data.ok, data: data};
                    });
                }).then(function (result) {
                    if (!result.ok || !button) {
                        return;
                    }
                    button.classList.add('is-on');
                    var num = button.querySelector('.num');
                    if (!num) {
                        num = document.createElement('span');
                        num.className = 'num';
                        button.appendChild(num);
                    }
                    num.textContent = String(result.data.count);
                    button.setAttribute('aria-label', '已点赞'
                        + (result.data.count > 0 ? '，' + result.data.count + ' 人' : ''));
                }).catch(function () {
                    // 失败保持原样，按钮恢复可点。
                }).then(function () {
                    form.__ledgerLiking = false;
                    if (button) {
                        button.disabled = false;
                    }
                });
            });
        });
    }

    function initLedger(ledger) {
        var mainInput = ledger.querySelector('.ledger-compose-card [data-ledger-input], .ledger-compose [data-ledger-input]');

        Array.prototype.forEach.call(ledger.querySelectorAll('[data-ledger-input]'), setupInput);
        Array.prototype.forEach.call(ledger.querySelectorAll('.ledger-form'), function (form) {
            setupForm(ledger, form);
        });
        setupLikeForms(ledger);

        // 留言区有内滚时，底部给一条渐隐提示；滑到底自动消失。
        Array.prototype.forEach.call(ledger.querySelectorAll('.ledger-list-scroll'), function (scroller) {
            if (scroller.__ledgerScrollReady) {
                return;
            }
            scroller.__ledgerScrollReady = true;
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

            // 到顶 / 到底后把滚轮明确交给页面；不依赖浏览器对滚动链的延迟判定。
            scroller.addEventListener('wheel', function (event) {
                if (event.ctrlKey) {
                    return;
                }
                var delta = event.deltaY * (event.deltaMode === 1 ? 16 : (event.deltaMode === 2 ? scroller.clientHeight : 1));
                if (!delta) {
                    return;
                }
                var atTop = scroller.scrollTop <= 0;
                var atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 1;
                if ((delta < 0 && atTop) || (delta > 0 && atBottom)) {
                    event.preventDefault();
                    window.scrollBy(0, delta);
                }
            }, { passive: false });

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
            if (button.__ledgerReady) {
                return;
            }
            button.__ledgerReady = true;
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

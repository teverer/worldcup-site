/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261125
   模块:界面设计 —— 前后台共用的确认对话框与轻提示。

   做法参考成熟产品里最常见的两条路径：
   - 原生 <dialog> + showModal（GitHub / GOV.UK 的确认框都是这个结构），
     焦点陷阱、Esc、背景遮罩交给浏览器，脚本只负责文案与动作；
   - flash / toast 从页面流里移到右上角的固定栈，失败停留更久，指针悬停暂停。
   不覆盖 window.confirm / alert，避免影响第三方脚本；只接管带 data-confirm 的
   链接、按钮和表单，没有 JS 时仍由 Yii 的 yii.js 退回原生确认。 */
(function (window, document) {
    'use strict';

    var dialogEl = null;
    var dialogParts = null;
    var dialogOpen = false;
    var toastStack = null;
    var toastCount = 0;
    var MAX_TOASTS = 4;

    var ICONS = {
        danger: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V5h6v2m-8 0 1 13h8l1-13"/></svg>',
        primary: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>',
        success: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
        error: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16h.01"/></svg>',
        warning: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3 20h18z"/><path d="M12 9v5M12 17h.01"/></svg>',
        info: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>'
    };

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, {once: true});
        } else {
            fn();
        }
    }

    function supportsDialog() {
        return typeof window.HTMLDialogElement !== 'undefined'
            && typeof document.createElement('dialog').showModal === 'function';
    }

    function buildDialog() {
        if (dialogEl) {
            return dialogEl;
        }

        dialogEl = document.createElement('dialog');
        dialogEl.className = 'wc-dialog';
        dialogEl.setAttribute('aria-labelledby', 'wc-dialog-title');
        dialogEl.setAttribute('aria-describedby', 'wc-dialog-message');
        dialogEl.innerHTML = ''
            + '<div class="wc-dialog__panel">'
            + '  <span class="wc-dialog__mark" data-wc-dialog-mark aria-hidden="true"></span>'
            + '  <div class="wc-dialog__copy">'
            + '    <h2 class="wc-dialog__title" id="wc-dialog-title"></h2>'
            + '    <p class="wc-dialog__message" id="wc-dialog-message"></p>'
            + '  </div>'
            + '  <div class="wc-dialog__actions">'
            + '    <button type="button" class="wc-btn wc-btn--ghost" data-wc-dialog-cancel>取消</button>'
            + '    <button type="button" class="wc-btn wc-btn--danger" data-wc-dialog-ok>删除</button>'
            + '  </div>'
            + '</div>';

        document.body.appendChild(dialogEl);

        dialogParts = {
            mark: dialogEl.querySelector('[data-wc-dialog-mark]'),
            title: dialogEl.querySelector('#wc-dialog-title'),
            message: dialogEl.querySelector('#wc-dialog-message'),
            ok: dialogEl.querySelector('[data-wc-dialog-ok]'),
            cancel: dialogEl.querySelector('[data-wc-dialog-cancel]')
        };

        return dialogEl;
    }

    function confirmAction(options) {
        options = options || {};

        if (!supportsDialog()) {
            return Promise.resolve(window.confirm(options.message || options.title || '确定继续吗？'));
        }

        if (dialogOpen) {
            return Promise.resolve(false);
        }

        buildDialog();

        var tone = options.tone === 'danger' ? 'danger' : 'primary';
        var title = options.title || (tone === 'danger' ? '确认删除？' : '确认继续？');
        var message = options.message || '';
        var okText = options.okText || (tone === 'danger' ? '删除' : '继续');
        var cancelText = options.cancelText || '取消';
        var previousFocus = document.activeElement;

        dialogEl.className = 'wc-dialog wc-dialog--' + tone;
        dialogParts.mark.innerHTML = ICONS[tone] || ICONS.primary;
        dialogParts.title.textContent = title;
        dialogParts.message.textContent = message;
        dialogParts.message.hidden = message === '';
        dialogParts.ok.textContent = okText;
        dialogParts.cancel.textContent = cancelText;
        dialogParts.ok.className = 'wc-btn ' + (tone === 'danger' ? 'wc-btn--danger' : 'wc-btn--primary');

        return new Promise(function (resolve) {
            var settled = false;

            function finish(value) {
                if (settled) {
                    return;
                }
                settled = true;
                dialogOpen = false;
                dialogParts.ok.removeEventListener('click', onOk);
                dialogParts.cancel.removeEventListener('click', onCancel);
                dialogEl.removeEventListener('cancel', onCancelEvent);
                dialogEl.removeEventListener('close', onClose);
                dialogEl.removeEventListener('click', onBackdrop);
                if (dialogEl.open) {
                    dialogEl.close(value ? 'ok' : 'cancel');
                }
                if (previousFocus && typeof previousFocus.focus === 'function') {
                    previousFocus.focus({preventScroll: true});
                }
                resolve(value);
            }

            function onOk() {
                finish(true);
            }

            function onCancel() {
                finish(false);
            }

            function onCancelEvent(event) {
                event.preventDefault();
                finish(false);
            }

            function onClose() {
                finish(false);
            }

            function onBackdrop(event) {
                if (event.target === dialogEl) {
                    finish(false);
                }
            }

            dialogParts.ok.addEventListener('click', onOk);
            dialogParts.cancel.addEventListener('click', onCancel);
            dialogEl.addEventListener('cancel', onCancelEvent);
            dialogEl.addEventListener('close', onClose);
            dialogEl.addEventListener('click', onBackdrop);

            dialogOpen = true;
            dialogEl.showModal();
            // 破坏性操作默认把焦点放在「取消」，避免回车误删。
            window.setTimeout(function () {
                dialogParts.cancel.focus();
            }, 0);
        });
    }

    function ensureToastStack() {
        if (toastStack && toastStack.parentNode) {
            return toastStack;
        }
        toastStack = document.createElement('div');
        toastStack.className = 'wc-toast-stack';
        toastStack.setAttribute('aria-live', 'polite');
        toastStack.setAttribute('aria-atomic', 'false');
        document.body.appendChild(toastStack);
        return toastStack;
    }

    function dismissToast(node) {
        if (!node || node.getAttribute('data-wc-toast-out') === '1') {
            return;
        }
        node.setAttribute('data-wc-toast-out', '1');
        node.classList.remove('is-in');
        node.classList.add('is-out');
        window.setTimeout(function () {
            if (node.parentNode) {
                node.parentNode.removeChild(node);
                toastCount = Math.max(0, toastCount - 1);
            }
        }, 180);
    }

    function toast(message, options) {
        options = options || {};
        if (!message) {
            return null;
        }

        var tone = options.tone || 'info';
        if (tone === 'danger') {
            tone = 'error';
        }
        var stack = ensureToastStack();
        var node = document.createElement('div');
        var mark = document.createElement('span');
        var text = document.createElement('p');
        var close = document.createElement('button');

        node.className = 'wc-toast wc-toast--' + tone;
        node.setAttribute('role', tone === 'error' ? 'alert' : 'status');

        mark.className = 'wc-toast__mark';
        mark.setAttribute('aria-hidden', 'true');
        mark.innerHTML = ICONS[tone] || ICONS.info;

        text.className = 'wc-toast__text';
        text.textContent = message;

        close.type = 'button';
        close.className = 'wc-toast__close';
        close.setAttribute('aria-label', '关闭提示');
        close.innerHTML = '&times;';

        node.appendChild(mark);
        node.appendChild(text);
        node.appendChild(close);

        // 超过上限时先收掉最早的一条，避免右上角堆满。
        if (toastCount >= MAX_TOASTS && stack.firstElementChild) {
            dismissToast(stack.firstElementChild);
        }

        stack.appendChild(node);
        toastCount += 1;

        window.requestAnimationFrame(function () {
            node.classList.add('is-in');
        });

        var duration = typeof options.duration === 'number'
            ? options.duration
            : (tone === 'error' ? 8000 : 5000);
        var remaining = duration;
        var startedAt = Date.now();
        var timer = null;

        function startTimer() {
            startedAt = Date.now();
            timer = window.setTimeout(function () {
                dismissToast(node);
            }, Math.max(1200, remaining));
        }

        function pauseTimer() {
            if (timer !== null) {
                window.clearTimeout(timer);
                timer = null;
                remaining -= Date.now() - startedAt;
            }
        }

        close.addEventListener('click', function () {
            dismissToast(node);
        });
        node.addEventListener('mouseenter', pauseTimer);
        node.addEventListener('mouseleave', startTimer);
        node.addEventListener('focusin', pauseTimer);
        node.addEventListener('focusout', startTimer);

        startTimer();
        return node;
    }

    function flashTone(type) {
        if (type === 'success') {
            return 'success';
        }
        if (type === 'error' || type === 'danger') {
            return 'error';
        }
        if (type === 'warning') {
            return 'warning';
        }
        return 'info';
    }

    function moveFlashMessages() {
        var flashes = document.querySelectorAll('[data-wc-flash]');
        Array.prototype.forEach.call(flashes, function (node) {
            var clone = node.cloneNode(true);
            Array.prototype.forEach.call(clone.querySelectorAll('.close, script, style'), function (junk) {
                junk.parentNode.removeChild(junk);
            });
            var message = (clone.textContent || '').replace(/\s+/g, ' ').trim();
            if (message) {
                toast(message, {tone: flashTone(node.getAttribute('data-wc-flash'))});
            }
            if (node.parentNode) {
                node.parentNode.removeChild(node);
            }
        });
    }

    function inferOptions(trigger) {
        var className = trigger.getAttribute('class') || '';
        var label = (trigger.textContent || '').trim();
        var tone = trigger.getAttribute('data-confirm-tone');
        var danger = tone === 'danger'
            || /\b(btn-danger|danger|text-danger)\b/i.test(className)
            || /删除|移除|注销|清空/.test(label);

        return {
            title: trigger.getAttribute('data-confirm-title') || (danger ? '确认删除？' : '确认继续？'),
            message: trigger.getAttribute('data-confirm') || '',
            okText: trigger.getAttribute('data-confirm-ok') || (danger ? '删除' : '继续'),
            cancelText: trigger.getAttribute('data-confirm-cancel') || '取消',
            tone: danger ? 'danger' : 'primary'
        };
    }

    function proceed(trigger) {
        var form = trigger.form || trigger.closest('form');
        var removed = [];
        var bypass = 'data-wc-confirm-bypass';

        function strip(node) {
            if (node && node.hasAttribute('data-confirm')) {
                removed.push([node, node.getAttribute('data-confirm')]);
                node.removeAttribute('data-confirm');
            }
        }

        trigger.setAttribute(bypass, '1');
        strip(trigger);
        strip(form);

        try {
            if (trigger.tagName === 'FORM') {
                if (typeof trigger.requestSubmit === 'function') {
                    trigger.requestSubmit();
                } else {
                    trigger.submit();
                }
            } else if (trigger.tagName === 'A') {
                trigger.click();
            } else if (form && (trigger.type === 'submit' || trigger.tagName === 'BUTTON')) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(trigger);
                } else {
                    trigger.click();
                }
            } else {
                trigger.click();
            }
        } finally {
            window.setTimeout(function () {
                trigger.removeAttribute(bypass);
                removed.forEach(function (pair) {
                    pair[0].setAttribute('data-confirm', pair[1]);
                });
            }, 0);
        }
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        var target = event.target;
        if (!target || typeof target.closest !== 'function') {
            return;
        }
        var trigger = target.closest('[data-confirm]');
        if (!trigger || trigger.getAttribute('data-wc-confirm-bypass') === '1') {
            return;
        }
        if (trigger.disabled) {
            return;
        }

        // 捕获阶段先于 yii.js 的委托处理，拿到确认权；没有 JS 时仍退回原生 confirm。
        event.preventDefault();
        event.stopImmediatePropagation();

        confirmAction(inferOptions(trigger)).then(function (ok) {
            if (ok) {
                proceed(trigger);
            }
        });
    }, true);

    // 表单级的 data-confirm（或回车直接提交）不经过 click，单独接住。
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM' || !form.hasAttribute('data-confirm')) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        confirmAction(inferOptions(form)).then(function (ok) {
            if (ok) {
                proceed(form);
            }
        });
    }, true);

    ready(function () {
        moveFlashMessages();
    });

    window.WCUI = {
        confirm: confirmAction,
        toast: toast,
        dismissToast: dismissToast,
        // AJAX 局部刷新后如果拿到新的 flash 节点，可以再调用一次。
        flash: moveFlashMessages
    };
}(window, document));

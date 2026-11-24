/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261115
   模块:届次与比赛 —— 积分榜、射手榜、各队进球分布的 ECharts 渲染。 */
(function () {
    'use strict';

    // R6 夜场 + 图表大气版:轴/图例/标签用暖白,系列色比 R6 更饱和、更亮。
    var INK = {
        primary: '#F4F2EC',
        secondary: '#D7DFDA',
        muted: '#A9BAB2',
        grid: 'rgba(244, 242, 236, .13)',
        axis: '#8FA29B',
        surface: '#0A1512'
    };

    // 夜场图表:主序列青绿、第二序列蓝、成就金。
    var PRIMARY_SERIES = '#2FAE83';
    var HOVER_SERIES = '#57D9AE';
    var PEAK_SERIES = '#7BE8C0';
    var EXTRA_SERIES = '#D24A52';
    var ACCENT_SERIES = '#E0B457';

    // 数字用 Saira Condensed(站内已预加载),中文名回退系统黑体。
    var NUM_FONT = '"Saira Condensed", "Arial Narrow", "Microsoft YaHei", sans-serif';
    var CN_FONT = '"Microsoft YaHei", "PingFang SC", "Noto Sans SC", sans-serif';

    var chartInstances = {};
    var resizeTimer = null;
    // 站内硬规则:尊重 prefers-reduced-motion;开启时取消 ECharts 入场动画。
    var prefersReducedMotion = !!(window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var ANIMATION_DURATION = prefersReducedMotion ? 0 : 500;

    function byId(id) {
        return document.getElementById(id);
    }

    function hasEcharts() {
        return typeof window.echarts !== 'undefined'
            && typeof window.echarts.init === 'function';
    }

    // ECharts 约 1MB,不再首屏加载:统计面板第一次可见时才注入,
    // 球员页则等图表接近视口;失败时由调用方显示空态。
    var echartsLoader = { loading: false, callbacks: [] };

    function loadEcharts(src, done) {
        if (hasEcharts() || window.__echartsLoadFailed) {
            done();
            return;
        }
        echartsLoader.callbacks.push(done);
        if (echartsLoader.loading) {
            return;
        }
        echartsLoader.loading = true;
        var script = document.createElement('script');
        script.async = true;
        script.src = src || '/js/vendor/echarts.min.js';
        script.onload = function () {
            echartsLoader.loading = false;
            if (!hasEcharts()) {
                window.__echartsLoadFailed = true;
            }
            var callbacks = echartsLoader.callbacks.splice(0);
            callbacks.forEach(function (callback) {
                callback();
            });
        };
        script.onerror = function () {
            echartsLoader.loading = false;
            window.__echartsLoadFailed = true;
            var callbacks = echartsLoader.callbacks.splice(0);
            callbacks.forEach(function (callback) {
                callback();
            });
        };
        document.head.appendChild(script);
    }

    // 元素接近视口时再执行;用于球员图/球队轨迹图,避免首屏下载 ECharts。
    function whenNearViewport(container, callback) {
        if (typeof window.IntersectionObserver !== 'function') {
            callback();
            return;
        }
        if (container.__nearObserver) {
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) {
                observer.disconnect();
                container.__nearObserver = null;
                callback();
            }
        }, { rootMargin: '240px' });
        container.__nearObserver = observer;
        observer.observe(container);
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatSigned(value) {
        var number = Number(value) || 0;
        return (number > 0 ? '+' : '') + number;
    }

    // 国旗放在轴标签最前面:24×15 的小图,没有可靠旗源时直接不占位。
    function addFlagRich(rich, rows, prefix) {
        rows.forEach(function (row, index) {
            if (row && row.teamFlag) {
                rich[prefix + index] = {
                    backgroundColor: { image: row.teamFlag },
                    width: 24,
                    height: 15,
                    borderRadius: 2,
                    borderColor: 'rgba(244, 242, 236, .22)',
                    borderWidth: 1
                };
                return;
            }
            // 没有可靠旗源（苏联 / 南斯拉夫 / 塞黑）：留一个同尺寸空框，
            // 保证图例 / 轴名整列对齐，不因缺旗左移。
            rich[prefix + index] = {
                backgroundColor: 'rgba(244, 242, 236, .06)',
                width: 24,
                height: 15,
                borderRadius: 2,
                borderColor: 'rgba(244, 242, 236, .16)',
                borderWidth: 1
            };
        });
        return rich;
    }

    function flagTag(row, index, prefix) {
        return row ? '{' + prefix + index + '|}  ' : '';
    }

    // 球员名统一为「首字母大写、其余小写」：Kylian MBAPPE -> Kylian Mbappe。
    // 同时处理空格、连字符与撇号后的首字母，兼容 Heung-min、O'Neill 等写法。
    // 与 PHP 端 wc_player_name_v2 cookie 保持一致:默认原名,只有主动切到中文名才用中文。
    function prefersOriginalName() {
        return !/(?:^|;\s*)wc_player_name_v2=zh(?:;|$)/.test(document.cookie);
    }

    // 有中文名优先显示中文名;没有或访客选择原名时回退原名。
    function displayPlayerName(row) {
        if (row && row.playerNameZh && (row.alwaysZh || !prefersOriginalName())) {
            return String(row.playerNameZh);
        }
        return formatPlayerName(row ? row.playerName : '');
    }

    function formatPlayerName(value) {
        var name = String(value === null || value === undefined ? '' : value)
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
        if (!name) {
            return '';
        }
        return name.replace(/(^|[\s\-'’])([a-zà-ÿ])/g, function (match, separator, letter) {
            return separator + letter.toUpperCase();
        });
    }

    // 粗略估算轴标签像素宽度：中日韩字符按全角计，其余按半角计。
    function estimateTextWidth(text, fontSize) {
        var width = 0;
        var scale = (Number(fontSize) || 13) / 13;
        var value = String(text === null || text === undefined ? '' : text);
        for (var i = 0; i < value.length; i += 1) {
            width += (value.charCodeAt(i) > 0x2e7f ? 11 : 6.2) * scale;
        }
        return width;
    }

    function findGroup(groups, name) {
        for (var i = 0; i < groups.length; i += 1) {
            if (groups[i].group === name) {
                return groups[i];
            }
        }
        return null;
    }

    function disposeChart(container) {
        if (!container) {
            return;
        }
        if (hasEcharts() && typeof window.echarts.getInstanceByDom === 'function') {
            var existing = window.echarts.getInstanceByDom(container);
            if (existing && !existing.isDisposed()) {
                existing.dispose();
            }
        }
        if (container.id) {
            delete chartInstances[container.id];
        }
    }

    function showEmpty(container, message) {
        if (!container) {
            return;
        }
        disposeChart(container);
        container.innerHTML = '<p class="chart-empty-state">'
            + escapeHtml(message || '暂无数据')
            + '</p>';
    }

    function initChart(container) {
        if (!container || !hasEcharts()) {
            showEmpty(container, '图表组件加载失败，请检查网络后刷新页面。');
            return null;
        }
        disposeChart(container);
        container.innerHTML = '';
        var chart = window.echarts.init(container, null, { renderer: 'canvas' });
        if (container.id) {
            chartInstances[container.id] = chart;
        }
        return chart;
    }

    function buildApiUrl(baseUrl, tournamentId, group) {
        if (!baseUrl) {
            return '';
        }
        var url;
        try {
            url = new URL(baseUrl, window.location.href);
        } catch (e) {
            return baseUrl;
        }
        url.searchParams.set('tournamentId', tournamentId);
        if (group) {
            url.searchParams.set('group', group);
        }
        return url.toString();
    }

    function fetchJson(url) {
        if (typeof window.fetch !== 'function') {
            return Promise.reject(new Error('当前浏览器不支持 fetch'));
        }
        return window.fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    function renderTable(container, columns, rows) {
        if (!container) {
            return;
        }
        if (!rows || !rows.length) {
            container.innerHTML = '<p class="chart-empty-state">暂无数据</p>';
            return;
        }

        var html = '<table class="chart-table"><thead><tr>';
        var i;
        var j;
        for (i = 0; i < columns.length; i += 1) {
            html += '<th scope="col">' + escapeHtml(columns[i].label) + '</th>';
        }
        html += '</tr></thead><tbody>';
        for (i = 0; i < rows.length; i += 1) {
            html += '<tr>';
            for (j = 0; j < columns.length; j += 1) {
                html += '<td' + (columns[j].numeric ? ' class="chart-table-num"' : '') + '>'
                    + escapeHtml(columns[j].value(rows[i]))
                    + '</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function failChart(containerId, tableId, message) {
        showEmpty(byId(containerId), message);
        var table = byId(tableId);
        if (table) {
            table.innerHTML = '<p class="chart-empty-state">' + escapeHtml(message) + '</p>';
        }
    }

    function renderStandings(data) {
        var groups = (data && data.groups) || [];
        var rows = [];

        groups.forEach(function (group) {
            (group.rows || []).forEach(function (row) {
                rows.push(Object.assign({ group: group.group }, row));
            });
        });

        // 数据表保留全部小组的完整明细。
        renderTable(byId('table-standings'), [
            { label: '小组', value: function (row) { return row.group; } },
            { label: '排名', value: function (row) { return row.rank; }, numeric: true },
            { label: '球队', value: function (row) { return row.teamName; } },
            { label: '赛', value: function (row) { return row.played; }, numeric: true },
            { label: '胜', value: function (row) { return row.won; }, numeric: true },
            { label: '平', value: function (row) { return row.drawn; }, numeric: true },
            { label: '负', value: function (row) { return row.lost; }, numeric: true },
            { label: '进 / 失', value: function (row) { return row.goalsFor + ' / ' + row.goalsAgainst; }, numeric: true },
            { label: '净胜', value: function (row) { return formatSigned(row.goalDifference); }, numeric: true },
            { label: '积分', value: function (row) { return row.points; }, numeric: true }
        ], rows);

        if (!rows.length) {
            showEmpty(byId('chart-standings'), '暂无数据');
            return;
        }

        // 只展示有数据的小组,默认 A 组;URL 带 group 参数时优先沿用。
        var availableGroups = groups.filter(function (group) {
            return group.rows && group.rows.length;
        });
        if (!availableGroups.length) {
            showEmpty(byId('chart-standings'), '暂无数据');
            return;
        }

        var root = byId('charts-panel');
        var requestedGroup = root ? root.getAttribute('data-group') : '';
        var activeGroup = findGroup(availableGroups, requestedGroup) ? requestedGroup : null;
        if (!activeGroup) {
            activeGroup = findGroup(availableGroups, 'A') ? 'A' : availableGroups[0].group;
        }

        var chart = initChart(byId('chart-standings'));
        if (!chart) {
            return;
        }

        var chartRows = [];
        var switchContainer = byId('standings-group-switch');

        function groupLabel(name) {
            return name === '未分组' ? name : name + ' 组';
        }

        function drawGroup(groupData) {
            chartRows = groupData.rows || [];
            var compact = chart.getWidth() < 560;
            var flagRich = compact ? {} : addFlagRich({}, chartRows, 'sf');
            var categories = [];
            var values = [];
            var groupLeaders = [];
            chartRows.forEach(function (row) {
                categories.push(row.teamName);
                values.push(Number(row.points) || 0);
                // 小组头名使用金色强调,其余球队使用主题绿色。
                groupLeaders.push(Number(row.rank) === 1);
            });

            chart.setOption({
                animationDuration: ANIMATION_DURATION,
                // 横向条形:队名在左、积分在右,四支球队比竖向柱更紧凑、也更容易读排名。
                grid: { left: 10, right: compact ? 42 : 64, top: 24, bottom: 20, containLabel: true },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: INK.surface,
                    borderColor: INK.grid,
                    textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                    formatter: function (params) {
                        var row = chartRows[params[0].dataIndex];
                        if (!row) {
                            return '';
                        }
                        return '<strong>' + escapeHtml(row.teamName) + '</strong>'
                            + '<br>小组：' + escapeHtml(groupData.group)
                            + '<br>排名：第 ' + row.rank + ' 名'
                            + '<br>积分：' + row.points
                            + '<br>胜 / 平 / 负：' + row.won + ' / ' + row.drawn + ' / ' + row.lost
                            + '<br>进球 / 失球：' + row.goalsFor + ' / ' + row.goalsAgainst
                            + '<br>净胜球：' + formatSigned(row.goalDifference);
                    }
                },
                xAxis: {
                    type: 'value',
                    name: '',
                    nameTextStyle: { color: INK.muted, fontSize: 14, fontFamily: NUM_FONT, padding: [0, 0, 4, 0] },
                    minInterval: 1,
                    axisLabel: { color: INK.secondary, fontSize: compact ? 13 : 16, fontFamily: NUM_FONT },
                    axisLine: { show: false },
                    axisTick: { show: false },
                    splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
                },
                yAxis: {
                    type: 'category',
                    // 排名第一放在最上方。
                    inverse: true,
                    data: categories,
                    axisTick: { show: false },
                    axisLine: { lineStyle: { color: INK.axis } },
                    axisLabel: {
                        color: INK.secondary,
                        fontSize: compact ? 15 : 17,
                        fontFamily: CN_FONT,
                        margin: compact ? 10 : 12,
                        formatter: function (value, index) {
                            return (compact ? '' : flagTag(chartRows[index], index, 'sf'))
                                + (index === 0
                                    ? '{first|1}  ' + value
                                    : '{rank|' + (index + 1) + '}  ' + value);
                        },
                        rich: Object.assign({
                            first: {
                                color: ACCENT_SERIES,
                                fontSize: compact ? 15 : 18,
                                fontFamily: NUM_FONT,
                                fontWeight: 'bold'
                            },
                            rank: {
                                color: INK.muted,
                                fontSize: compact ? 14 : 17,
                                fontFamily: NUM_FONT,
                                fontWeight: 'bold'
                            }
                        }, flagRich)
                    }
                },
                series: [{
                    name: '积分',
                    type: 'bar',
                    data: values,
                    barMaxWidth: compact ? 38 : 46,
                    itemStyle: {
                        color: function (params) {
                            return groupLeaders[params.dataIndex] ? HOVER_SERIES : PRIMARY_SERIES;
                        },
                        borderRadius: [0, 4, 4, 0]
                    },
                    label: {
                        show: true,
                        position: 'right',
                        color: INK.primary,
                        fontSize: compact ? 17 : 19,
                        fontFamily: NUM_FONT,
                        fontWeight: 'bold'
                    },
                    emphasis: { itemStyle: { color: HOVER_SERIES } }
                }]
            }, true);
            chart.resize();
        }

        function selectGroup(name) {
            var groupData = findGroup(availableGroups, name);
            if (!groupData) {
                return;
            }
            activeGroup = name;
            if (switchContainer) {
                var buttons = switchContainer.querySelectorAll('button[data-group]');
                Array.prototype.forEach.call(buttons, function (button) {
                    button.setAttribute('aria-pressed',
                        button.getAttribute('data-group') === name ? 'true' : 'false');
                });
            }
            drawGroup(groupData);
        }

        if (switchContainer) {
            switchContainer.innerHTML = '';
            availableGroups.forEach(function (groupData) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'chart-group-switch-btn';
                button.setAttribute('data-group', groupData.group);
                button.setAttribute('aria-pressed', groupData.group === activeGroup ? 'true' : 'false');
                button.textContent = groupLabel(groupData.group);
                button.addEventListener('click', function () {
                    selectGroup(groupData.group);
                });
                switchContainer.appendChild(button);
            });
        }

        drawGroup(findGroup(availableGroups, activeGroup));
    }

    function renderScorers(data) {
        var scorers = (data && data.scorers) || [];
        var chartRows = scorers.slice().reverse();

        renderTable(byId('table-scorers'), [
            { label: '排名', value: function (row) { return row.rank; }, numeric: true },
            { label: '球员', value: function (row) { return displayPlayerName(row); } },
            { label: '球队', value: function (row) { return row.teamName; } },
            { label: '进球', value: function (row) { return row.goals; }, numeric: true }
        ], scorers);

        if (!scorers.length) {
            showEmpty(byId('chart-scorers'), '暂无数据');
            return;
        }

        var container = byId('chart-scorers');
        var compact = container.clientWidth < 560;
        var flagRich = compact ? {} : addFlagRich({}, chartRows, 'pf');
        // 窄屏只留姓名,球队放在 tooltip;桌面保留「姓名 · 球队」。
        var names = chartRows.map(function (row) {
            var name = displayPlayerName(row);
            if (compact) {
                // 窄屏去掉国旗后，用队码区分同名 / 同国球员；队码在前，截断也看得到。
                return row.teamCode ? row.teamCode + ' · ' + name : name;
            }
            return name + ' · ' + row.teamName;
        });
        var maxNameWidth = names.reduce(function (maxWidth, name) {
            return Math.max(maxWidth, estimateTextWidth(name, compact ? 14 : 17));
        }, 0);
        var yLabelWidth = compact
            ? 120
            : Math.min(330, Math.max(210, Math.ceil(maxNameWidth) + 42));
        var goals = chartRows.map(function (row) {
            return Number(row.goals) || 0;
        });
        // 只有射手榜第一名使用金色,其余使用深绿。
        var leaderIndex = chartRows.length - 1;

        var chart = initChart(container);
        if (!chart) {
            return;
        }

        chart.setOption({
            animationDuration: ANIMATION_DURATION,
            grid: { left: 10, right: compact ? 54 : 64, top: 12, bottom: 16, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                formatter: function (params) {
                    var row = chartRows[params[0].dataIndex];
                    if (!row) {
                        return '';
                    }
                    var label = displayPlayerName(row);
                    if (row.playerNameZh) {
                        label += '（' + formatPlayerName(row.playerName) + '）';
                    }
                    return '<strong>' + escapeHtml(label) + '</strong>'
                        + '<br>球队：' + escapeHtml(row.teamName)
                        + '<br>进球：' + row.goals;
                }
            },
            xAxis: {
                type: 'value',
                name: '',
                minInterval: 1,
                axisLabel: { color: INK.muted, fontSize: compact ? 13 : 15, fontFamily: NUM_FONT },
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: 'rgba(244, 242, 236, .075)', width: 1, type: 'solid' } }
            },
            yAxis: {
                type: 'category',
                data: names,
                axisTick: { show: false },
                axisLine: { lineStyle: { color: INK.axis } },
                axisLabel: {
                    color: INK.secondary,
                    fontSize: compact ? 14 : 17,
                    fontFamily: CN_FONT,
                    margin: compact ? 10 : 12,
                    width: yLabelWidth,
                    overflow: 'truncate',
                    ellipsis: '…',
                    formatter: function (value, index) {
                        return compact
                            ? value
                            : flagTag(chartRows[index], index, 'pf') + value;
                    },
                    rich: flagRich
                }
            },
            series: [{
                name: '进球数',
                type: 'bar',
                data: goals,
                barMaxWidth: compact ? 24 : 30,
                barCategoryGap: '46%',
                showBackground: true,
                backgroundStyle: { color: 'rgba(244, 242, 236, .035)' },
                itemStyle: {
                    color: function (params) {
                        return params.dataIndex === leaderIndex ? ACCENT_SERIES : PRIMARY_SERIES;
                    },
                    borderRadius: [0, 2, 2, 0]
                },
                label: {
                    show: true,
                    position: 'right',
                    color: INK.primary,
                    fontSize: compact ? 16 : 19,
                    fontFamily: NUM_FONT,
                    fontWeight: 'bold'
                },
                emphasis: { itemStyle: { color: HOVER_SERIES } }
            }]
        });
        chart.resize();
    }

    // 进球时间分布：7 根竖柱回答“什么时候进球”，与小组赛积分表不重复。
    function renderGoalTiming(data) {
        var buckets = (data && data.buckets) || [];
        var total = Number(data && data.totalGoals) || 0;
        var secondHalf = Number(data && data.secondHalfGoals) || 0;
        var secondHalfPercent = Number(data && data.secondHalfPercent) || 0;

        if (!buckets.length) {
            showEmpty(byId('chart-goal-timing'), '暂无数据');
            return;
        }

        renderTable(byId('table-goal-timing'), [
            { label: '时段', value: function (row) { return row.label; } },
            { label: '进球', value: function (row) { return row.goals; }, numeric: true },
            { label: '占全届', value: function (row) { return row.percent + '%'; }, numeric: true }
        ], buckets);

        var container = byId('chart-goal-timing');
        var chart = initChart(container);
        if (!chart) {
            return;
        }
        var compact = container.clientWidth < 560;
        var labels = buckets.map(function (row, index) {
            if (!compact) {
                return row.label;
            }
            return index === buckets.length - 1 ? '加时' : row.label.replace('+', '');
        });
        var values = buckets.map(function (row) {
            return Number(row.goals) || 0;
        });
        // 常规时段里进球最多的一段,用来做一句结论;加时不参与“15 分钟”比较。
        var peakIndex = 0;
        values.forEach(function (value, index) {
            if (index < values.length - 1 && value > values[peakIndex]) {
                peakIndex = index;
            }
        });
        var colors = values.map(function (value, index) {
            if (index === values.length - 1) {
                return EXTRA_SERIES;
            }
            if (index === peakIndex) {
                return PEAK_SERIES;
            }
            return index >= 3 ? HOVER_SERIES : PRIMARY_SERIES;
        });

        // 关键数字放进 HTML,不画进 canvas:更清晰、可选中、屏幕阅读器能读。
        var totalEl = byId('timing-total');
        var secondEl = byId('timing-second');
        var peakEl = byId('timing-peak');
        var statsEl = byId('chart-timing-stats');
        if (totalEl) {
            totalEl.textContent = String(total);
        }
        if (secondEl) {
            secondEl.textContent = secondHalfPercent + '%';
        }
        if (peakEl) {
            // 单位用小子号,让数字本身站住,而不是把「球」也放大成标题。
            peakEl.innerHTML = String(values[peakIndex])
                + '<span class="chart-hero-unit">球</span>';
        }
        if (statsEl) {
            statsEl.hidden = false;
        }

        chart.setOption({
            animationDuration: ANIMATION_DURATION,
            grid: {
                left: 6,
                right: 8,
                top: 22,
                bottom: 4,
                containLabel: true
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                formatter: function (params) {
                    var index = params[0].dataIndex;
                    var row = buckets[index];
                    return row
                        ? '<strong>' + escapeHtml(row.label) + '</strong><br>'
                            + row.goals + ' 球 · 占全届 ' + row.percent + '%'
                        : '';
                }
            },
            xAxis: {
                type: 'category',
                data: labels,
                axisTick: { show: false },
                axisLine: { lineStyle: { color: INK.grid } },
                axisLabel: {
                    color: INK.secondary,
                    fontSize: compact ? 12 : 15,
                    fontFamily: CN_FONT,
                    interval: 0,
                    rotate: compact ? 28 : 0,
                    margin: 12
                }
            },
            // 有刻度才能读出“42 球”大概多高;三条细线足够,不铺满网格。
            yAxis: {
                type: 'value',
                splitNumber: 3,
                axisLabel: {
                    color: INK.muted,
                    fontSize: compact ? 11 : 13,
                    fontFamily: NUM_FONT
                },
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: INK.grid, width: 1 } }
            },
            series: [{
                name: '进球',
                type: 'bar',
                data: values.map(function (value, index) {
                    return {
                        value: value,
                        itemStyle: {
                            color: colors[index],
                            borderRadius: [2, 2, 0, 0]
                        }
                    };
                }),
                // 柱子之间留出呼吸;不靠阴影和渐变,只靠色相和高度表达。
                barMaxWidth: compact ? 30 : 64,
                barCategoryGap: '42%',
                showBackground: true,
                backgroundStyle: { color: 'rgba(244, 242, 236, .045)' },
                // 把 7 根柱子分成“上半场 / 下半场”两段读,而不是让读者自己数到第 4 根。
                markArea: {
                    silent: true,
                    itemStyle: { color: 'rgba(87, 217, 174, .035)' },
                    label: {
                        show: true,
                        position: 'insideTop',
                        color: INK.muted,
                        fontSize: compact ? 11 : 13,
                        fontFamily: CN_FONT,
                        padding: [8, 0, 0, 0]
                    },
                    data: [
                        [{ name: '上半场', xAxis: 0 }, { xAxis: 2 }],
                        [{ name: '下半场', xAxis: 3 }, { xAxis: 5 }]
                    ]
                },
                label: {
                    show: true,
                    position: 'top',
                    color: INK.primary,
                    fontSize: compact ? 13 : 17,
                    fontFamily: NUM_FONT,
                    fontWeight: 'bold',
                    formatter: function (params) {
                        return String(params.value);
                    }
                },
                emphasis: { itemStyle: { color: HOVER_SERIES } }
            }]
        });
        chart.resize();
    }

    // 占比统一以全届总进球为分母:图例隐藏 / 聚合都不会改写它。
    function sharePercent(value, total) {
        if (!total) {
            return 0;
        }
        return Math.round((Number(value) || 0) / total * 1000) / 10;
    }

    function renderTeamGoals(data) {
        var teams = (data && data.teams) || [];
        var total = Number(data && data.totalGoals);
        if (!isFinite(total) || total < 0) {
            total = teams.reduce(function (sum, row) {
                return sum + (Number(row.goals) || 0);
            }, 0);
        }

        var tableRows = teams.map(function (row, index) {
            return {
                rank: index + 1,
                teamName: row.teamName,
                teamFlag: row.teamFlag || '',
                goals: row.goals,
                percent: sharePercent(row.goals, total)
            };
        });

        renderTable(byId('table-team-goals'), [
            { label: '排名', value: function (row) { return row.rank; }, numeric: true },
            { label: '球队', value: function (row) { return row.teamName; } },
            { label: '进球', value: function (row) { return row.goals; }, numeric: true },
            { label: '占比', value: function (row) { return row.percent + '%'; }, numeric: true }
        ], tableRows);

        if (!teams.length) {
            showEmpty(byId('chart-team-goals'), '暂无数据');
            return;
        }

        // 圆环：前 8 队 + 其他；占比固定以全届总进球为分母。
        // 与上面的射手榜条形图形成不同形态：那张回答“谁进球”，这张回答“各队占多少”。
        var container = byId('chart-team-goals');
        var chart = initChart(container);
        if (!chart) {
            return;
        }
        var compact = container.clientWidth < 560;
        var topCount = 8;
        var topRows = tableRows.slice(0, topCount);
        var restRows = tableRows.slice(topCount);
        var restGoals = restRows.reduce(function (sum, row) {
            return sum + (Number(row.goals) || 0);
        }, 0);
        var restPercent = sharePercent(restGoals, total);
        var legendIndex = {};
        topRows.forEach(function (row, index) {
            legendIndex[row.teamName] = index;
        });

        // 占比是“多少”而不是“哪一类”:用同一色相的明度阶梯,
        // 进球越多越亮;不拿彩虹色当分类,避免读者以为颜色有别的含义。
        var PIE_COLORS = [
            '#7BE8C0', '#5BD0A6', '#46BB8F', '#37A47C',
            '#2D8C6A', '#267558', '#205E47', '#1C4A38'
        ];
        var slices = topRows.map(function (row, index) {
            return {
                name: row.teamName,
                value: Number(row.goals) || 0,
                percent: row.percent,
                rank: row.rank,
                itemStyle: {
                    color: index === 0
                        ? ACCENT_SERIES
                        : PIE_COLORS[(index - 1) % PIE_COLORS.length]
                }
            };
        });
        if (restRows.length) {
            slices.push({
                name: '其他 ' + restRows.length + ' 队',
                value: restGoals,
                percent: restPercent,
                rank: null,
                itemStyle: { color: '#6E8389' }
            });
        }

        var useFlags = !compact;
        var legendRich = useFlags ? addFlagRich({}, topRows, 'lg') : {};
        var legendFormatter = function (name) {
            var index = legendIndex[name];
            var row = index === undefined ? null : topRows[index];
            var flag = useFlags && row ? flagTag(row, index, 'lg') : '';
            if (compact) {
                return flag + name;
            }
            return flag + name
                + (row ? '  ' + row.goals + ' 球 · ' + row.percent + '%' : '  ' + restGoals + ' 球 · ' + restPercent + '%');
        };
        var centerX = compact ? '50%' : '36%';

        chart.setOption({
            animationDuration: ANIMATION_DURATION,
            tooltip: {
                trigger: 'item',
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                formatter: function (params) {
                    var row = params.data || {};
                    return '<strong>' + escapeHtml(row.name || '') + '</strong>'
                        + '<br>' + row.value + ' 球 · 占全届 ' + row.percent + '%'
                        + (row.rank ? '<br>第 ' + row.rank + ' 名' : '');
                }
            },
            legend: compact ? {
                type: 'plain',
                orient: 'horizontal',
                bottom: 2,
                left: 'center',
                right: 'center',
                itemWidth: 7,
                itemHeight: 7,
                itemGap: 6,
                icon: 'circle',
                textStyle: {
                    color: INK.secondary,
                    fontSize: 12,
                    fontFamily: CN_FONT
                },
                formatter: legendFormatter
            } : {
                orient: 'vertical',
                right: '2%',
                top: 'middle',
                itemWidth: 9,
                itemHeight: 9,
                itemGap: 14,
                icon: 'circle',
                textStyle: {
                    color: INK.secondary,
                    fontSize: 15,
                    fontFamily: CN_FONT,
                    rich: legendRich
                },
                formatter: legendFormatter
            },
            graphic: [
                {
                    type: 'text',
                    left: centerX,
                    top: compact ? '25%' : '43%',
                    style: {
                        text: String(total),
                        fill: INK.primary,
                        font: 'bold ' + (compact ? 36 : 54) + 'px ' + NUM_FONT,
                        textAlign: 'center'
                    }
                },
                {
                    type: 'text',
                    left: centerX,
                    top: compact ? '38%' : '53%',
                    style: {
                        text: '全届进球',
                        fill: INK.muted,
                        font: '13px ' + CN_FONT,
                        textAlign: 'center'
                    }
                }
            ],
            series: [{
                name: '进球',
                type: 'pie',
                radius: compact ? ['34%', '56%'] : ['48%', '70%'],
                center: compact ? ['50%', '34%'] : ['36%', '52%'],
                avoidLabelOverlap: true,
                label: { show: false },
                labelLine: { show: false },
                itemStyle: {
                    borderColor: INK.surface,
                    borderWidth: 2
                },
                emphasis: {
                    scale: true,
                    scaleSize: 5,
                    itemStyle: {
                        shadowBlur: 18,
                        shadowColor: 'rgba(0, 0, 0, .35)'
                    }
                },
                data: slices
            }]
        });
        chart.resize();
    }

    // 球员详情页:各届进球 / 助攻并排柱。数据由 _career_chart.php 内联输出,
    // 不额外请求接口;没有进球或助攻时该节根本不会渲染。
    function initPlayerCareer() {
        var container = byId('player-career-chart');
        if (!container || container.__careerDone) {
            return;
        }

        // 球员图在首屏下方:接近视口再加载 ECharts,别让球员页一打开就下载 1MB。
        if (!hasEcharts() && !window.__echartsLoadFailed) {
            whenNearViewport(container, function () {
                loadEcharts(container.getAttribute('data-echarts-src'), function () {
                    initPlayerCareer();
                });
            });
            return;
        }

        var dataEl = byId('player-career-data');
        var data = null;
        try {
            data = dataEl ? JSON.parse(dataEl.textContent || '') : null;
        } catch (e) {
            data = null;
        }

        if (!data || !data.years || !data.years.length) {
            container.__careerDone = true;
            showEmpty(container, '暂无生涯数据');
            return;
        }

        var goals = (data.goals || []).map(function (value) {
            return Number(value) || 0;
        });
        var assists = (data.assists || []).map(function (value) {
            return Number(value) || 0;
        });
        var hasAssists = assists.some(function (value) {
            return value > 0;
        });

        if (!hasEcharts() || window.__echartsLoadFailed) {
            container.__careerDone = true;
            showEmpty(container, '图表组件加载失败，请检查网络后刷新页面。');
            return;
        }

        var chart = initChart(container);
        if (!chart) {
            return;
        }

        var years = data.years.map(String);
        var series = [{
            name: '进球',
            type: 'bar',
            data: goals,
            barMaxWidth: 82,
            itemStyle: {
                color: PRIMARY_SERIES,
                borderRadius: [3, 3, 0, 0]
            },
            label: {
                show: true,
                position: 'top',
                color: INK.primary,
                fontSize: 19,
                fontFamily: NUM_FONT,
                fontWeight: 'bold',
                formatter: function (params) {
                    return params.value > 0 ? params.value : '';
                }
            },
            emphasis: { itemStyle: { color: HOVER_SERIES } }
        }];

        if (hasAssists) {
            series.push({
                name: '助攻',
                type: 'bar',
                data: assists,
                barMaxWidth: 82,
                barGap: '12%',
                itemStyle: {
                    color: '#4B8FD6',
                    borderRadius: [3, 3, 0, 0]
                },
                label: {
                    show: true,
                    position: 'top',
                    color: INK.secondary,
                    fontSize: 17,
                    fontFamily: NUM_FONT,
                    formatter: function (params) {
                        return params.value > 0 ? params.value : '';
                    }
                },
                emphasis: { itemStyle: { color: '#6FA8E8' } }
            });
        }

        chart.setOption({
            animationDuration: ANIMATION_DURATION,
            grid: { left: 8, right: 20, top: 46, bottom: 10, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                formatter: function (params) {
                    var lines = ['<strong>' + escapeHtml(params[0].axisValue) + ' 年</strong>'];
                    params.forEach(function (item) {
                        if (item.value > 0 || item.seriesName === '进球') {
                            lines.push(item.seriesName + '：' + item.value);
                        }
                    });
                    return lines.join('<br>');
                }
            },
            legend: {
                show: hasAssists,
                top: 0,
                right: 0,
                icon: 'roundRect',
                itemWidth: 13,
                itemHeight: 10,
                textStyle: { color: INK.secondary, fontSize: 15, fontFamily: CN_FONT }
            },
            xAxis: {
                type: 'category',
                data: years,
                axisTick: { show: false },
                axisLine: { lineStyle: { color: INK.axis } },
                axisLabel: { color: INK.secondary, fontSize: 17, fontFamily: NUM_FONT, margin: 14 }
            },
            yAxis: {
                type: 'value',
                minInterval: 1,
                // 给最高柱留出标签空间,避免顶到图例上沿。
                max: function (value) {
                    return Math.ceil(value.max * 1.15);
                },
                axisLabel: { color: INK.secondary, fontSize: 14, fontFamily: NUM_FONT },
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
            },
            series: series
        });
        chart.resize();
        container.__careerDone = true;
    }

    // 球队详情页:历届最终阶段折线,冠军金点。数据由 _history_chart.php 内联输出。
    function initTeamHistory() {
        var container = byId('team-history-chart');
        if (!container || container.__teamDone) {
            return;
        }

        if (!hasEcharts() && !window.__echartsLoadFailed) {
            whenNearViewport(container, function () {
                loadEcharts(container.getAttribute('data-echarts-src'), function () {
                    initTeamHistory();
                });
            });
            return;
        }

        var dataEl = byId('team-history-data');
        var data = null;
        try {
            data = dataEl ? JSON.parse(dataEl.textContent || '') : null;
        } catch (e) {
            data = null;
        }
        var points = (data && data.points) || [];
        if (!points.length) {
            container.__teamDone = true;
            showEmpty(container, '暂无战绩数据');
            return;
        }
        if (!hasEcharts() || window.__echartsLoadFailed) {
            container.__teamDone = true;
            showEmpty(container, '图表组件加载失败，请检查网络后刷新页面。');
            return;
        }

        var chart = initChart(container);
        if (!chart) {
            return;
        }

        var years = points.map(function (point) {
            return String(point.year);
        });
        // 窄屏 11 个年份会叠在一起,隔一个显示;桌面仍显示全部。
        var compactAxis = container.clientWidth < 520;
        var chartPoints = points.map(function (point) {
            return {
                value: Math.max(0, Math.min(6, point.rank - 1)),
                symbolSize: point.champion ? 19 : 11,
                itemStyle: {
                    color: point.champion ? ACCENT_SERIES : PRIMARY_SERIES,
                    borderColor: INK.surface,
                    borderWidth: 2,
                    shadowBlur: point.champion ? 18 : 0,
                    shadowColor: 'rgba(224, 180, 87, .45)'
                },
                label: {
                    show: !!point.champion,
                    formatter: '冠军',
                    position: 'top',
                    color: ACCENT_SERIES,
                    fontSize: 14,
                    fontFamily: CN_FONT,
                    fontWeight: 'bold'
                }
            };
        });

        chart.setOption({
            animationDuration: ANIMATION_DURATION,
            grid: { left: 8, right: 22, top: 44, bottom: 10, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'line', lineStyle: { color: INK.axis, type: 'dashed' } },
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                formatter: function (params) {
                    var point = points[params[0].dataIndex];
                    if (!point) {
                        return '';
                    }
                    return '<strong>' + point.year + '</strong>'
                        + '<br>' + escapeHtml(point.stage) + (point.champion ? ' · 冠军' : '')
                        + '<br>' + point.played + ' 场 · ' + point.won + '-' + point.drawn + '-' + point.lost
                        + '<br>进 ' + point.goalsFor + ' / 失 ' + point.goalsAgainst;
                }
            },
            xAxis: {
                type: 'category',
                data: years,
                axisTick: { show: false },
                axisLine: { lineStyle: { color: INK.axis } },
                axisLabel: {
                    color: INK.secondary,
                    fontSize: compactAxis ? 12 : 16,
                    fontFamily: NUM_FONT,
                    margin: 12,
                    interval: compactAxis ? 1 : 0
                }
            },
            yAxis: {
                type: 'category',
                data: ['小组赛', '32 强', '16 强', '8 强', '半决赛', '三四名', '决赛'],
                axisTick: { show: false },
                axisLine: { show: false },
                axisLabel: { color: INK.secondary, fontSize: 16, fontFamily: CN_FONT, margin: 12 },
                splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
            },
            series: [{
                name: '最终阶段',
                type: 'line',
                data: chartPoints,
                symbol: 'circle',
                lineStyle: { color: PRIMARY_SERIES, width: 3 },
                labelLayout: { hideOverlap: true },
                emphasis: { focus: 'series' }
            }]
        });
        chart.resize();
        container.__teamDone = true;
    }

    // 比赛详情页:比分走势阶梯线 + 进球点。点球大战由下方时间线单独呈现。
    function initGameScore() {
        var container = byId('game-score-chart');
        if (!container || container.__gameDone) {
            return;
        }

        if (!hasEcharts() && !window.__echartsLoadFailed) {
            whenNearViewport(container, function () {
                loadEcharts(container.getAttribute('data-echarts-src'), function () {
                    initGameScore();
                });
            });
            return;
        }

        var dataEl = byId('game-score-data');
        var data = null;
        try {
            data = dataEl ? JSON.parse(dataEl.textContent || '') : null;
        } catch (e) {
            data = null;
        }
        var marks = (data && data.marks) || [];
        if (!marks.length) {
            container.__gameDone = true;
            showEmpty(container, '暂无比分走势');
            return;
        }
        if (!hasEcharts() || window.__echartsLoadFailed) {
            container.__gameDone = true;
            showEmpty(container, '图表组件加载失败，请检查网络后刷新页面。');
            return;
        }

        var chart = initChart(container);
        if (!chart) {
            return;
        }

        var homeColor = PRIMARY_SERIES;
        var awayColor = '#4B8FD6';
        var homeName = data.homeName;
        var awayName = data.awayName;
        var maxMinute = Number(data.maxMinute) || 90;
        var lastMark = marks[marks.length - 1] || {};
        var finalHome = Number(lastMark.homeScore) || 0;
        var finalAway = Number(lastMark.awayScore) || 0;
        var toPoints = function (rows) {
            return (rows || []).map(function (row) {
                return [Number(row[0]), Number(row[1])];
            });
        };
        var markData = marks.map(function (mark) {
            return {
                value: [Number(mark.minute), Number(mark.score)],
                event: mark,
                itemStyle: {
                    color: mark.team === 'home' ? homeColor : awayColor,
                    borderColor: INK.surface,
                    borderWidth: 2
                }
            };
        });

        chart.setOption({
            animationDuration: ANIMATION_DURATION,
            grid: { left: 8, right: 22, top: 42, bottom: 10, containLabel: true },
            legend: {
                data: [homeName, awayName],
                top: 0,
                right: 0,
                icon: 'roundRect',
                itemWidth: 14,
                itemHeight: 9,
                textStyle: { color: INK.secondary, fontSize: 16, fontFamily: CN_FONT },
                formatter: function (name) {
                    return name + '  ' + (name === homeName ? finalHome : finalAway);
                }
            },
            tooltip: {
                trigger: 'item',
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 14, fontFamily: CN_FONT },
                formatter: function (params) {
                    var mark = params.data && params.data.event;
                    if (!mark) {
                        return '';
                    }
                    var tags = [];
                    if (mark.penalty) {
                        tags.push('点球');
                    }
                    if (mark.ownGoal) {
                        tags.push('乌龙球');
                    }
                    return '<strong>第 ' + mark.minute + ' 分钟</strong><br>'
                        + escapeHtml(mark.player)
                        + (tags.length ? '（' + tags.join(' · ') + '）' : '')
                        + '<br>' + escapeHtml(homeName) + ' ' + mark.homeScore
                        + '–' + mark.awayScore + ' ' + escapeHtml(awayName);
                }
            },
            xAxis: {
                type: 'value',
                min: 0,
                max: maxMinute,
                interval: maxMinute > 90 ? 30 : 15,
                axisTick: { show: false },
                axisLine: { lineStyle: { color: INK.axis } },
                axisLabel: {
                    color: INK.secondary,
                    fontSize: 16,
                    fontFamily: NUM_FONT,
                    margin: 12,
                    formatter: function (value) {
                        return value + '′';
                    }
                },
                splitLine: { show: false }
            },
            yAxis: {
                type: 'value',
                min: 0,
                minInterval: 1,
                axisTick: { show: false },
                axisLine: { show: false },
                axisLabel: { color: INK.secondary, fontSize: 15, fontFamily: NUM_FONT },
                splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
            },
            series: [
                {
                    name: homeName,
                    type: 'line',
                    step: 'end',
                    data: toPoints(data.homeSeries),
                    symbol: 'none',
                    lineStyle: { color: homeColor, width: 3 },
                    silent: true,
                    tooltip: { show: false },
                    markArea: {
                        silent: true,
                        label: {
                            show: true,
                            position: 'insideTop',
                            color: INK.muted,
                            fontSize: 13,
                            fontFamily: CN_FONT
                        },
                        data: (function () {
                            var areas = [
                                [{
                                    xAxis: 0,
                                    itemStyle: { color: 'rgba(244, 242, 236, .025)' },
                                    label: { formatter: '上半场' }
                                }, { xAxis: 45 }],
                                [{
                                    xAxis: 45,
                                    itemStyle: { color: 'rgba(244, 242, 236, .055)' },
                                    label: { formatter: '下半场' }
                                }, { xAxis: 90 }]
                            ];
                            if (maxMinute > 90) {
                                areas.push([{
                                    xAxis: 90,
                                    itemStyle: { color: 'rgba(224, 180, 87, .07)' },
                                    label: { formatter: '加时', color: ACCENT_SERIES }
                                }, { xAxis: maxMinute }]);
                            }
                            return areas;
                        }())
                    }
                },
                {
                    name: awayName,
                    type: 'line',
                    step: 'end',
                    data: toPoints(data.awaySeries),
                    symbol: 'none',
                    lineStyle: { color: awayColor, width: 3 },
                    silent: true,
                    tooltip: { show: false }
                },
                {
                    name: '进球',
                    type: 'scatter',
                    data: markData,
                    symbolSize: 16,
                    z: 10,
                    emphasis: { scale: 1.35 },
                    tooltip: { show: true }
                }
            ]
        });
        chart.resize();
        container.__gameDone = true;
    }

    function resizeAll() {
        Object.keys(chartInstances).forEach(function (id) {
            var chart = chartInstances[id];
            if (chart && !chart.isDisposed()) {
                chart.resize();
            }
        });
    }

    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(resizeAll, 150);
    });

    var chartsInitialized = false;

    function isVisible(element) {
        return !!(element && (element.offsetWidth || element.offsetHeight || element.getClientRects().length));
    }

    function init() {
        var root = byId('charts-panel');
        if (!root || chartsInitialized || !isVisible(root)) {
            return;
        }

        // 首次进入统计面板时才加载 ECharts,加载完再走一遍。
        if (!hasEcharts() && !window.__echartsLoadFailed) {
            loadEcharts(root.getAttribute('data-echarts-src'), function () {
                init();
            });
            return;
        }

        chartsInitialized = true;

        var containers = root.querySelectorAll('[data-chart]');
        if (!hasEcharts() || window.__echartsLoadFailed) {
            Array.prototype.forEach.call(containers, function (container) {
                showEmpty(container, '图表组件加载失败，请检查网络后刷新页面。');
            });
            return;
        }

        if (typeof window.Promise === 'undefined' || typeof window.fetch !== 'function') {
            Array.prototype.forEach.call(containers, function (container) {
                showEmpty(container, '当前浏览器版本过低，无法加载图表。');
            });
            return;
        }

        var tournamentId = root.getAttribute('data-tournament-id');
        if (!tournamentId) {
            Array.prototype.forEach.call(containers, function (container) {
                showEmpty(container, '缺少届次参数，无法加载图表。');
            });
            return;
        }

        var group = root.getAttribute('data-group') || '';
        var goalTimingUrl = buildApiUrl(root.getAttribute('data-goal-timing-url'), tournamentId, '');
        var standingsUrl = buildApiUrl(root.getAttribute('data-standings-url'), tournamentId, group);
        var scorersUrl = buildApiUrl(root.getAttribute('data-scorers-url'), tournamentId, '');
        var teamGoalsUrl = buildApiUrl(root.getAttribute('data-team-goals-url'), tournamentId, '');

        // 大图是「进球时间」或「小组积分榜」二选一：只请求当前存在的那张，
        // 另一张保留为视图切换，不再默认铺开。
        if (byId('chart-goal-timing')) {
            if (!goalTimingUrl) {
                failChart('chart-goal-timing', 'table-goal-timing', '统计接口地址缺失，无法加载图表。');
            } else {
                fetchJson(goalTimingUrl).then(renderGoalTiming).catch(function () {
                    failChart('chart-goal-timing', 'table-goal-timing', '进球时间分布加载失败，请稍后重试。');
                });
            }
        }

        if (byId('chart-standings')) {
            if (!standingsUrl) {
                failChart('chart-standings', 'table-standings', '统计接口地址缺失，无法加载图表。');
            } else {
                fetchJson(standingsUrl).then(renderStandings).catch(function () {
                    failChart('chart-standings', 'table-standings', '小组积分榜加载失败，请稍后重试。');
                });
            }
        }

        if (byId('chart-scorers')) {
            if (!scorersUrl) {
                failChart('chart-scorers', 'table-scorers', '统计接口地址缺失，无法加载图表。');
            } else {
                fetchJson(scorersUrl).then(renderScorers).catch(function () {
                    failChart('chart-scorers', 'table-scorers', '射手榜加载失败，请稍后重试。');
                });
            }
        }

        if (byId('chart-team-goals')) {
            if (!teamGoalsUrl) {
                failChart('chart-team-goals', 'table-team-goals', '统计接口地址缺失，无法加载图表。');
            } else {
                fetchJson(teamGoalsUrl).then(renderTeamGoals).catch(function () {
                    failChart('chart-team-goals', 'table-team-goals', '各队进球分布加载失败，请稍后重试。');
                });
            }
        }
    }

    // 桌面端数据表以覆盖层展开,由脚本提供一个可见的关闭按钮。
    function initChartDataToggles() {
        Array.prototype.forEach.call(document.querySelectorAll('.chart-data'), function (details) {
            details.addEventListener('toggle', function () {
                var card = details.closest('.chart-card');
                if (!card) {
                    return;
                }
                var button = card.querySelector('.chart-data-close');
                if (details.open) {
                    if (!button) {
                        button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'chart-data-close';
                        button.textContent = '收起数据表，回到图表';
                        button.addEventListener('click', function () {
                            details.open = false;
                        });
                        card.appendChild(button);
                    }
                } else if (button) {
                    button.remove();
                }
            });
        });
    }

    // 统计面板默认隐藏;tab 切到 #charts 后由页面脚本派发事件,再懒加载图表。
    window.addEventListener('worldcup:charts-shown', function () {
        if (!chartsInitialized) {
            init();
        } else {
            window.setTimeout(resizeAll, 60);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initChartDataToggles();
            init();
            initPlayerCareer();
            initTeamHistory();
            initGameScore();
        });
    } else {
        initChartDataToggles();
        init();
        initPlayerCareer();
        initTeamHistory();
        initGameScore();
    }
}());

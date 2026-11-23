/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261115
   模块:届次与比赛 —— 积分榜、射手榜、各队进球分布的 ECharts 渲染。 */
(function () {
    'use strict';

    var INK = {
        primary: '#0B1A22',
        secondary: '#33454C',
        muted: '#5F6B66',
        grid: '#D3DBD6',
        axis: '#8A9AA1',
        surface: '#F5F7F4'
    };

    // 冷纸底六色:金只给第一名,其余用深绿到灰的五色;红不进入图表。
    var TEAM_COLORS = ['#146B45', '#4F8A6B', '#7FC8A9', '#8A9AA1', '#33454C'];
    var PRIMARY_SERIES = '#146B45';
    var HOVER_SERIES = '#0E3B2E';
    var ACCENT_SERIES = '#C9A227';
    var OTHER_COLOR = '#8A9AA1';

    var chartInstances = {};
    var resizeTimer = null;

    function byId(id) {
        return document.getElementById(id);
    }

    function hasEcharts() {
        return typeof window.echarts !== 'undefined'
            && typeof window.echarts.init === 'function';
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

    // 球员名统一为「首字母大写、其余小写」：Kylian MBAPPE -> Kylian Mbappe。
    // 同时处理空格、连字符与撇号后的首字母，兼容 Heung-min、O'Neill 等写法。
    // 与 PHP 端 wc_player_name cookie 保持一致:默认中文名,选择原名后全部回退。
    function prefersOriginalName() {
        return /(?:^|;\s*)wc_player_name=orig(?:;|$)/.test(document.cookie);
    }

    // 有中文名优先显示中文名;没有或访客选择原名时回退原名。
    function displayPlayerName(row) {
        if (!prefersOriginalName() && row && row.playerNameZh) {
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
    function estimateTextWidth(text) {
        var width = 0;
        var value = String(text === null || text === undefined ? '' : text);
        for (var i = 0; i < value.length; i += 1) {
            width += value.charCodeAt(i) > 0x2e7f ? 11 : 6.2;
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
                animationDuration: 500,
                grid: { left: 16, right: 20, top: 36, bottom: 18, containLabel: true },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: INK.surface,
                    borderColor: INK.grid,
                    textStyle: { color: INK.primary, fontSize: 13 },
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
                    type: 'category',
                    data: categories,
                    axisTick: { show: false },
                    axisLine: { lineStyle: { color: INK.axis } },
                    axisLabel: {
                        interval: 0,
                        color: INK.secondary,
                        fontSize: 13,
                        margin: 10,
                        // 单组最多 4 支球队,横轴标签不旋转即可完整显示。
                        rotate: categories.length > 6 ? 30 : 0
                    }
                },
                yAxis: {
                    type: 'value',
                    name: '积分',
                    nameTextStyle: { color: INK.muted, fontSize: 13, padding: [0, 0, 4, 0] },
                    minInterval: 1,
                    axisLabel: { color: INK.secondary, fontSize: 13 },
                    axisLine: { show: false },
                    axisTick: { show: false },
                    splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
                },
                series: [{
                    name: '积分',
                    type: 'bar',
                    data: values,
                    barMaxWidth: 48,
                    itemStyle: {
                        color: function (params) {
                            return groupLeaders[params.dataIndex] ? ACCENT_SERIES : PRIMARY_SERIES;
                        },
                        borderRadius: [4, 4, 0, 0]
                    },
                    label: {
                        show: true,
                        position: 'top',
                        color: INK.primary,
                        fontSize: 13,
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

        var names = chartRows.map(function (row) {
            return displayPlayerName(row) + ' · ' + row.teamName;
        });
        // y 轴保留完整「姓名 · 球队」,按最长标签动态留宽,避免截断。
        var maxNameWidth = names.reduce(function (maxWidth, name) {
            return Math.max(maxWidth, estimateTextWidth(name));
        }, 0);
        var yLabelWidth = Math.min(220, Math.max(132, Math.ceil(maxNameWidth) + 10));
        var goals = chartRows.map(function (row) {
            return Number(row.goals) || 0;
        });
        // 只有射手榜第一名使用金色,其余使用深绿。
        var leaderIndex = chartRows.length - 1;

        var chart = initChart(byId('chart-scorers'));
        if (!chart) {
            return;
        }

        chart.setOption({
            animationDuration: 500,
            grid: { left: 12, right: 44, top: 12, bottom: 8, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 13 },
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
                name: '进球',
                nameTextStyle: { color: INK.muted, fontSize: 13 },
                minInterval: 1,
                axisLabel: { color: INK.secondary, fontSize: 13 },
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
            },
            yAxis: {
                type: 'category',
                data: names,
                axisTick: { show: false },
                axisLine: { lineStyle: { color: INK.axis } },
                axisLabel: {
                    color: INK.secondary,
                    fontSize: 13,
                    width: yLabelWidth,
                    overflow: 'none'
                }
            },
            series: [{
                name: '进球数',
                type: 'bar',
                data: goals,
                barMaxWidth: 18,
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
                    fontSize: 13,
                    fontWeight: 'bold'
                },
                emphasis: { itemStyle: { color: HOVER_SERIES } }
            }]
        });
        chart.resize();
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
                goals: row.goals,
                percent: total > 0 ? Math.round((Number(row.goals) || 0) / total * 1000) / 10 : 0
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

        var pieData = teams.map(function (row) {
            return { name: row.teamName, value: Number(row.goals) || 0 };
        });

        // 超过 8 支球队时,饼图保留前 7 名 + 其他,避免颜色循环;
        // 完整明细仍保留在上方数据表中。
        if (pieData.length > 8) {
            var top = pieData.slice(0, 7);
            var otherTotal = pieData.slice(7).reduce(function (sum, item) {
                return sum + item.value;
            }, 0);
            top.push({ name: '其他球队', value: otherTotal, itemStyle: { color: OTHER_COLOR } });
            pieData = top;
        }

        pieData.forEach(function (item, index) {
            if (!item.itemStyle) {
                item.itemStyle = {
                    // 进球最多的球队用金色,其余按深绿到灰的五色循环。
                    color: index === 0
                        ? ACCENT_SERIES
                        : TEAM_COLORS[(index - 1) % TEAM_COLORS.length]
                };
            }
        });

        var chart = initChart(byId('chart-team-goals'));
        if (!chart) {
            return;
        }

        chart.setOption({
            animationDuration: 500,
            title: {
                text: String(total),
                subtext: '总进球',
                left: 'center',
                top: '32%',
                textAlign: 'center',
                textStyle: { color: INK.primary, fontSize: 26, fontWeight: 'bold' },
                subtextStyle: { color: INK.muted, fontSize: 13 }
            },
            tooltip: {
                trigger: 'item',
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 13 },
                formatter: function (params) {
                    return '<strong>' + escapeHtml(params.name) + '</strong>'
                        + '<br>进球：' + params.value
                        + '（' + params.percent + '%）';
                }
            },
            legend: {
                type: 'scroll',
                bottom: 0,
                left: 'center',
                icon: 'circle',
                itemWidth: 10,
                itemHeight: 10,
                textStyle: { color: INK.secondary, fontSize: 13 }
            },
            series: [{
                name: '各队进球',
                type: 'pie',
                radius: ['40%', '62%'],
                center: ['50%', '42%'],
                avoidLabelOverlap: true,
                itemStyle: {
                    borderColor: INK.surface,
                    borderWidth: 2,
                    borderRadius: 2
                },
                label: {
                    show: true,
                    formatter: function (params) {
                        return params.percent >= 4
                            ? params.name + '\n' + params.value + ' 球'
                            : '';
                    },
                    color: INK.secondary,
                    fontSize: 13,
                    lineHeight: 16
                },
                labelLine: {
                    length: 8,
                    length2: 6,
                    lineStyle: { color: INK.axis }
                },
                labelLayout: { hideOverlap: true },
                emphasis: {
                    scale: true,
                    scaleSize: 4,
                    label: { fontWeight: 'bold' }
                },
                data: pieData
            }]
        });
        chart.resize();
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
        var standingsUrl = buildApiUrl(root.getAttribute('data-standings-url'), tournamentId, group);
        var scorersUrl = buildApiUrl(root.getAttribute('data-scorers-url'), tournamentId, '');
        var teamGoalsUrl = buildApiUrl(root.getAttribute('data-team-goals-url'), tournamentId, '');

        if (!standingsUrl || !scorersUrl || !teamGoalsUrl) {
            failChart('chart-standings', 'table-standings', '统计接口地址缺失，无法加载图表。');
            failChart('chart-scorers', 'table-scorers', '统计接口地址缺失，无法加载图表。');
            failChart('chart-team-goals', 'table-team-goals', '统计接口地址缺失，无法加载图表。');
            return;
        }

        // 三个请求并行发出;单个失败不影响另外两个图表。
        fetchJson(standingsUrl).then(renderStandings).catch(function () {
            failChart('chart-standings', 'table-standings', '积分榜加载失败，请稍后重试。');
        });

        fetchJson(scorersUrl).then(renderScorers).catch(function () {
            failChart('chart-scorers', 'table-scorers', '射手榜加载失败，请稍后重试。');
        });

        fetchJson(teamGoalsUrl).then(renderTeamGoals).catch(function () {
            failChart('chart-team-goals', 'table-team-goals', '各队进球分布加载失败，请稍后重试。');
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
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());

/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261115
   模块:届次与比赛 —— 积分榜、射手榜、各队进球分布的 ECharts 渲染。 */
(function () {
    'use strict';

    var INK = {
        primary: '#182d27',
        secondary: '#52514e',
        muted: '#898781',
        grid: '#e1e0d9',
        axis: '#c3c2b7',
        surface: '#ffffff'
    };

    // 分类色顺序经 dataviz validate_palette.js 在白色背景下校验通过。
    var CATEGORICAL = [
        '#2a78d6', '#eb6834', '#1baf7a', '#eda100',
        '#e87ba4', '#008300', '#6250d6', '#e34948'
    ];
    var PRIMARY_SERIES = '#2a78d6';
    var HOVER_SERIES = '#256abf';
    var OTHER_COLOR = '#898781';

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

        var categories = [];
        var values = [];
        var tooltipRows = [];
        groups.forEach(function (group) {
            var groupLabel = group.group === '未分组' ? group.group : group.group + '组';
            (group.rows || []).forEach(function (row) {
                categories.push((group.group ? groupLabel : '') + '\n' + row.teamName);
                values.push(Number(row.points) || 0);
                tooltipRows.push(Object.assign({ group: group.group }, row));
            });
        });

        var chart = initChart(byId('chart-standings'));
        if (!chart) {
            return;
        }

        chart.setOption({
            animationDuration: 500,
            grid: { left: 16, right: 20, top: 36, bottom: 18, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 12 },
                formatter: function (params) {
                    var row = tooltipRows[params[0].dataIndex];
                    if (!row) {
                        return '';
                    }
                    return '<strong>' + escapeHtml(row.teamName) + '</strong>'
                        + '<br>小组：' + escapeHtml(row.group)
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
                    fontSize: 11,
                    lineHeight: 15,
                    rotate: categories.length > 10 ? 30 : 0
                }
            },
            yAxis: {
                type: 'value',
                name: '积分',
                nameTextStyle: { color: INK.muted, fontSize: 11, padding: [0, 0, 4, 0] },
                minInterval: 1,
                axisLabel: { color: INK.secondary, fontSize: 11 },
                axisLine: { show: false },
                axisTick: { show: false },
                splitLine: { lineStyle: { color: INK.grid, width: 1, type: 'solid' } }
            },
            series: [{
                name: '积分',
                type: 'bar',
                data: values,
                barMaxWidth: 24,
                itemStyle: { color: PRIMARY_SERIES, borderRadius: [4, 4, 0, 0] },
                label: {
                    show: true,
                    position: 'top',
                    color: INK.primary,
                    fontSize: 11,
                    fontWeight: 'bold'
                },
                emphasis: { itemStyle: { color: HOVER_SERIES } }
            }]
        });
    }

    function renderScorers(data) {
        var scorers = (data && data.scorers) || [];
        var chartRows = scorers.slice().reverse();

        renderTable(byId('table-scorers'), [
            { label: '排名', value: function (row) { return row.rank; }, numeric: true },
            { label: '球员', value: function (row) { return row.playerName; } },
            { label: '球队', value: function (row) { return row.teamName; } },
            { label: '进球', value: function (row) { return row.goals; }, numeric: true }
        ], scorers);

        if (!scorers.length) {
            showEmpty(byId('chart-scorers'), '暂无数据');
            return;
        }

        var names = chartRows.map(function (row) {
            return row.playerName + ' · ' + row.teamName;
        });
        var goals = chartRows.map(function (row) {
            return Number(row.goals) || 0;
        });

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
                textStyle: { color: INK.primary, fontSize: 12 },
                formatter: function (params) {
                    var row = chartRows[params[0].dataIndex];
                    if (!row) {
                        return '';
                    }
                    return '<strong>' + escapeHtml(row.playerName) + '</strong>'
                        + '<br>球队：' + escapeHtml(row.teamName)
                        + '<br>进球：' + row.goals;
                }
            },
            xAxis: {
                type: 'value',
                name: '进球',
                nameTextStyle: { color: INK.muted, fontSize: 11 },
                minInterval: 1,
                axisLabel: { color: INK.secondary, fontSize: 11 },
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
                    fontSize: 11,
                    width: 108,
                    overflow: 'truncate'
                }
            },
            series: [{
                name: '进球数',
                type: 'bar',
                data: goals,
                barMaxWidth: 18,
                itemStyle: { color: PRIMARY_SERIES, borderRadius: [0, 4, 4, 0] },
                label: {
                    show: true,
                    position: 'right',
                    color: INK.primary,
                    fontSize: 11,
                    fontWeight: 'bold'
                },
                emphasis: { itemStyle: { color: HOVER_SERIES } }
            }]
        });
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
                item.itemStyle = { color: CATEGORICAL[index % CATEGORICAL.length] };
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
                subtextStyle: { color: INK.muted, fontSize: 11 }
            },
            tooltip: {
                trigger: 'item',
                backgroundColor: INK.surface,
                borderColor: INK.grid,
                textStyle: { color: INK.primary, fontSize: 12 },
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
                textStyle: { color: INK.secondary, fontSize: 11 }
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
                    fontSize: 11,
                    lineHeight: 15
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

    function init() {
        var root = byId('charts-panel');
        if (!root) {
            return;
        }

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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());

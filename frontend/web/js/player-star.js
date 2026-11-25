/* Team: 国足世界杯夺冠队
   Coding by 李行阳 2411353, 20261125
   模块:届次与比赛 —— 四十年球员星云
   D3 7.9 (ISC) 在统计面板第一次可见时才注入；数据来自 /player-star/data。
   采用二维点云（x=生涯进球，y=参赛届数/助攻）：
   离散的进球数如果只做一维蜂群会变成一根根“梳子”，二维才像星云。
   星点用径向渐变画成软边光斑，再以 screen 叠加；密集处自然更亮。
   该届有进球的球员更亮，其余退后——亮度本身就是“这一届谁进球”的编码。
   灵感来自 The Pudding 的单位图与 glow 点云的叠加做法；代码为本站 D3 实现。 */
(function () {
    'use strict';

    var root = document.getElementById('player-star');
    if (!root) {
        return;
    }

    var stage = root.querySelector('.player-star-stage');
    var loading = root.querySelector('.player-star-loading');
    var tip = root.querySelector('.player-star-tip');
    var playBtn = root.querySelector('.player-star-play');
    var resetBtn = root.querySelector('.player-star-reset');
    var yearInput = root.querySelector('#player-star-year');
    var yearOut = root.querySelector('.player-star-year');
    var yBtns = root.querySelectorAll('.player-star-mode button');
    var ghostYear = root.querySelector('[data-star-ghost-year]');

    var prefersReducedMotion = !!(window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var duration = prefersReducedMotion ? 0 : 700;
    var isNarrow = function () {
        return window.matchMedia('(max-width: 767px)').matches;
    };

    var CONTINENT_COLORS = {
        '欧洲': '#9FB3AD',
        '南美': '#57B894',
        '南美洲': '#57B894',
        '非洲': '#C97A4A',
        '亚洲': '#7FA8C9',
        '中北美': '#9C8FC4',
        '大洋洲': '#6E8389',
        '其他': '#6E8389'
    };
    var GOLD = '#E0B457';
    var INK = {
        primary: '#F4F2EC',
        secondary: '#D7DFDA',
        muted: '#A9BAB2',
        grid: 'rgba(244, 242, 236, .09)',
        axis: '#8FA29B'
    };

    var state = {
        data: null,
        yMode: 'editions',
        index: 0,
        playing: false,
        timer: null,
        svg: null
    };

    var scriptLoads = {};

    function loadScript(src, key, done) {
        if (window[key]) {
            done(true);
            return;
        }
        if (scriptLoads[src]) {
            scriptLoads[src].push(done);
            return;
        }
        scriptLoads[src] = [done];
        var script = document.createElement('script');
        script.async = true;
        script.src = src;
        script.onload = function () {
            var callbacks = scriptLoads[src] || [];
            delete scriptLoads[src];
            callbacks.forEach(function (cb) { cb(true); });
        };
        script.onerror = function () {
            var callbacks = scriptLoads[src] || [];
            delete scriptLoads[src];
            callbacks.forEach(function (cb) { cb(false); });
        };
        document.head.appendChild(script);
    }

    function startOnce() {
        if (state.data || scriptLoads.__started) {
            return;
        }
        scriptLoads.__started = true;
        loadScript(root.dataset.d3Src, 'd3', function (ok) {
            if (!ok || typeof window.d3 === 'undefined') {
                showLoadError();
                return;
            }
            fetch(root.dataset.starUrl, { credentials: 'same-origin' })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (data) {
                    state.data = data;
                    init();
                })
                .catch(function () {
                    showLoadError();
                });
        });
    }

    function showLoadError() {
        if (loading) {
            loading.textContent = '星云加载失败，请刷新页面重试。';
        }
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    startOnce();
                    observer.disconnect();
                }
            });
        }, { rootMargin: '240px 0px' });
        observer.observe(root);
    } else {
        startOnce();
    }
    document.addEventListener('worldcup:charts-shown', startOnce);

    function init() {
        var years = state.data.years || [];
        if (!years.length) {
            showLoadError();
            return;
        }
        state.index = years.length - 1;
        yearInput.min = String(years[0]);
        yearInput.max = String(years[years.length - 1]);
        yearInput.step = years.length > 1 ? String(years[1] - years[0]) : '1';
        yearInput.value = String(years[state.index]);
        yearOut.textContent = String(years[state.index]);

        if (loading) {
            loading.remove();
        }

        bindControls();
        render(true);
    }

    function bindControls() {
        playBtn.addEventListener('click', togglePlay);
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                pause();
                setIndex(state.data.years.length - 1);
            });
        }
        yearInput.addEventListener('input', function () {
            pause();
            var value = Number(yearInput.value);
            var best = 0;
            var bestDiff = Infinity;
            state.data.years.forEach(function (year, index) {
                var diff = Math.abs(year - value);
                if (diff < bestDiff) {
                    bestDiff = diff;
                    best = index;
                }
            });
            setIndex(best);
        });
        Array.prototype.forEach.call(yBtns, function (button) {
            button.addEventListener('click', function () {
                var mode = button.getAttribute('data-y');
                if (mode === state.yMode) {
                    return;
                }
                state.yMode = mode;
                Array.prototype.forEach.call(yBtns, function (other) {
                    other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
                });
                render(false);
            });
        });
    }

    function togglePlay() {
        if (state.playing) {
            pause();
        } else {
            if (state.index >= state.data.years.length - 1) {
                setIndex(0);
            }
            state.playing = true;
            playBtn.setAttribute('aria-pressed', 'true');
            playBtn.textContent = '暂停';
            state.timer = window.setInterval(function () {
                if (state.index >= state.data.years.length - 1) {
                    pause();
                    return;
                }
                setIndex(state.index + 1);
            }, 900);
        }
    }

    function pause() {
        state.playing = false;
        playBtn.setAttribute('aria-pressed', 'false');
        playBtn.textContent = '播放四十年';
        if (state.timer) {
            window.clearInterval(state.timer);
            state.timer = null;
        }
    }

    function setIndex(index) {
        state.index = Math.max(0, Math.min(state.data.years.length - 1, index));
        yearInput.value = String(state.data.years[state.index]);
        yearOut.textContent = String(state.data.years[state.index]);
        render(false);
    }

    function sumUntil(values, index) {
        var total = 0;
        for (var i = 0; i <= index && i < values.length; i++) {
            total += Number(values[i]) || 0;
        }
        return total;
    }

    function xValue(player) {
        return sumUntil(player.g, state.index);
    }

    function yValue(player) {
        if (state.yMode === 'assists') {
            return sumUntil(player.a, state.index);
        }
        // 参赛届数按年累计；s 是 1/0 的参赛标记。
        return sumUntil(player.s || [], state.index);
    }

    function scoredInEdition(player, index) {
        return Number(player.g && player.g[index]) > 0;
    }

    function threshold() {
        // 口径与页面注一致：所有宽度都只画生涯 ≥2 球的球员。
        return 2;
    }

    function yLabel() {
        return state.yMode === 'assists' ? '生涯助攻' : '参赛届数';
    }

    function playerLink(player) {
        var base = root.dataset.playerUrl || '/index.php?r=player/view';
        return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(player.id);
    }

    function prefersOriginalName() {
        return !/(?:^|;\s*)wc_player_name_v2=zh(?:;|$)/.test(document.cookie);
    }

    function playerName(player) {
        if (player.name_zh && !prefersOriginalName()) {
            return player.name_zh;
        }
        return player.name || player.name_zh || '待补';
    }

    function playerFullName(player) {
        var first = playerName(player);
        var other = prefersOriginalName() ? player.name_zh : player.name;
        if (other && other !== first) {
            return first + '（' + other + '）';
        }
        return first;
    }

    function hashUnit(n) {
        var x = Math.sin(Number(n) * 127.1 + 311.7) * 43758.5453;
        return x - Math.floor(x);
    }

    function continentKey(name) {
        if (name === '南美' || name === '南美洲') {
            return 'sa';
        }
        if (name === '中北美') {
            return 'na';
        }
        if (name === '欧洲') {
            return 'eu';
        }
        if (name === '非洲') {
            return 'af';
        }
        if (name === '亚洲') {
            return 'as';
        }
        return 'other';
    }

    function starFill(player, rank) {
        if (rank === 0) {
            return 'url(#ps-star-gold)';
        }
        return 'url(#ps-star-' + continentKey(player.continent) + ')';
    }

    function glowFill(player, rank) {
        if (rank === 0) {
            return 'url(#ps-glow-gold)';
        }
        return 'url(#ps-glow-' + continentKey(player.continent) + ')';
    }

    // 星核：中心一颗小而实的白点，压住径向渐变的模糊感。
    function coreFill(player, rank) {
        return rank === 0 ? '#FFF6D8' : 'rgba(247, 251, 248, .95)';
    }

    // 软边星点：中心是提亮过的核，边缘透明；screen 叠加后密集区自然发亮。
    function buildStarDefs(d3, defs) {
        var colors = {
            eu: CONTINENT_COLORS['欧洲'],
            sa: CONTINENT_COLORS['南美'],
            af: CONTINENT_COLORS['非洲'],
            as: CONTINENT_COLORS['亚洲'],
            na: CONTINENT_COLORS['中北美'],
            other: CONTINENT_COLORS['其他']
        };
        Object.keys(colors).forEach(function (key) {
            var base = colors[key];
            var core = d3.color(base).brighter(0.7).formatHex();
            var grad = defs.append('radialGradient')
                .attr('id', 'ps-star-' + key)
                .attr('cx', '50%')
                .attr('cy', '50%')
                .attr('r', '50%');
            // 中心更实、外缘更快落到 0：星点清楚，光晕只留在外围。
            grad.append('stop').attr('offset', '0%').attr('stop-color', core).attr('stop-opacity', 1);
            grad.append('stop').attr('offset', '24%').attr('stop-color', core).attr('stop-opacity', .96);
            grad.append('stop').attr('offset', '48%').attr('stop-color', base).attr('stop-opacity', .9);
            grad.append('stop').attr('offset', '72%').attr('stop-color', base).attr('stop-opacity', .28);
            grad.append('stop').attr('offset', '100%').attr('stop-color', base).attr('stop-opacity', 0);

            // 外围雾光：半径约为星点 2.6 倍，alpha 很低；点越密叠加越亮。
            var glow = defs.append('radialGradient')
                .attr('id', 'ps-glow-' + key)
                .attr('cx', '50%')
                .attr('cy', '50%')
                .attr('r', '50%');
            glow.append('stop').attr('offset', '0%').attr('stop-color', base).attr('stop-opacity', .12);
            glow.append('stop').attr('offset', '46%').attr('stop-color', base).attr('stop-opacity', .055);
            glow.append('stop').attr('offset', '100%').attr('stop-color', base).attr('stop-opacity', 0);
        });

        var gold = defs.append('radialGradient')
            .attr('id', 'ps-star-gold')
            .attr('cx', '50%')
            .attr('cy', '50%')
            .attr('r', '50%');
        gold.append('stop').attr('offset', '0%').attr('stop-color', '#FFF6D8').attr('stop-opacity', 1);
        gold.append('stop').attr('offset', '26%').attr('stop-color', GOLD).attr('stop-opacity', .95);
        gold.append('stop').attr('offset', '62%').attr('stop-color', GOLD).attr('stop-opacity', .34);
        gold.append('stop').attr('offset', '100%').attr('stop-color', GOLD).attr('stop-opacity', 0);

        var goldGlow = defs.append('radialGradient')
            .attr('id', 'ps-glow-gold')
            .attr('cx', '50%')
            .attr('cy', '50%')
            .attr('r', '50%');
        goldGlow.append('stop').attr('offset', '0%').attr('stop-color', GOLD).attr('stop-opacity', .22);
        goldGlow.append('stop').attr('offset', '48%').attr('stop-color', GOLD).attr('stop-opacity', .09);
        goldGlow.append('stop').attr('offset', '100%').attr('stop-color', GOLD).attr('stop-opacity', 0);

        var halo = defs.append('radialGradient')
            .attr('id', 'ps-halo-gold')
            .attr('cx', '50%')
            .attr('cy', '50%')
            .attr('r', '50%');
        halo.append('stop').attr('offset', '0%').attr('stop-color', GOLD).attr('stop-opacity', .24);
        halo.append('stop').attr('offset', '46%').attr('stop-color', GOLD).attr('stop-opacity', .10);
        halo.append('stop').attr('offset', '100%').attr('stop-color', GOLD).attr('stop-opacity', 0);
    }

    function render(first) {
        if (!state.data || !window.d3) {
            return;
        }
        var d3 = window.d3;
        var years = state.data.years;
        var year = years[state.index];
        if (ghostYear) {
            ghostYear.textContent = String(year);
        }

        var all = [];
        state.data.players.forEach(function (player) {
            var xv = xValue(player);
            if (xv > 0) {
                all.push({ player: player, xv: xv, yv: yValue(player) });
            }
        });
        var limit = threshold();
        var shown = all.filter(function (row) { return row.xv >= limit; });
        if (shown.length < 8) {
            limit = 1;
            shown = all;
        }

        var sorted = all.slice().sort(function (a, b) {
            return b.xv - a.xv || b.player.assists - a.player.assists || b.yv - a.yv;
        });
        var rankById = {};
        sorted.forEach(function (row, rank) {
            rankById[row.player.id] = rank;
        });

        // 坐标轴用全时段最大值，播放时轴不跳；空行也能看出“后来者还在往上走”。
        var maxX = 1;
        var maxY = state.yMode === 'assists' ? 1 : 1;
        state.data.players.forEach(function (player) {
            maxX = Math.max(maxX, Number(player.goals) || 0);
            if (state.yMode === 'assists') {
                maxY = Math.max(maxY, Number(player.assists) || 0);
            } else {
                maxY = Math.max(maxY, Number(player.editions) || 0);
            }
        });
        maxX = Math.max(maxX, state.yMode === 'assists' ? 6 : 10);
        maxY = state.yMode === 'assists' ? Math.max(3, maxY) : Math.max(5, maxY);

        var W = Math.max(320, stage.clientWidth || 1000);
        var H = isNarrow() ? 470 : 660;
        var m = {
            left: isNarrow() ? 38 : 52,
            right: isNarrow() ? 22 : 34,
            top: 22,
            bottom: isNarrow() ? 64 : 72
        };
        var plotBottom = H - m.bottom;
        // 幽灵年份的底边贴住 x 轴；SVG 按 viewBox 等比缩放时一起换算。
        var renderedScale = stage.clientWidth ? Math.min(1, stage.clientWidth / W) : 1;
        stage.style.setProperty('--ps-axis-bottom', (m.bottom * renderedScale).toFixed(1) + 'px');

        var x = d3.scaleLinear().domain([0, maxX]).range([m.left, W - m.right]);
        // 助攻 0 也是有效值：上下各留半格，0 助的点不会压在 x 轴上。
        var yDomain = state.yMode === 'assists' ? [-0.5, maxY + 0.5] : [0.5, maxY + 0.5];
        var y = d3.scaleLinear().domain(yDomain).range([plotBottom, m.top]);
        var xStep = x(1) - x(0);
        var yStep = Math.abs(y(1) - y(0));

        if (!state.svg) {
            state.svg = d3.select(stage).append('svg')
                .attr('class', 'player-star-svg')
                .attr('role', 'presentation');
            buildStarDefs(d3, state.svg.append('defs'));
            state.svg.append('g').attr('class', 'ps-grid');
            state.svg.append('g').attr('class', 'ps-axis-x');
            state.svg.append('g').attr('class', 'ps-axis-y');
            state.svg.append('g').attr('class', 'ps-titles');
            state.svg.append('g').attr('class', 'ps-glows');
            state.svg.append('g').attr('class', 'ps-halos');
            state.svg.append('g').attr('class', 'ps-dots');
            state.svg.append('g').attr('class', 'ps-cores');
            state.svg.append('g').attr('class', 'ps-labels');
        }
        var svg = state.svg;
        svg.attr('viewBox', '0 0 ' + W + ' ' + H)
            .attr('width', W)
            .attr('height', H);

        var xTicks = x.ticks(isNarrow() ? 4 : 6);
        var yTicks = state.yMode === 'assists'
            ? y.ticks(Math.min(5, maxY))
            : d3.range(1, maxY + 1);

        // 网格：横线表达届数/助攻层，竖线表达进球刻度。
        var grid = svg.select('.ps-grid');
        var vLines = grid.selectAll('line.ps-grid-v').data(xTicks);
        vLines.enter().append('line').attr('class', 'ps-grid-v').merge(vLines)
            .attr('x1', function (d) { return x(d); })
            .attr('x2', function (d) { return x(d); })
            .attr('y1', m.top)
            .attr('y2', plotBottom)
            .attr('stroke', INK.grid);
        var hLines = grid.selectAll('line.ps-grid-h').data(yTicks);
        hLines.enter().append('line').attr('class', 'ps-grid-h').merge(hLines)
            .attr('x1', m.left)
            .attr('x2', W - m.right)
            .attr('y1', function (d) { return y(d); })
            .attr('y2', function (d) { return y(d); })
            .attr('stroke', INK.grid);

        // 坐标轴
        var axisX = svg.select('.ps-axis-x').attr('transform', 'translate(0,' + plotBottom + ')');
        axisX.call(d3.axisBottom(x).ticks(isNarrow() ? 4 : 6).tickFormat(d3.format('d')));
        var axisY = svg.select('.ps-axis-y').attr('transform', 'translate(' + m.left + ',0)');
        axisY.call(d3.axisLeft(y).tickValues(yTicks).tickFormat(d3.format('d')));
        [axisX, axisY].forEach(function (axis) {
            axis.selectAll('text')
                .attr('fill', INK.muted)
                .style('font-family', '"Saira Condensed", "Arial Narrow", sans-serif')
                .style('font-size', isNarrow() ? '13.5px' : '15px');
            axis.selectAll('line, path').attr('stroke', INK.axis);
        });

        var titles = svg.select('.ps-titles');
        var titleData = [
            { text: '生涯进球', x: (m.left + W - m.right) / 2, y: plotBottom + 34, anchor: 'middle' },
            { text: yLabel(), x: 12, y: (m.top + plotBottom) / 2, anchor: 'middle', rotate: -90 }
        ];
        var titlesSel = titles.selectAll('text').data(titleData);
        titlesSel.enter().append('text').merge(titlesSel)
            .attr('x', function (d) { return d.x; })
            .attr('y', function (d) { return d.y; })
            .attr('text-anchor', function (d) { return d.anchor; })
            .attr('transform', function (d) {
                return d.rotate ? 'rotate(' + d.rotate + ' ' + d.x + ' ' + d.y + ')' : null;
            })
            .attr('fill', INK.muted)
            .style('font-size', isNarrow() ? '13.5px' : '15px')
            .text(function (d) { return d.text; });

        // 点云：x=生涯进球，y=届数/助攻，确定性抖动避免点完全重叠。
        // 前两名不抖动，让标签和点严格对齐。
        function dotX(row) {
            return x(row.xv) + (rankById[row.player.id] < 2
                ? 0
                : (hashUnit(row.player.id) - 0.5) * xStep * 0.72);
        }
        function dotY(row) {
            return y(row.yv) + (rankById[row.player.id] < 2
                ? 0
                : (hashUnit(row.player.id + 13) - 0.5) * yStep * 0.46);
        }

        // 普通星点保持小而弱，领跑者更大；密集处靠 screen 叠加发亮。
        function radiusOf(row) {
            var rank = rankById[row.player.id];
            if (rank === 0) {
                return isNarrow() ? 8.0 : 7.6;
            }
            if (rank < 10) {
                return isNarrow() ? 5.6 : 5.2;
            }
            return (isNarrow() ? 4.0 : 3.6) + hashUnit(row.player.id * 3.7) * (isNarrow() ? 1.1 : 1.0);
        }

        // 每颗星先铺一层很大的低透明雾光，密集处叠加成星云；
        // 该届有进球的更亮，和星点本身的亮度编码一致。
        var glows = svg.select('.ps-glows').selectAll('circle').data(shown, function (row) {
            return row.player.id;
        });
        glows.enter().append('circle')
            .attr('fill', function (row) {
                return glowFill(row.player, rankById[row.player.id]);
            })
            .style('pointer-events', 'none')
            .attr('cx', dotX)
            .attr('cy', dotY)
            .attr('r', 0)
            .merge(glows)
            .transition()
            .duration(first ? 0 : duration)
            .ease(d3.easeCubicOut)
            .attr('cx', dotX)
            .attr('cy', dotY)
            .attr('r', function (row) {
                return radiusOf(row) * 2.3;
            })
            .attr('opacity', function (row) {
                return scoredInEdition(row.player, state.index) ? 1 : .5;
            });
        glows.exit().remove();

        // 金牌领跑者的一圈柔光，先于星点绘制；不再是硬边圆点 + 描边。
        var leader = shown.filter(function (row) {
            return rankById[row.player.id] === 0;
        });
        var halos = svg.select('.ps-halos').selectAll('circle').data(leader, function (row) {
            return row.player.id;
        });
        halos.enter().append('circle')
            .attr('fill', 'url(#ps-halo-gold)')
            .style('pointer-events', 'none')
            .attr('cx', function (row) { return x(row.xv); })
            .attr('cy', function (row) { return y(row.yv); })
            .attr('r', 0)
            .merge(halos)
            .transition()
            .duration(first ? 0 : duration)
            .ease(d3.easeCubicOut)
            .attr('cx', function (row) { return x(row.xv); })
            .attr('cy', function (row) { return y(row.yv); })
            .attr('r', isNarrow() ? 13 : 17);
        halos.exit().remove();

        var dotsG = svg.select('.ps-dots');
        var dots = dotsG.selectAll('circle').data(shown, function (row) {
            return row.player.id;
        });
        var enter = dots.enter().append('circle')
            .attr('cx', dotX)
            .attr('cy', plotBottom)
            .attr('r', 0)
            .attr('stroke', 'none')
            .style('cursor', 'pointer')
            .on('pointerenter', function (event, row) { showTip(event, row.player); })
            .on('pointermove', function (event) { moveTip(event); })
            .on('pointerleave', hideTip)
            .on('click', function (event, row) { window.location.href = playerLink(row.player); });
        var merged = enter.merge(dots);
        merged
            .attr('fill', function (row) {
                return starFill(row.player, rankById[row.player.id]);
            })
            .attr('class', function (row) {
                return rankById[row.player.id] < 12 && scoredInEdition(row.player, state.index)
                    ? 'ps-star-bright'
                    : null;
            })
            .style('animation-duration', function (row) {
                return (6 + hashUnit(row.player.id + 7) * 4).toFixed(2) + 's';
            })
            .style('animation-delay', function (row) {
                return '-' + (hashUnit(row.player.id + 99) * 8).toFixed(2) + 's';
            })
            .transition()
            .duration(first ? 0 : duration)
            .ease(d3.easeCubicOut)
            .attr('cx', dotX)
            .attr('cy', dotY)
            .attr('r', radiusOf)
            .attr('opacity', function (row) {
                var rank = rankById[row.player.id];
                var lit = scoredInEdition(row.player, state.index);
                if (rank === 0) {
                    return 1;
                }
                if (rank < 10) {
                    return lit ? 1 : .82;
                }
                return lit ? 1 : .64;
            });
        dots.exit()
            .transition()
            .duration(first ? 0 : duration * 0.5)
            .attr('r', 0)
            .remove();

        // 小而实的星核：让星点从“糊”变“清楚”，但仍保留渐变外晕。
        var cores = svg.select('.ps-cores').selectAll('circle').data(shown, function (row) {
            return row.player.id;
        });
        cores.enter().append('circle')
            .attr('fill', function (row) {
                return coreFill(row.player, rankById[row.player.id]);
            })
            .style('pointer-events', 'none')
            .attr('cx', dotX)
            .attr('cy', dotY)
            .attr('r', 0)
            .merge(cores)
            .transition()
            .duration(first ? 0 : duration)
            .ease(d3.easeCubicOut)
            .attr('cx', dotX)
            .attr('cy', dotY)
            .attr('r', function (row) {
                return Math.max(1.15, radiusOf(row) * 0.34);
            })
            .attr('opacity', function (row) {
                return scoredInEdition(row.player, state.index) ? 1 : .72;
            });
        cores.exit().remove();

        // 参赛届数视图标进球前二；助攻视图标“进球 + 助攻”前二。
        var labelSorted = sorted.slice().sort(function (a, b) {
            if (state.yMode === 'assists') {
                return (b.xv + b.yv) - (a.xv + a.yv) || b.xv - a.xv;
            }
            return b.xv - a.xv || b.yv - a.yv;
        });
        var labelRows = labelSorted.slice(0, 2).map(function (row) {
            return shown.filter(function (shownRow) {
                return shownRow.player.id === row.player.id;
            })[0] || null;
        }).filter(Boolean);
        var labelPlace = {};
        labelRows.forEach(function (row) {
            var cx = dotX(row);
            var end = cx > W - 120;
            labelPlace[row.player.id] = {
                x: end ? cx - 10 : cx + 10,
                y: dotY(row) - 7,
                anchor: end ? 'end' : 'start'
            };
        });
        // 两名领跑者挤在一起时（同为 1 届、进球相邻），第二条标签下移一行，
        // 1986 的莱因克尔 / 马拉多纳就不会叠成一团。
        if (labelRows.length === 2) {
            var first = labelRows[0];
            var second = labelRows[1];
            if (Math.abs(dotY(first) - dotY(second)) < 28
                && Math.abs(dotX(first) - dotX(second)) < 220) {
                var below = Math.min(plotBottom - 6, dotY(second) + 22);
                labelPlace[second.player.id].y = below;
            }
        }
        var labels = svg.select('.ps-labels').selectAll('text').data(labelRows, function (row) {
            return row.player.id;
        });
        var labelEnter = labels.enter().append('text')
            .attr('class', 'ps-label')
            .attr('fill', INK.primary)
            .style('font-size', isNarrow() ? '13.5px' : '15px')
            .style('font-family', '"Microsoft YaHei", "PingFang SC", sans-serif')
            .style('pointer-events', 'none');
        labelEnter.merge(labels)
            .attr('x', function (row) {
                return labelPlace[row.player.id].x;
            })
            .attr('text-anchor', function (row) {
                return labelPlace[row.player.id].anchor;
            })
            .attr('y', function (row) {
                return labelPlace[row.player.id].y;
            })
            .text(function (row) {
                return playerName(row.player) + ' ' + row.xv + ' 球 · ' + row.yv
                    + (state.yMode === 'assists' ? ' 助' : ' 届');
            });
        labels.exit().remove();

        stage.setAttribute('aria-label',
            '四十年球员分布，' + year + ' 年；横轴生涯进球，纵轴' + yLabel()
            + '；显示 ' + shown.length + ' 名生涯至少 ' + limit + ' 球的球员（助攻视图显示同一批进球者的助攻数）；'
            + '亮点为该届有进球，金色为当前进球领跑。');
    }

    function showTip(event, player) {
        tip.textContent = playerFullName(player)
            + '\n' + (player.team || '球队待补') + ' · ' + (player.continent || '大洲待补')
            + '\n进球 ' + player.goals + ' · 助攻 ' + player.assists + ' · ' + player.editions + ' 届';
        tip.classList.add('is-visible');
        moveTip(event);
    }

    function moveTip(event) {
        if (!tip.classList.contains('is-visible')) {
            return;
        }
        var rect = stage.getBoundingClientRect();
        var left = event.clientX - rect.left + 14;
        var top = event.clientY - rect.top + 14;
        var tipRect = tip.getBoundingClientRect();
        if (left + tipRect.width > rect.width - 8) {
            left = event.clientX - rect.left - tipRect.width - 14;
        }
        if (top + tipRect.height > rect.height - 8) {
            top = event.clientY - rect.top - tipRect.height - 14;
        }
        tip.style.left = Math.max(8, left) + 'px';
        tip.style.top = Math.max(8, top) + 'px';
    }

    function hideTip() {
        tip.classList.remove('is-visible');
    }

    var resizeTimer = null;
    window.addEventListener('resize', function () {
        if (!state.data) {
            return;
        }
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            render(true);
        }, 150);
    });

    // 姓名切换:领跑者、tooltip 和数据表一起换名，不刷新页面。
    window.addEventListener('worldcup:name-lang-changed', function () {
        if (state.data) {
            render(true);
        }
    });
}());

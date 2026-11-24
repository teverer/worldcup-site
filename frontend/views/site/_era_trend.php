<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:界面设计 —— 首页「四十年，场均进球」长周期曲线
 *
 * 只回答一个问题：1986—2026，进球是多了还是少了。
 * 十一个点按年份排列，点上是当届冠军国旗；点进那一届。
 * 服务端输出 SVG，不依赖 ECharts；悬停/键盘焦点看东道主、决赛与总进球。
 *
 * @var yii\web\View $this
 * @var array $editions 按年份倒序的届次数据
 */

use common\components\TeamFlag;
use yii\helpers\Html;
use yii\helpers\Url;

$series = [];
foreach (array_reverse($editions) as $edition) {
    $games = isset($edition['games']) ? (int) $edition['games'] : 0;
    if ($games < 1) {
        continue;
    }
    $goals = isset($edition['goals']) ? (int) $edition['goals'] : 0;
    $series[] = $edition + [
        'avg' => $goals / $games,
    ];
}

if (count($series) < 2) {
    return;
}

$count = count($series);
$values = array_map(static function ($item) {
    return $item['avg'];
}, $series);
$minValue = min($values);
$maxValue = max($values);
// 0.2 一格：2.0 / 2.2 / … / 3.0，比四等分的 2.3、2.6、2.8 好读。
$min = floor($minValue * 5) / 5;
$max = ceil($maxValue * 5) / 5;
if ($max - $min < 0.6) {
    $max = $min + 0.6;
}

$viewW = 1200;
$viewH = 320;
$padL = 64;
$padR = 40;
$padT = 82;
$padB = 64;
$plotW = $viewW - $padL - $padR;
$plotH = $viewH - $padT - $padB;
$baselineY = $padT + $plotH;

$pointX = static function ($index) use ($count, $padL, $plotW) {
    return $count === 1 ? $padL + $plotW / 2 : $padL + ($plotW * $index / ($count - 1));
};
$pointY = static function ($value) use ($min, $max, $padT, $plotH) {
    return $padT + (($max - $value) / ($max - $min)) * $plotH;
};

$linePoints = [];
$flags = '';
$yearLabels = '';
$championLabels = '';
foreach ($series as $index => $item) {
    $x = $pointX($index);
    $y = $pointY($item['avg']);
    $linePoints[] = round($x, 2) . ',' . round($y, 2);

    $final = isset($item['final']) && is_array($item['final']) ? $item['final'] : [];
    $champion = isset($final['champion']) ? (string) $final['champion'] : '待补';
    $opponent = isset($final['opponent']) ? (string) $final['opponent'] : '';
    $score = isset($final['champion_score'], $final['opponent_score'])
        && $final['champion_score'] !== null
        ? (int) $final['champion_score'] . '–' . (int) $final['opponent_score']
        : '';
    $penalty = !empty($final['penalty_text']) ? '（点球 ' . $final['penalty_text'] . '）' : '';
    $editionLabel = (string) $item['name'];
    if (mb_strpos($editionLabel, (string) $item['year']) === false) {
        $editionLabel = $item['year'] . ' ' . $editionLabel;
    }
    $title = $editionLabel
        . ' · 冠军 ' . $champion
        . ($score !== '' ? ' · 决赛 ' . $score . ' ' . $opponent . $penalty : '')
        . ' · ' . (int) $item['games'] . ' 场 ' . (int) $item['goals'] . ' 球'
        . ' · 场均 ' . number_format($item['avg'], 2);

    $url = Url::to(['/tournament/view', 'id' => $item['id']]);
    $flagUrl = TeamFlag::url(isset($final['champion_code']) ? $final['champion_code'] : null);
    $tipX = max(76, min($viewW - 76, $x));
    $tipText = $item['year'] . ' · ' . $champion . ' · ' . number_format($item['avg'], 2);

    $flags .= '<a class="era-point" href="' . Html::encode($url) . '" tabindex="0" aria-label="' . Html::encode($title) . '">'
        . '<title>' . Html::encode($title) . '</title>'
        . '<line class="era-guide" x1="' . round($x, 2) . '" y1="' . $padT . '" x2="' . round($x, 2) . '" y2="' . $baselineY . '"/>'
        . ($flagUrl !== null
            ? '<rect class="era-flag-frame" x="' . round($x - 13, 2) . '" y="' . round($y - 49, 2) . '" width="26" height="20" rx="1"/>'
                . '<image class="era-flag" x="' . round($x - 12, 2) . '" y="' . round($y - 48, 2) . '" width="24" height="18" href="' . Html::encode($flagUrl) . '"/>'
            : '<text class="era-flag-code" x="' . round($x, 2) . '" y="' . round($y - 32, 2) . '">'
                . Html::encode(isset($final['champion_code']) ? $final['champion_code'] : '—') . '</text>')
        . '<circle class="era-dot" cx="' . round($x, 2) . '" cy="' . round($y, 2) . '" r="4.6"/>'
        . '<g class="era-tip" transform="translate(' . round($tipX, 2) . ',' . round($y - 64, 2) . ')">'
        . '<rect x="-72" y="-20" width="144" height="24" rx="2"/>'
        . '<text x="0" y="-4" text-anchor="middle">' . Html::encode($tipText) . '</text>'
        . '</g>'
        . '</a>';

    $yearLabels .= '<text class="era-year" x="' . round($x, 2) . '" y="' . ($baselineY + 26) . '">'
        . Html::encode((string) $item['year']) . '</text>';
    $championLabels .= '<text class="era-champion-name" x="' . round($x, 2) . '" y="' . ($baselineY + 47) . '">'
        . Html::encode($champion) . '</text>';
}

$line = implode(' ', $linePoints);
$mean = array_sum($values) / $count;

$grid = '';
$ticks = max(1, (int) round(($max - $min) / 0.2));
for ($tick = 0; $tick <= $ticks; $tick++) {
    $value = $min + (($max - $min) * $tick / $ticks);
    $y = $pointY($value);
    $grid .= '<line class="era-grid" x1="' . $padL . '" y1="' . round($y, 2) . '" x2="' . ($viewW - $padR) . '" y2="' . round($y, 2) . '"/>'
        . '<text class="era-axis-label" x="' . ($padL - 14) . '" y="' . round($y + 4, 2) . '">'
        . number_format($value, 1) . '</text>';
}

$first = $series[0];
$last = $series[$count - 1];
$maxItem = $series[0];
$minItem = $series[0];
foreach ($series as $item) {
    if ($item['avg'] > $maxItem['avg']) {
        $maxItem = $item;
    }
    if ($item['avg'] < $minItem['avg']) {
        $minItem = $item;
    }
}

$delta = $last['avg'] - $first['avg'];
$trendWord = $delta >= 0 ? '整体变多' : '整体变少';
$summary = $trendWord . '：' . ($delta >= 0 ? '+' : '') . number_format($delta, 2)
    . '（' . number_format($first['avg'], 2) . ' → ' . number_format($last['avg'], 2) . '）；'
    . '最高 ' . $maxItem['year'] . ' 的 ' . number_format($maxItem['avg'], 2)
    . '，最低 ' . $minItem['year'] . ' 的 ' . number_format($minItem['avg'], 2) . '。';
?>
<section class="era-trend" aria-labelledby="era-trend-title">
    <header class="era-trend-head">
        <div class="era-trend-head-main">
            <h2 id="era-trend-title">1990 触底，2026 反弹</h2>
            <p>四十年，十一届世界杯的场均进球并非一路下滑。旗是当届冠军；点一届，进入那一年。</p>
        </div>
        <p class="era-trend-insight"><?= Html::encode($summary) ?></p>
    </header>

    <p class="era-scroll-hint" hidden>左右滑动看完整曲线</p>
    <div class="era-chart-scroll">
        <svg class="era-chart" viewBox="0 0 <?= $viewW ?> <?= $viewH ?>"
             preserveAspectRatio="xMidYMid meet" role="img"
             aria-labelledby="era-chart-title era-chart-desc">
            <title id="era-chart-title">1986 至 2026 年世界杯场均进球折线</title>
            <desc id="era-chart-desc"><?= Html::encode($summary . ' 每个点标注当届冠军国旗，可进入该届。') ?></desc>
            <?= $grid ?>
            <line class="era-baseline" x1="<?= $padL ?>" y1="<?= $baselineY ?>" x2="<?= $viewW - $padR ?>" y2="<?= $baselineY ?>"/>

            <line class="era-mean" x1="<?= $padL ?>" y1="<?= round($pointY($mean), 2) ?>" x2="<?= $viewW - $padR ?>" y2="<?= round($pointY($mean), 2) ?>"/>
            <text class="era-mean-label" x="<?= $viewW - $padR ?>" y="<?= round($pointY($mean) - 7, 2) ?>">均值 <?= number_format($mean, 2) ?></text>
            <polyline class="era-line" pathLength="1" points="<?= Html::encode($line) ?>"/>
            <?= $flags ?>
            <?= $yearLabels ?>
            <?= $championLabels ?>

            <text class="era-axis-title" x="<?= $padL - 14 ?>" y="<?= $padT - 34 ?>">场均进球</text>
        </svg>
    </div>

    <p class="era-trend-note">口径：只统计已结束的比赛；点球大战进球不计入。场均进球 = 总进球 ÷ 场次。2026 年起为 48 队 / 104 场，赛制变化会影响场均；纵轴从 <?= number_format($min, 1) ?> 起，不是 0 基线。</p>

    <details class="era-data">
        <summary>查看十一届数据表</summary>
        <div class="era-table-wrap">
            <table class="era-table">
                <caption class="sr-only">1986—2026 世界杯场均进球与冠军</caption>
                <thead>
                    <tr>
                        <th scope="col">年份</th>
                        <th scope="col">东道主</th>
                        <th scope="col">冠军</th>
                        <th scope="col">决赛</th>
                        <th scope="col" class="num">场次</th>
                        <th scope="col" class="num">进球</th>
                        <th scope="col" class="num">场均</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($series as $item): ?>
                        <?php $final = isset($item['final']) && is_array($item['final']) ? $item['final'] : []; ?>
                        <tr>
                            <th scope="row"><?= Html::a(Html::encode((string) $item['year']), ['/tournament/view', 'id' => $item['id']]) ?></th>
                            <td><?= Html::encode((string) $item['host']) ?></td>
                            <td><?= Html::encode(isset($final['champion']) ? (string) $final['champion'] : '待补') ?></td>
                            <td>
                                <?php if (isset($final['champion_score'], $final['opponent_score'], $final['opponent'])
                                    && $final['champion_score'] !== null): ?>
                                    <?= Html::encode((int) $final['champion_score'] . '–' . (int) $final['opponent_score'] . ' ' . $final['opponent']) ?>
                                <?php else: ?>
                                    待补
                                <?php endif ?>
                            </td>
                            <td class="num"><?= (int) $item['games'] ?></td>
                            <td class="num"><?= (int) $item['goals'] ?></td>
                            <td class="num"><?= number_format($item['avg'], 2) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </details>

    <p class="era-more"><?= Html::a('继续看：十一届纪录、决赛与总射手', ['/record/index']) ?></p>

    <script>
    (function () {
        var scroller = document.querySelector('.era-chart-scroll');
        var hint = document.querySelector('.era-scroll-hint');
        if (!scroller || !hint) {
            return;
        }
        var sync = function () {
            hint.hidden = scroller.scrollWidth <= scroller.clientWidth + 2;
        };
        sync();
        window.addEventListener('resize', sync);
    })();
    </script>
</section>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:球队与球员 —— 球员详情「生涯进球」图:各届进球与助攻的并排柱。
 *
 * 只在有进球或助攻时渲染;没有数据的球员直接不出现这一节,不留空框。
 *
 * @var yii\web\View $this
 * @var array $statistics 由 Player::statistics() 提供,每行含 year / goals / assists
 * @var array $participations 由 Player::participations() 提供,补上没有进球/助攻的届次
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

// 兼容旧调用:只传 statistics 时仍能按有进球的届次画图。
$participations = isset($participations) && is_array($participations) ? $participations : [];

// 先以参赛届次建轴,再把进球/助攻覆盖上去:
// statistics() 只返回有进球或助攻的届次,不补零的话,中间的空白届会被轴跳过。
$byYear = [];
foreach ($participations as $row) {
    $byYear[(int) $row['year']] = [
        'year' => (int) $row['year'],
        'goals' => 0,
        'assists' => 0,
    ];
}
foreach ($statistics as $row) {
    $year = (int) $row['year'];
    $byYear[$year] = [
        'year' => $year,
        'goals' => (int) $row['goals'],
        'assists' => (int) $row['assists'],
    ];
}

$points = array_values($byYear);
usort($points, function ($a, $b) {
    return $a['year'] <=> $b['year'];
});

$totalGoals = (int) array_sum(array_column($points, 'goals'));
$totalAssists = (int) array_sum(array_column($points, 'assists'));

if (!$points || $totalGoals + $totalAssists < 1) {
    return;
}

$this->registerCssFile('@web/css/charts.css');
$this->registerCssFile('@web/css/chart-stage.css');
$this->registerCssFile('@web/css/player-chart.css');
$this->registerJsFile('@web/js/charts.js', [
    'position' => View::POS_END,
    'id' => 'charts-js',
]);

$payload = [
    'years' => array_column($points, 'year'),
    'goals' => array_column($points, 'goals'),
    'assists' => array_column($points, 'assists'),
];

$chartTitle = $totalAssists > 0 ? '历届进球与助攻' : '历届进球';
$singleTournament = count($points) === 1;
?>
<section class="pd-section pd-career" aria-labelledby="pd-career-chart-title">
    <div class="pd-section-head">
        <h2 id="pd-career-chart-title"><?= $chartTitle ?></h2>
        <p>
            柱为当届<?= $totalAssists > 0 ? '进球与助攻' : '进球' ?>；只计已结束比赛，不含乌龙球与点球大战。
        </p>
    </div>
    <div class="chart-card pd-career-card">
        <div id="player-career-chart" class="chart-canvas chart-canvas--player<?= $singleTournament ? ' is-single' : '' ?>"
             data-chart="player-career" role="img"
             data-echarts-src="<?= Html::encode(Url::to('@web/js/vendor/echarts.min.js')) ?>"
             aria-label="球员各届世界杯进球<?= $totalAssists > 0 ? '与助攻' : '' ?>柱状图">
            <p class="chart-loading">图表加载中…</p>
        </div>
    </div>
    <script type="application/json" id="player-career-data"><?= json_encode($payload, JSON_UNESCAPED_UNICODE) ?></script>
</section>

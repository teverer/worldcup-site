<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:球队与球员 —— 球队详情「世界杯轨迹」:各届最终阶段折线,冠军用金点。
 *
 * 只画已录入比赛的届次;不足两届时返回,避免单点图。
 *
 * @var yii\web\View $this
 * @var array $records 由 Team::tournamentRecords() 提供
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$points = [];
foreach ($records as $record) {
    $rank = (int) ($record['stage_rank'] ?? -1);
    if ((int) $record['played'] < 1 || $rank < 1) {
        continue;
    }
    $points[] = [
        'year' => (int) $record['year'],
        'rank' => $rank,
        'stage' => (string) $record['stage'],
        'played' => (int) $record['played'],
        'won' => (int) $record['won'],
        'drawn' => (int) $record['drawn'],
        'lost' => (int) $record['lost'],
        'goalsFor' => (int) $record['goals_for'],
        'goalsAgainst' => (int) $record['goals_against'],
        'champion' => !empty($record['champion']),
    ];
}

if (count($points) < 2) {
    if (count($points) === 1) {
        echo Html::tag('p', '参赛不足两届，暂不绘制世界杯轨迹；完整记录见下表。', ['class' => 'td-chart-note']);
    }
    return;
}

usort($points, function ($a, $b) {
    return $a['year'] <=> $b['year'];
});

$this->registerCssFile('@web/css/charts.css');
$this->registerCssFile('@web/css/chart-stage.css');
$this->registerCssFile('@web/css/team-chart.css');
$this->registerJsFile('@web/js/charts.js', [
    'position' => View::POS_END,
    'id' => 'charts-js',
]);

$payload = ['points' => $points];
?>
<div class="td-history-chart chart-card">
    <div class="chart-card-head">
        <h3>世界杯轨迹</h3>
        <p class="chart-caption">
            金点 = 夺冠；只画已录入比赛的届次
        </p>
    </div>
    <div id="team-history-chart" class="chart-canvas chart-canvas--team"
         role="img" data-echarts-src="<?= Html::encode(Url::to('@web/js/vendor/echarts.min.js')) ?>"
         aria-label="球队历届世界杯最终阶段折线图，金点为夺冠">
        <p class="chart-loading">图表加载中…</p>
    </div>
    <script type="application/json" id="team-history-data"><?= json_encode($payload, JSON_UNESCAPED_UNICODE) ?></script>
</div>

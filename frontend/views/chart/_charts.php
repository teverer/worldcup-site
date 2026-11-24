<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 积分榜 / 射手榜 / 各队进球分布三个图表容器。
 *
 * 使用方式(由届次页或比赛页嵌入):
 * <?= $this->render('@frontend/views/chart/_charts.php', ['tournamentId' => $model->id]) ?>
 *
 * @var yii\web\View $this
 * @var int|string $tournamentId
 * @var string|null $group
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$requestTournamentId = Yii::$app->request->get('tournamentId', 0);
if (!is_scalar($requestTournamentId)) {
    $requestTournamentId = 0;
}
$tournamentId = isset($tournamentId) && is_scalar($tournamentId)
    ? (int) $tournamentId
    : (int) $requestTournamentId;

$requestGroup = isset($group) ? $group : Yii::$app->request->get('group', '');
$group = is_scalar($requestGroup) ? trim((string) $requestGroup) : '';

if ($tournamentId < 1) {
    echo Html::tag('div', '请先选择届次后再查看统计图表。', ['class' => 'alert alert-info chart-notice']);
    return;
}

$this->registerCssFile('@web/css/charts.css');
$this->registerCssFile('@web/css/chart-stage.css');
// ECharts 不在首屏加载:charts.js 在统计面板第一次可见时才注入本地脚本。
// 本地 /js/vendor/echarts.min.js(Apache-2.0),避免部署环境无外网或 CDN 不可用。
$this->registerJsFile('@web/js/charts.js', [
    'position' => View::POS_END,
    'id' => 'charts-js',
]);
?>
<section id="charts-panel" class="charts-panel"
         data-tournament-id="<?= (int) $tournamentId ?>"
         data-group="<?= Html::encode($group) ?>"
         data-echarts-src="<?= Html::encode(Url::to('@web/js/vendor/echarts.min.js')) ?>"
         data-goal-timing-url="<?= Html::encode(Url::to(['/chart/goal-timing'])) ?>"
         data-scorers-url="<?= Html::encode(Url::to(['/chart/scorers'])) ?>"
         data-team-goals-url="<?= Html::encode(Url::to(['/chart/team-goals'])) ?>">
    <div class="chart-grid">
        <article class="chart-card chart-card--wide">
            <div class="chart-card-head">
                <div class="chart-card-head-text">
                    <h3>进球时间分布</h3>
                    <p class="chart-caption">按 15 分钟分桶；45+ / 90+ 含补时，加时单列 91–120；不计点球大战。</p>
                </div>
            </div>
            <div id="chart-goal-timing" class="chart-canvas chart-canvas--timing"
                 data-chart="goal-timing" role="img" aria-label="进球时间分布柱状图">
                <p class="chart-loading">图表加载中…</p>
            </div>
            <details class="chart-data">
                <summary>查看进球时间数据表</summary>
                <div id="table-goal-timing" class="chart-table-wrap"></div>
            </details>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>

        <article class="chart-card">
            <div class="chart-card-head">
                <h3>射手榜 Top 10</h3>
                <p class="chart-caption">按进球数降序；不计乌龙球与点球大战。</p>
            </div>
            <div id="chart-scorers" class="chart-canvas chart-canvas--scorers"
                 data-chart="scorers" role="img" aria-label="射手榜横向柱状图">
                <p class="chart-loading">图表加载中…</p>
            </div>
            <details class="chart-data">
                <summary>查看射手榜数据表</summary>
                <div id="table-scorers" class="chart-table-wrap"></div>
            </details>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>

        <article class="chart-card">
            <div class="chart-card-head">
                <h3>各队进球分布</h3>
                <p class="chart-caption">前 8 队 + 其他；扇面为占全届总进球的比例，分母固定。</p>
            </div>
            <div id="chart-team-goals" class="chart-canvas chart-canvas--teams"
                 data-chart="team-goals" role="img" aria-label="各队进球占比圆环图">
                <p class="chart-loading">图表加载中…</p>
            </div>
            <details class="chart-data">
                <summary>查看各队进球数据表</summary>
                <div id="table-team-goals" class="chart-table-wrap"></div>
            </details>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>
    </div>

    <p class="chart-footnote">
        口径：只统计已结束的比赛；乌龙球计入受益球队；点球大战进球单独统计，不计入球员进球、球队进球分布与进球时间分布。45+ / 90+ 含补时，加时单列。
    </p>
</section>

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
$this->registerJsFile('https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js', [
    'position' => View::POS_END,
    'id' => 'echarts-cdn',
    'onerror' => 'window.__echartsLoadFailed = true;',
]);
$this->registerJsFile('@web/js/charts.js', [
    'position' => View::POS_END,
    'id' => 'charts-js',
]);
?>
<section id="charts-panel" class="charts-panel"
         data-tournament-id="<?= (int) $tournamentId ?>"
         data-group="<?= Html::encode($group) ?>"
         data-standings-url="<?= Html::encode(Url::to(['/chart/standings'])) ?>"
         data-scorers-url="<?= Html::encode(Url::to(['/chart/scorers'])) ?>"
         data-team-goals-url="<?= Html::encode(Url::to(['/chart/team-goals'])) ?>">
    <header class="charts-heading">
        <p class="charts-kicker">模块③ · 届次与比赛</p>
        <h2>赛事数据可视化</h2>
        <p>积分榜只统计已结束的小组赛；射手榜排除乌龙球与点球大战进球，各队进球分布按进球归属球队统计。</p>
    </header>

    <div class="chart-grid">
        <article class="chart-card chart-card--wide">
            <div class="chart-card-head chart-card-head--standings">
                <div class="chart-card-head-text">
                    <h3>小组积分榜</h3>
                    <p class="chart-caption">默认显示 A 组，可切换小组查看该组球队的积分；柱顶为积分，悬停可查看胜 / 平 / 负与进失球。</p>
                </div>
                <div class="chart-group-switch-wrap">
                    <span class="chart-group-switch-label" id="standings-group-label">小组</span>
                    <div id="standings-group-switch" class="chart-group-switch"
                         role="group" aria-labelledby="standings-group-label"></div>
                </div>
            </div>
            <div id="chart-standings" class="chart-canvas chart-canvas--standings"
                 data-chart="standings" role="img" aria-label="小组积分榜柱状图">
                <p class="chart-loading">图表加载中…</p>
            </div>
            <details class="chart-data">
                <summary>查看积分榜数据表</summary>
                <div id="table-standings" class="chart-table-wrap"></div>
            </details>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>

        <article class="chart-card">
            <div class="chart-card-head">
                <h3>射手榜 Top 10</h3>
                <p class="chart-caption">按进球数降序；乌龙球与点球大战进球不计入球员进球。</p>
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
                <p class="chart-caption">按 goal.team_id 统计；乌龙球已计入受益球队，点球大战进球已排除。</p>
            </div>
            <div id="chart-team-goals" class="chart-canvas chart-canvas--pie"
                 data-chart="team-goals" role="img" aria-label="各队进球分布饼图">
                <p class="chart-loading">图表加载中…</p>
            </div>
            <details class="chart-data">
                <summary>查看各队进球数据表</summary>
                <div id="table-team-goals" class="chart-table-wrap"></div>
            </details>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>
    </div>
</section>

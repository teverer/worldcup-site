<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 本届射手榜 / 本届各队进球分布两个图表容器。
 *
 * 使用方式(由届次页嵌入):
 * <?= $this->render('@frontend/views/chart/_charts.php', ['tournamentId' => $model->id]) ?>
 *
 * 2026-11-24 减法：移除「进球时间分布」与「小组积分榜（旧版）」切换，
 * 只保留两张回答“谁进球 / 哪些队进球”的图；积分榜完整表格在「小组赛」。
 *
 * @var yii\web\View $this
 * @var int|string $tournamentId
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

if ($tournamentId < 1) {
    echo Html::tag('div', '请先选择届次后再查看统计图表。', ['class' => 'alert alert-info chart-notice']);
    return;
}

$this->registerCssFile('@web/css/charts.css');
$this->registerCssFile('@web/css/chart-stage.css');
// ECharts 不在首屏加载:charts.js 在统计面板第一次可见时才注入本地脚本。
// 本地 /js/vendor/echarts.min.js(Apache-2.0),避免部署环境无外网或 CDN 不可用。
// 追加 filemtime 版本号,避免用户拿到旧图表脚本。
$chartsJsVersion = @filemtime(Yii::getAlias('@webroot/js/charts.js')) ?: '';
$this->registerJsFile(
    Url::to('@web/js/charts.js') . ($chartsJsVersion !== '' ? '?v=' . $chartsJsVersion : ''),
    [
        'position' => View::POS_END,
        'id' => 'charts-js',
    ]
);
?>
<section id="charts-panel" class="charts-panel"
         data-tournament-id="<?= (int) $tournamentId ?>"
         data-echarts-src="<?= Html::encode(Url::to('@web/js/vendor/echarts.min.js')) ?>"
         data-scorers-url="<?= Html::encode(Url::to(['/chart/scorers'])) ?>"
         data-team-goals-url="<?= Html::encode(Url::to(['/chart/team-goals'])) ?>">

    <div class="chart-grid">
        <article class="chart-card">
            <div class="chart-card-head">
                <h3>本届射手榜 Top 10</h3>
            </div>
            <div id="chart-scorers" class="chart-canvas chart-canvas--scorers"
                 data-chart="scorers" role="img" aria-label="本届射手榜横向柱状图">
                <p class="chart-loading">图表加载中…</p>
            </div>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>

        <article class="chart-card">
            <div class="chart-card-head">
                <h3>本届各队进球分布</h3>
            </div>
            <div class="chart-teams-wrap">
                <div id="chart-team-goals" class="chart-canvas chart-canvas--teams"
                     data-chart="team-goals" role="img" aria-label="本届各队进球占比圆环图">
                    <p class="chart-loading">图表加载中…</p>
                </div>
                <?php /* 环心数字用 HTML 叠层：随圆心百分比走，不会被 ECharts 文字锚点带偏。 */ ?>
                <div class="chart-donut-center" data-donut-center hidden>
                    <strong>—</strong>
                    <span>全届进球</span>
                </div>
            </div>
            <noscript><p class="chart-empty-state">图表需要启用 JavaScript。</p></noscript>
        </article>
    </div>

    <p class="chart-footnote">
        乌龙球计入受益队；点球大战不计入射手与球队进球
    </p>
</section>

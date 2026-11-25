<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 统计视图
 *
 * 两张本届图（射手榜、各队进球分布）回答“谁进球、哪些队进球”；
 * 下面的「历届数据」把这一届放回十一届：四十年总射手榜、国家决赛榜、四强版图。
 * 四强版图之后是「球员星云」：每个点一个人，x=生涯进球、y=参赛届数，
 * 按届播放，不替换上面两张本届图。
 * 小组积分的完整表格留在「小组赛」标签，不在统计页重复。
 *
 * @var yii\web\View $this
 * @var common\models\Tournament $model
 * @var array $history
 * @var array $currentGoals
 * @var array|null $champion
 * @var array|null $runnerUp
 * @var array $stats
 */

use yii\helpers\Url;

$statsHistoryCssVersion = @filemtime(Yii::getAlias('@webroot/css/stats-history.css')) ?: '';
$this->registerCssFile(
    Url::to('@web/css/stats-history.css') . ($statsHistoryCssVersion !== '' ? '?v=' . $statsHistoryCssVersion : '')
);

// 届次页尾部的背景过渡：统计面板总是渲染，所以在这里注册；规则只作用于淘汰赛舞台。
$transitionCssVersion = @filemtime(Yii::getAlias('@webroot/css/tournament-transition.css')) ?: '';
$this->registerCssFile(
    Url::to('@web/css/tournament-transition.css') . ($transitionCssVersion !== '' ? '?v=' . $transitionCssVersion : '')
);
?>
<section class="td-panel td-panel--stats" id="charts" tabindex="-1"
         aria-labelledby="td-charts-title">
    <header class="td-section-head" id="td-stats-head">
        <h2 class="section-title" id="td-charts-title">这一届的数据</h2>
        <?php /* 带 #charts 回跳:切换姓名后仍停留在“统计”,不要掉回淘汰赛。 */ ?>
        <?= $this->render('//partials/_name_lang', ['returnUrl' => Url::current() . '#charts']) ?>
    </header>

    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>

    <div class="stats-history" id="history">
        <?php /* 与上面「这一届的数据」同一套章节标题，给历届部分一个清楚的起点。 */ ?>
        <header class="td-section-head stats-history-head" id="td-history-head">
            <h2 class="section-title" id="td-history-title">历届数据</h2>
        </header>
        <?php /* 四十年总射手榜 / 国家决赛榜直接接在两张本届图下面，同一列对照。 */ ?>
        <?= $this->render('_history_stats', [
            'history' => $history,
            'currentGoals' => $currentGoals,
            'champion' => $champion,
            'runnerUp' => $runnerUp,
            'stats' => $stats,
            'currentEditionId' => (int) $model->id,
            'currentYear' => (int) $model->year,
        ]) ?>

        <?php /* 球员星云属于「历届数据」这一章：接在四强版图之后，不再另起一节。 */ ?>
        <?= $this->render('_player_star') ?>
    </div>
</section>

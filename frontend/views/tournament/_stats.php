<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 统计视图
 *
 * 两张本届图（射手榜、各队进球分布）回答“谁进球、哪些队进球”；
 * 下面的「历史坐标」把这一届放回十一届：历届总射手、谁常在决赛、四强版图。
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

use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('@web/css/stats-history.css');
?>
<section class="td-panel td-panel--stats" id="charts" tabindex="-1"
         aria-labelledby="td-charts-title">
    <header class="td-section-head" id="td-stats-head">
        <h2 class="section-title" id="td-charts-title">这一届的数据</h2>
        <?php /* 带 #charts 回跳:切换姓名后仍停留在“统计”,不要掉回淘汰赛。 */ ?>
        <?= $this->render('//partials/_name_lang', ['returnUrl' => Url::current() . '#charts']) ?>
    </header>

    <p class="td-section-note">
        两张图回答这一届“谁进球、哪些队进球”；小组积分的完整表格在「小组赛」。
        下面把这一届放回十一届里看。
    </p>

    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>

    <div class="stats-history" id="history">
        <header class="stats-history-head">
            <p class="stats-history-kicker">十一届坐标</p>
            <h2>把 <?= Html::encode((string) $model->year) ?> 放回十一届里看</h2>
            <p>
                金色 = 本届。三块都是跨届聚合，用来回答“这一届在历史上是什么位置”，
                不重复上面的本届图表。
            </p>
        </header>

        <nav class="stats-history-nav" aria-label="本页三块，可跳转">
            <span class="stats-history-nav-label">跳到</span>
            <a href="#hist-scorers">历届总射手</a>
            <a href="#hist-nations">谁常在决赛</a>
            <a href="#hist-four">四强版图</a>
        </nav>

        <?= $this->render('_history_stats', [
            'history' => $history,
            'currentGoals' => $currentGoals,
            'champion' => $champion,
            'runnerUp' => $runnerUp,
            'stats' => $stats,
            'currentEditionId' => (int) $model->id,
            'currentYear' => (int) $model->year,
        ]) ?>
    </div>
</section>

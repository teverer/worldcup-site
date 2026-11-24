<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 统计视图
 *
 * 三张图分别回答:什么时候进球（进球时间分布）、谁进球（射手榜）、各队占多少（进球构成）。
 * 小组积分留在「小组赛」标签的表格里，不在统计页重复。每张图保留口径说明与数据表入口。
 *
 * @var yii\web\View $this
 * @var common\models\Tournament $model
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<section class="td-panel td-panel--stats" id="charts" tabindex="-1"
         aria-labelledby="td-charts-title">
    <header class="td-section-head" id="td-stats-head">
        <h2 class="section-title" id="td-charts-title">这一届的进球</h2>
        <?php /* 带 #charts 回跳:切换姓名后仍停留在“统计”,不要掉回淘汰赛。 */ ?>
        <?= $this->render('//partials/_name_lang', ['returnUrl' => Url::current() . '#charts']) ?>
    </header>

    <p class="td-section-note">三张图：什么时候进、谁进、哪些队进了多少。小组积分的完整表格在「小组赛」。<?= Html::a('看四十年四强版图', Url::to(['/record/index', 'edition' => $model->id]) . '#four', ['class' => 'td-crosslink']) ?>。</p>

    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>
</section>

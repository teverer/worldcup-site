<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 统计视图
 *
 * 只保留三张现有图:小组积分榜、射手榜 Top 10、各队进球分布。
 * 不新增图表;每张图保留一句口径说明与“查看数据表”入口。
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
        <h2 class="section-title" id="td-charts-title">统计图表</h2>
        <?php /* 带 #charts 回跳:切换姓名后仍停留在“统计”,不要掉回淘汰赛。 */ ?>
        <?= $this->render('//partials/_name_lang', ['returnUrl' => Url::current() . '#charts']) ?>
    </header>

    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>
</section>

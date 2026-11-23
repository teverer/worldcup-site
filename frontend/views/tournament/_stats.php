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
?>
<section class="td-panel td-panel--stats" id="charts" tabindex="-1"
         aria-labelledby="td-charts-title">
    <header class="td-section-head">
        <h2 class="section-title" id="td-charts-title">统计 <small>积分榜 · 射手榜 · 进球分布</small></h2>
        <p class="td-section-note">图表只读取已结束比赛的进球与积分，点球大战进球单列统计。</p>
    </header>

    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>
</section>

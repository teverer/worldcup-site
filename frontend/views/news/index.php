<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 已发布资讯列表与搜索。
 *
 * @var yii\web\View $this
 * @var common\models\NewsSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\widgets\ListView;

$this->title = '资讯';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/community.css');
?>
<section class="community-page news-index">
    <header class="community-heading">
        <p class="community-kicker">WORLD CUP JOURNAL</p>
        <h1>世界杯资讯</h1>
        <p>赛事回顾、数据观察与团队动态。</p>
    </header>

    <?= Html::beginForm(['/news/index'], 'get', ['class' => 'community-search']) ?>
    <div class="form-group">
        <?= Html::activeLabel($searchModel, 'q', ['for' => 'news-q']) ?>
        <?= Html::activeTextInput($searchModel, 'q', [
            'id' => 'news-q',
            'name' => 'q',
            'class' => 'form-control',
            'maxlength' => 100,
            'placeholder' => '搜索标题或作者',
        ]) ?>
    </div>
    <div class="community-search-actions">
        <?= Html::submitButton('搜索', ['class' => 'btn btn-success']) ?>
        <?= Html::a('重置', ['/news/index'], ['class' => 'btn btn-default']) ?>
    </div>
    <?= Html::endForm() ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '_card',
        'layout' => '<div class="community-summary">{summary}</div>{items}<div class="community-pager">{pager}</div>',
        'summary' => '共 {totalCount} 条资讯',
        'emptyText' => '暂无已发布资讯。',
        'emptyTextOptions' => ['class' => 'community-empty'],
        'options' => ['class' => 'news-list'],
        'itemOptions' => ['tag' => false],
        'viewParams' => ['searchModel' => $searchModel],
        'pager' => [
            'options' => ['class' => 'pagination'],
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
        ],
    ]) ?>
</section>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 资讯索引:按时间读,搜索标题或作者。
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
    <header class="news-head">
        <h1>资讯</h1>
        <p>赛事回顾、数据观察与团队动态。</p>
    </header>

    <?= Html::beginForm(['/news/index'], 'get', ['class' => 'news-search', 'role' => 'search']) ?>
        <label class="sr-only" for="news-q">搜索标题或作者</label>
        <?= Html::textInput('q', $searchModel->q, [
            'id' => 'news-q',
            'class' => 'form-control',
            'maxlength' => 100,
            'placeholder' => '搜索标题或作者',
        ]) ?>
        <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
        <?php if (trim((string) $searchModel->q) !== ''): ?>
            <?= Html::a('清除', ['/news/index'], ['class' => 'news-clear']) ?>
        <?php endif ?>
    <?= Html::endForm() ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '_card',
        'layout' => '<p class="news-summary">{summary}</p>{items}<div class="community-pager">{pager}</div>',
        'summary' => '共 {totalCount} 篇资讯',
        'emptyText' => '<div class="news-empty"><h2>没有找到资讯</h2>'
            . '<p>换一个标题或作者试试，或者回首页从届次进入。</p>'
            . '<p><a href="' . Html::encode(Yii::$app->homeUrl) . '">回首页</a></p></div>',
        'emptyTextOptions' => ['class' => 'news-empty-wrap'],
        'options' => ['class' => 'news-rows'],
        'itemOptions' => ['tag' => false],
        'pager' => [
            'options' => ['class' => 'pagination'],
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
        ],
    ]) ?>
</section>

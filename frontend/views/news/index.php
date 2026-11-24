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

    <?php // 2 篇资讯不值得一条搜索框；文章多起来再恢复。 ?>
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '_card',
        'layout' => '{items}<div class="community-pager">{pager}</div>',
        'emptyText' => '<div class="news-empty"><h2>还没有资讯</h2>'
            . '<p><a href="' . Html::encode(Yii::$app->homeUrl) . '">回首页从届次进入</a></p></div>',
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

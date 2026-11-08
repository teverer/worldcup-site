<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 已发布资讯详情。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => '资讯', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/community.css');

$safeContent = HtmlPurifier::process($model->content ?: '', [
    'Cache.DefinitionImpl' => null,
]);
$date = $model->publish_time ?: $model->create_time;
?>
<article class="community-page news-detail">
    <header class="news-detail-header">
        <p class="community-kicker">世界杯资讯</p>
        <h1><?= Html::encode($model->title) ?></h1>
        <p class="news-detail-meta">
            <?= Html::encode(Yii::$app->formatter->asDate($date, 'php:Y-m-d')) ?>
            <?php if ($model->author): ?> · <?= Html::encode($model->author) ?><?php endif ?>
        </p>
    </header>

    <?php if ($model->cover): ?>
    <figure class="news-detail-cover">
        <?= Html::img('@web/' . $model->cover, ['alt' => $model->title . '封面']) ?>
    </figure>
    <?php endif ?>

    <div class="news-content">
        <?= $safeContent !== '' ? $safeContent : '<p>暂无正文。</p>' ?>
    </div>

    <footer class="news-detail-footer">
        <?= Html::a('← 返回资讯列表', ['index'], ['class' => 'btn btn-default']) ?>
    </footer>
</article>

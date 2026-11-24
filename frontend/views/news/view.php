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
use common\components\CommentTarget;
use frontend\widgets\CommentSection;

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
        <?= $safeContent !== '' ? $safeContent : '<div class="empty-state"><h3>暂无正文</h3><p>该资讯的正文尚未发布。</p></div>' ?>
    </div>

    <?php // 面包屑和导航已经能回列表；文章读完直接进入本页留言。 ?>
    <?= CommentSection::widget([
        'targetType' => CommentTarget::NEWS,
        'targetId' => $model->id,
    ]) ?>
</article>

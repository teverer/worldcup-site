<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 资讯列表卡片。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

use yii\helpers\Html;
use yii\helpers\StringHelper;

$plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($model->content ?: '')));
$excerpt = StringHelper::truncate($plainText, 150, '…', null, true);
$date = $model->publish_time ?: $model->create_time;
?>
<article class="news-card">
    <?= Html::a(
        $model->cover
            ? Html::img('@web/' . $model->cover, ['class' => 'news-cover', 'alt' => $model->title . '封面'])
            : '<span class="news-cover news-cover-placeholder">WORLD CUP</span>',
        ['view', 'id' => $model->id],
        ['class' => 'news-cover-link', 'aria-label' => '阅读：' . $model->title, 'encode' => false]
    ) ?>
    <div class="news-card-body">
        <p class="news-meta">
            <?= Html::encode(Yii::$app->formatter->asDate($date, 'php:Y-m-d')) ?>
            <?php if ($model->author): ?> · <?= Html::encode($model->author) ?><?php endif ?>
        </p>
        <h2><?= Html::a(Html::encode($model->title), ['view', 'id' => $model->id]) ?></h2>
        <p class="news-excerpt"><?= Html::encode($excerpt ?: '阅读全文') ?></p>
        <?= Html::a('阅读全文 <span aria-hidden="true">→</span>', ['view', 'id' => $model->id], [
            'class' => 'news-read-more',
        ]) ?>
    </div>
</article>

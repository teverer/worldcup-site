<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 资讯列表的一行:日期、标题、摘要、阅读入口。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

use yii\helpers\Html;
use yii\helpers\StringHelper;

$plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($model->content ?: '')));
$excerpt = StringHelper::truncate($plainText, 120, '…', null, true);
$date = $model->publish_time ?: $model->create_time;
$dateText = Yii::$app->formatter->asDate($date, 'php:Y-m-d');
?>
<article class="news-row">
    <a class="news-row-link" href="<?= Html::encode(\yii\helpers\Url::to(['view', 'id' => $model->id])) ?>">
        <span class="news-row-date"><?= Html::encode($dateText) ?></span>
        <span class="news-row-body">
            <span class="news-row-title"><?= Html::encode($model->title) ?></span>
            <?php if ($excerpt !== ''): ?>
                <span class="news-row-excerpt"><?= Html::encode($excerpt) ?></span>
            <?php endif ?>
            <?php if ($model->author): ?>
                <span class="news-row-author"><?= Html::encode($model->author) ?></span>
            <?php endif ?>
        </span>
    </a>
</article>

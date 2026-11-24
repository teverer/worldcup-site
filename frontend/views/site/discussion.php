<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:界面设计 —— 全站留言簿（世界杯整体的一本簿）
 *
 * @var yii\web\View $this
 */

use common\components\CommentTarget;
use frontend\widgets\CommentSection;

$this->title = '世界杯留言簿';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-discussion">
    <?= CommentSection::widget([
        'targetType' => CommentTarget::SITE,
        'targetId' => CommentTarget::SITE_ID,
        'heading' => '世界杯留言簿',
        'headingTag' => 'h1',
        'emptyTitle' => '留言簿还是空的。',
    ]) ?>
</div>

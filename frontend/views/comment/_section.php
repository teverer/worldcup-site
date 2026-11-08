<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 留言区容器与顶层发表入口。
 *
 * @var frontend\widgets\CommentSection $this
 * @var string $targetType
 * @var int $targetId
 * @var string $targetLabel
 * @var common\models\Comment[] $comments
 * @var common\models\Comment[] $roots
 * @var array $childrenByParent
 * @var array $likeCounts
 * @var array $likedIds
 * @var bool $isAdmin
 */

use yii\helpers\Html;

$sectionId = 'comments-' . $targetType . '-' . $targetId;
?>
<section class="comment-section" id="<?= Html::encode($sectionId) ?>" aria-labelledby="<?= Html::encode($sectionId) ?>-title">
    <header class="comment-section-heading">
        <div>
            <p class="community-kicker">COMMUNITY</p>
            <h2 id="<?= Html::encode($sectionId) ?>-title"><?= Html::encode($targetLabel) ?>留言</h2>
        </div>
        <span class="comment-total"><?= count($comments) ?> 条讨论</span>
    </header>

    <?php if (Yii::$app->user->isGuest): ?>
        <div class="comment-login-prompt">
            <span>登录后可以发表留言、回复和点赞。</span>
            <?= Html::a('前往登录', ['/site/login'], ['class' => 'btn btn-success']) ?>
        </div>
    <?php else: ?>
        <div class="comment-compose">
            <?= $this->render('_form', [
                'targetType' => $targetType,
                'targetId' => $targetId,
                'parentId' => null,
                'placeholder' => '分享你的观点……',
                'submitLabel' => '发表留言',
            ]) ?>
        </div>
    <?php endif ?>

    <?php if (!$roots): ?>
        <p class="comment-empty">还没有留言，来写下第一条讨论吧。</p>
    <?php else: ?>
        <div class="comment-list">
            <?php foreach ($roots as $comment): ?>
                <?= $this->render('_item', [
                    'comment' => $comment,
                    'childrenByParent' => $childrenByParent,
                    'likeCounts' => $likeCounts,
                    'likedIds' => $likedIds,
                    'targetType' => $targetType,
                    'targetId' => $targetId,
                    'isAdmin' => $isAdmin,
                    'depth' => 0,
                ]) ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>

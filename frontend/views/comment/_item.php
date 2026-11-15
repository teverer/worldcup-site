<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 单条留言、点赞、回复和删除操作。
 *
 * @var frontend\widgets\CommentSection $this
 * @var common\models\Comment $comment
 * @var array $childrenByParent
 * @var array $likeCounts
 * @var array $likedIds
 * @var string $targetType
 * @var int $targetId
 * @var bool $isAdmin
 * @var int $depth
 */

use yii\helpers\Html;

$user = $comment->user;
$author = $user ? ($user->nickname ?: $user->username) : '未知用户';
$initial = mb_substr($author, 0, 1, 'UTF-8');
$isOwner = !Yii::$app->user->isGuest && $comment->isOwnedBy(Yii::$app->user->id);
$canDelete = $isOwner || $isAdmin;
$liked = isset($likedIds[(int) $comment->id]);
$likeCount = isset($likeCounts[(int) $comment->id]) ? $likeCounts[(int) $comment->id] : 0;
$children = isset($childrenByParent[(int) $comment->id])
    ? $childrenByParent[(int) $comment->id]
    : [];
?>
<article class="comment-item" id="comment-<?= (int) $comment->id ?>">
    <div class="comment-avatar" aria-hidden="true"><?= Html::encode($initial) ?></div>
    <div class="comment-body">
        <header class="comment-meta">
            <strong><?= Html::encode($author) ?></strong>
            <time datetime="<?= Html::encode($comment->create_time) ?>">
                <?= Html::encode(Yii::$app->formatter->asDatetime($comment->create_time, 'php:Y-m-d H:i')) ?>
            </time>
        </header>
        <div class="comment-content"><?= nl2br(Html::encode($comment->content)) ?></div>
        <div class="comment-actions">
            <?php if (Yii::$app->user->isGuest || $liked): ?>
                <span class="comment-like is-static">赞 <?= (int) $likeCount ?></span>
            <?php else: ?>
                <?= Html::beginForm(['/comment/like', 'id' => $comment->id], 'post', ['class' => 'comment-inline-form']) ?>
                    <?= Html::submitButton('赞 ' . (int) $likeCount, ['class' => 'btn btn-link comment-like']) ?>
                <?= Html::endForm() ?>
            <?php endif ?>

            <?php if (!Yii::$app->user->isGuest): ?>
                <details class="comment-reply-box">
                    <summary>回复</summary>
                    <?= $this->render('_form', [
                        'targetType' => $targetType,
                        'targetId' => $targetId,
                        'parentId' => $comment->id,
                        'placeholder' => '回复 ' . $author . '……',
                        'submitLabel' => '发表回复',
                    ]) ?>
                </details>
            <?php endif ?>

            <?php if ($canDelete): ?>
                <?= Html::beginForm(['/comment/delete', 'id' => $comment->id], 'post', ['class' => 'comment-inline-form']) ?>
                    <?= Html::submitButton('删除', [
                        'class' => 'btn btn-link comment-delete',
                        'data-confirm' => '确定删除这条留言及其回复吗？',
                    ]) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
        </div>

        <?php if ($children): ?>
            <div class="comment-replies">
                <?php foreach ($children as $child): ?>
                    <?= $this->render('_item', [
                        'comment' => $child,
                        'childrenByParent' => $childrenByParent,
                        'likeCounts' => $likeCounts,
                        'likedIds' => $likedIds,
                        'targetType' => $targetType,
                        'targetId' => $targetId,
                        'isAdmin' => $isAdmin,
                        'depth' => $depth + 1,
                    ]) ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</article>

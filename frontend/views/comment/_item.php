<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 留言簿的一条记录、同感、回复与删除。
 *
 * @var frontend\widgets\CommentSection $this
 * @var common\models\Comment $comment
 * @var array $childrenByParent
 * @var array $authorsById
 * @var array $likeCounts
 * @var array $likedIds
 * @var string $targetType
 * @var int $targetId
 * @var bool $isAdmin
 * @var int|null $currentUserId
 * @var int $depth
 */

use frontend\widgets\CommentSection;
use yii\helpers\Html;

$author = CommentSection::authorName($comment);
$initial = CommentSection::sealInitial($author);
$sealColor = CommentSection::sealColor((int) $comment->user_id);
$isOwner = $currentUserId !== null && (int) $comment->user_id === (int) $currentUserId;
$canDelete = $isOwner || $isAdmin;
$liked = isset($likedIds[(int) $comment->id]);
$likeCount = isset($likeCounts[(int) $comment->id]) ? (int) $likeCounts[(int) $comment->id] : 0;
$children = isset($childrenByParent[(int) $comment->id])
    ? $childrenByParent[(int) $comment->id]
    : [];
$visibleChildren = array_slice($children, 0, 2);
$hiddenChildren = array_slice($children, 2);
$isReply = $depth > 0;
$parentAuthor = $comment->parent_id !== null && isset($authorsById[(int) $comment->parent_id])
    ? $authorsById[(int) $comment->parent_id]
    : null;
$dateText = Yii::$app->formatter->asDate($comment->create_time, 'php:m-d');
$timeText = Yii::$app->formatter->asTime($comment->create_time, 'php:H:i');
$fullTime = Yii::$app->formatter->asDatetime($comment->create_time, 'php:Y-m-d H:i');
$entryClass = $isReply ? 'ledger-reply' : 'ledger-entry';
?>
<article class="<?= $entryClass ?>" id="comment-<?= (int) $comment->id ?>">
    <?php if (!$isReply): ?>
        <div class="ledger-rail">
            <a href="#comment-<?= (int) $comment->id ?>" title="<?= Html::encode($fullTime) ?>">
                <span class="day"><?= Html::encode($dateText) ?></span>
                <span class="time"><?= Html::encode($timeText) ?></span>
            </a>
        </div>
    <?php endif ?>
    <div class="ledger-body">
        <header class="ledger-meta">
            <span class="seal" style="background:<?= Html::encode($sealColor) ?>"
                  aria-hidden="true"><?= Html::encode($initial) ?></span>
            <span class="ledger-name"><?= Html::encode($author) ?></span>
            <?php if ($isOwner): ?>
                <span class="ledger-you">你</span>
            <?php endif ?>
            <?php if ($isReply): ?>
                <a class="ledger-reply-time" href="#comment-<?= (int) $comment->id ?>"
                   title="<?= Html::encode($fullTime) ?>"><?= Html::encode($dateText . ' ' . $timeText) ?></a>
            <?php endif ?>
        </header>

        <p class="ledger-text">
            <?php if ($depth > 1 && $parentAuthor !== null): ?><span class="ledger-at">回复 @<?= Html::encode($parentAuthor) ?>：</span><?php endif ?>
            <?= nl2br(Html::encode($comment->content)) ?>
        </p>

        <div class="ledger-actions">
            <?php if ($currentUserId === null || $liked): ?>
                <span class="ledger-action ledger-action--static<?= $liked ? ' is-on' : '' ?>">
                    <span>同感</span>
                    <?php if ($likeCount > 0): ?><span class="num"><?= $likeCount ?></span><?php endif ?>
                </span>
            <?php else: ?>
                <?= Html::beginForm(['/comment/like', 'id' => $comment->id], 'post', ['class' => 'ledger-inline-form']) ?>
                    <?= Html::submitButton(
                        '<span>同感</span>' . ($likeCount > 0 ? '<span class="num">' . $likeCount . '</span>' : ''),
                        ['class' => 'ledger-action', 'encode' => false]
                    ) ?>
                <?= Html::endForm() ?>
            <?php endif ?>

            <?php if ($currentUserId !== null): ?>
                <details class="ledger-reply-box">
                    <summary class="ledger-action">
                        <span class="ledger-reply-open-label">回复</span>
                        <span class="ledger-reply-close-label">收起</span>
                    </summary>
                    <?= $this->render('_form', [
                        'targetType' => $targetType,
                        'targetId' => $targetId,
                        'parentId' => $comment->id,
                        'placeholder' => '回复 ' . $author . '……',
                        'submitLabel' => '回复',
                        'isReply' => true,
                    ]) ?>
                </details>
            <?php endif ?>

            <?php if ($canDelete): ?>
                <?= Html::beginForm(['/comment/delete', 'id' => $comment->id], 'post', ['class' => 'ledger-inline-form']) ?>
                    <?= Html::submitButton('删除', [
                        'class' => 'ledger-action ledger-action--quiet ledger-action--danger',
                        'data-confirm' => '确定删除这条留言及其回复吗？',
                    ]) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
        </div>

        <?php if ($children): ?>
            <div class="ledger-replies">
                <?php foreach ($visibleChildren as $child): ?>
                    <?= $this->render('_item', [
                        'comment' => $child,
                        'childrenByParent' => $childrenByParent,
                        'authorsById' => $authorsById,
                        'likeCounts' => $likeCounts,
                        'likedIds' => $likedIds,
                        'targetType' => $targetType,
                        'targetId' => $targetId,
                        'isAdmin' => $isAdmin,
                        'currentUserId' => $currentUserId,
                        'depth' => $depth + 1,
                    ]) ?>
                <?php endforeach ?>
                <?php if ($hiddenChildren): ?>
                    <details class="ledger-more">
                        <summary>展开另外 <?= count($hiddenChildren) ?> 条回复</summary>
                        <?php foreach ($hiddenChildren as $child): ?>
                            <?= $this->render('_item', [
                                'comment' => $child,
                                'childrenByParent' => $childrenByParent,
                                'authorsById' => $authorsById,
                                'likeCounts' => $likeCounts,
                                'likedIds' => $likedIds,
                                'targetType' => $targetType,
                                'targetId' => $targetId,
                                'isAdmin' => $isAdmin,
                                'currentUserId' => $currentUserId,
                                'depth' => $depth + 1,
                            ]) ?>
                        <?php endforeach ?>
                    </details>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
</article>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 一条主留言；回复拍平在其下，最多显示两条，其余可展开。
 *
 * @var frontend\widgets\CommentSection $this
 * @var common\models\Comment $comment
 * @var common\models\Comment[] $replies
 * @var array $authorsById
 * @var array $likeCounts
 * @var array $likedIds
 * @var string $targetType
 * @var int $targetId
 * @var bool $isAdmin
 * @var int|null $currentUserId
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
$visibleReplies = array_slice($replies, 0, 2);
$hiddenReplies = array_slice($replies, 2);
$fullTime = Yii::$app->formatter->asDatetime($comment->create_time, 'php:Y-m-d H:i');
$shortTime = Yii::$app->formatter->asDatetime($comment->create_time, 'php:m-d H:i');
?>
<article class="ledger-entry" id="comment-<?= (int) $comment->id ?>">
    <span class="seal seal--lg ledger-avatar" style="background:<?= Html::encode($sealColor) ?>"
          aria-hidden="true"><?= Html::encode($initial) ?></span>

    <div class="ledger-body">
        <header class="ledger-meta">
            <span class="ledger-name"><?= Html::encode($author) ?></span>
            <?php if ($isOwner): ?>
                <span class="ledger-you">你</span>
            <?php endif ?>
            <time class="ledger-time" datetime="<?= Html::encode($fullTime) ?>"><?= Html::encode($shortTime) ?></time>
        </header>

        <p class="ledger-text"><?= nl2br(Html::encode($comment->content)) ?></p>

        <div class="ledger-actions">
            <?php if ($currentUserId === null || $liked): ?>
                <span class="ledger-action ledger-action--static<?= $liked ? ' is-on' : '' ?>"
                      aria-label="点赞<?= $likeCount > 0 ? '，' . $likeCount . ' 人' : '' ?>">
                    <svg class="ledger-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 10v10H4V10h3Zm0 10h9.2a2 2 0 0 0 2-1.6l1.2-6A2 2 0 0 0 17.5 10H13V6a2 2 0 0 0-2-2l-4 6v10Z"></path>
                    </svg>
                    <span>点赞</span>
                    <?php if ($likeCount > 0): ?><span class="num"><?= $likeCount ?></span><?php endif ?>
                </span>
            <?php else: ?>
                <?= Html::beginForm(['/comment/like', 'id' => $comment->id], 'post', ['class' => 'ledger-inline-form']) ?>
                    <?= Html::submitButton(
                        '<svg class="ledger-icon" viewBox="0 0 24 24" aria-hidden="true">'
                        . '<path d="M7 10v10H4V10h3Zm0 10h9.2a2 2 0 0 0 2-1.6l1.2-6A2 2 0 0 0 17.5 10H13V6a2 2 0 0 0-2-2l-4 6v10Z"></path>'
                        . '</svg><span>点赞</span>'
                        . ($likeCount > 0 ? '<span class="num">' . $likeCount . '</span>' : ''),
                        ['class' => 'ledger-action', 'encode' => false, 'aria-label' => '点赞']
                    ) ?>
                <?= Html::endForm() ?>
            <?php endif ?>

            <?php if ($currentUserId !== null): ?>
                <details class="ledger-reply-box">
                    <summary class="ledger-action">
                        <svg class="ledger-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 11.5a7.5 7.5 0 0 1-10.9 6.7L4 20l1.3-4.2A7.5 7.5 0 1 1 20 11.5Z"></path>
                        </svg>
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
                    <?= Html::submitButton(
                        '<svg class="ledger-icon" viewBox="0 0 24 24" aria-hidden="true">'
                        . '<path d="M4 7h16M9 7V5h6v2m-8 0 1 13h8l1-13"></path></svg><span>删除</span>',
                        [
                            'class' => 'ledger-action ledger-action--quiet ledger-action--danger',
                            'encode' => false,
                            'data-confirm' => '确定删除这条留言及其回复吗？',
                        ]
                    ) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
        </div>

        <?php if ($replies): ?>
            <div class="ledger-replies">
                <?php foreach ($visibleReplies as $reply): ?>
                    <?= $this->render('_reply', [
                        'comment' => $reply,
                        'rootId' => (int) $comment->id,
                        'parentAuthor' => isset($authorsById[(int) $reply->parent_id])
                            ? $authorsById[(int) $reply->parent_id]
                            : null,
                        'likeCounts' => $likeCounts,
                        'likedIds' => $likedIds,
                        'targetType' => $targetType,
                        'targetId' => $targetId,
                        'isAdmin' => $isAdmin,
                        'currentUserId' => $currentUserId,
                    ]) ?>
                <?php endforeach ?>

                <?php if ($hiddenReplies): ?>
                    <details class="ledger-more">
                        <summary>
                            <span class="ledger-more-open">查看全部 <?= count($replies) ?> 条回复</span>
                            <span class="ledger-more-close">收起回复</span>
                        </summary>
                        <?php foreach ($hiddenReplies as $reply): ?>
                            <?= $this->render('_reply', [
                                'comment' => $reply,
                                'rootId' => (int) $comment->id,
                                'parentAuthor' => isset($authorsById[(int) $reply->parent_id])
                                    ? $authorsById[(int) $reply->parent_id]
                                    : null,
                                'likeCounts' => $likeCounts,
                                'likedIds' => $likedIds,
                                'targetType' => $targetType,
                                'targetId' => $targetId,
                                'isAdmin' => $isAdmin,
                                'currentUserId' => $currentUserId,
                            ]) ?>
                        <?php endforeach ?>
                    </details>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
</article>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 留言簿：页尾的社区记录、输入与空态。
 *
 * @var frontend\widgets\CommentSection $this
 * @var string $targetType
 * @var int $targetId
 * @var common\models\Comment[] $comments
 * @var common\models\Comment[] $roots
 * @var array $childrenByParent
 * @var array $authorsById
 * @var array $likeCounts
 * @var array $likedIds
 * @var bool $isAdmin
 * @var int|null $currentUserId
 * @var string $prompt
 * @var string $placeholder
 * @var string[] $starters
 * @var int $participantCount
 * @var string $heading
 * @var string $headingTag
 * @var string $intro
 * @var string $emptyTitle
 */

use yii\helpers\Html;

$total = count($comments);
$isGuest = $currentUserId === null;
$loginReturnUrl = Yii::$app->request->url . '#comments';
?>
<section class="ledger" id="comments" aria-labelledby="comments-title">
    <header class="ledger-head">
        <div class="ledger-title-row">
            <?= Html::tag($headingTag, Html::encode($heading), ['class' => 'ledger-title', 'id' => 'comments-title']) ?>
        </div>
        <?php if ($total > 0): ?>
            <span class="ledger-count">
                <b><?= $total ?></b> 条<?php if ($participantCount > 0): ?> · <b><?= $participantCount ?></b> 人<?php endif ?>
            </span>
        <?php endif ?>
    </header>
    <?php if ($intro !== ''): ?>
        <p class="ledger-intro"><?= Html::encode($intro) ?></p>
    <?php endif ?>
    <p class="ledger-prompt"><?= Html::encode($prompt) ?></p>

    <div class="ledger-compose">
        <div class="ledger-rail"><span class="day">今天</span></div>
        <div class="ledger-body">
            <?php if ($isGuest): ?>
                <p class="guest-line">
                    登录后，在这一页留一笔。<?= Html::a('登录', ['/site/login', 'returnUrl' => $loginReturnUrl]) ?>
                    <span class="muted">还没有账号？<?= Html::a('注册', ['/site/signup', 'returnUrl' => $loginReturnUrl]) ?></span>
                </p>
            <?php else: ?>
                <?= $this->render('_form', [
                    'targetType' => $targetType,
                    'targetId' => $targetId,
                    'parentId' => null,
                    'placeholder' => $placeholder,
                    'submitLabel' => '记下',
                    'isReply' => false,
                ]) ?>
            <?php endif ?>
        </div>
    </div>

    <?php if (!$roots): ?>
        <div class="ledger-empty">
            <p class="ledger-empty-title"><?= Html::encode($emptyTitle) ?></p>
            <?php if ($isGuest): ?>
                <p class="ledger-empty-sub">登录后，成为第一个留下记忆的人。</p>
            <?php else: ?>
                <p class="ledger-empty-sub">第一个坐下来的人，通常记得最清楚。可以从这些开始：</p>
                <div class="ledger-starters">
                    <?php foreach ($starters as $starter): ?>
                        <button type="button" class="ledger-starter"
                                data-ledger-starter="<?= Html::encode($starter) ?>"><?= Html::encode($starter) ?></button>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    <?php else: ?>
        <div class="ledger-list">
            <?php foreach ($roots as $comment): ?>
                <?= $this->render('_item', [
                    'comment' => $comment,
                    'childrenByParent' => $childrenByParent,
                    'authorsById' => $authorsById,
                    'likeCounts' => $likeCounts,
                    'likedIds' => $likedIds,
                    'targetType' => $targetType,
                    'targetId' => $targetId,
                    'isAdmin' => $isAdmin,
                    'currentUserId' => $currentUserId,
                    'depth' => 0,
                ]) ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>

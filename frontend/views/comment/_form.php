<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 留言簿的输入行（顶层留言与回复共用）。
 *
 * @var frontend\widgets\CommentSection $this
 * @var string $targetType
 * @var int $targetId
 * @var int|null $parentId
 * @var string $placeholder
 * @var string $submitLabel
 * @var bool $isReply
 */

use frontend\widgets\CommentSection;
use yii\helpers\Html;

$formId = 'comment-form-' . $targetType . '-' . $targetId . '-' . ($parentId ?: 'root');
$identity = Yii::$app->user->identity;
$author = $identity ? ($identity->nickname ?: $identity->username) : '';
$initial = CommentSection::sealInitial($author);
$sealColor = CommentSection::sealColor($identity ? (int) $identity->id : 0);
$isReply = isset($isReply) ? (bool) $isReply : false;
?>
<?= Html::beginForm(['/comment/create'], 'post', ['class' => 'ledger-form', 'id' => $formId]) ?>
    <?= Html::hiddenInput('CommentForm[targetType]', $targetType) ?>
    <?= Html::hiddenInput('CommentForm[targetId]', $targetId) ?>
    <?php if ($parentId !== null): ?>
        <?= Html::hiddenInput('CommentForm[parentId]', $parentId) ?>
    <?php endif ?>
    <div class="composer-row<?= $isReply ? ' composer-row--reply' : '' ?>">
        <span class="seal<?= $isReply ? '' : ' seal--lg' ?>" style="background:<?= Html::encode($sealColor) ?>"
              aria-hidden="true"><?= Html::encode($initial) ?></span>
        <div class="composer-field">
            <label class="sr-only" for="<?= Html::encode($formId) ?>-content">留言内容</label>
            <?= Html::textarea('CommentForm[content]', '', [
                'id' => $formId . '-content',
                'class' => 'composer-input',
                'rows' => $isReply ? 2 : 1,
                'maxlength' => 5000,
                'required' => true,
                'placeholder' => $placeholder,
                'data-ledger-input' => '1',
            ]) ?>
            <div class="composer-foot">
                <?= Html::submitButton($submitLabel, ['class' => 'btn-record']) ?>
            </div>
        </div>
    </div>
<?= Html::endForm() ?>

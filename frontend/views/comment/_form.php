<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 顶层留言和回复表单。
 *
 * @var frontend\widgets\CommentSection $this
 * @var string $targetType
 * @var int $targetId
 * @var int|null $parentId
 * @var string $placeholder
 * @var string $submitLabel
 */

use yii\helpers\Html;

$formId = 'comment-form-' . $targetType . '-' . $targetId . '-' . ($parentId ?: 'root');
?>
<?= Html::beginForm(['/comment/create'], 'post', ['class' => 'comment-form', 'id' => $formId]) ?>
    <?= Html::hiddenInput('CommentForm[targetType]', $targetType) ?>
    <?= Html::hiddenInput('CommentForm[targetId]', $targetId) ?>
    <?php if ($parentId !== null): ?>
        <?= Html::hiddenInput('CommentForm[parentId]', $parentId) ?>
    <?php endif ?>
    <label class="sr-only" for="<?= Html::encode($formId) ?>-content">留言内容</label>
    <?= Html::textarea('CommentForm[content]', '', [
        'id' => $formId . '-content',
        'class' => 'form-control',
        'rows' => $parentId === null ? 4 : 3,
        'maxlength' => 5000,
        'required' => true,
        'placeholder' => $placeholder,
    ]) ?>
    <div class="comment-form-footer">
        <span>最多 5000 字</span>
        <?= Html::submitButton($submitLabel, ['class' => 'btn btn-success']) ?>
    </div>
<?= Html::endForm() ?>

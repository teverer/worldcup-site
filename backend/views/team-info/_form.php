<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队信息编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\TeamInfo $model
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->errorSummary($model) ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 50, 'autofocus' => true]) ?>
    <?= $form->field($model, 'slogan')->textInput(['maxlength' => 100]) ?>
    <?= $form->field($model, 'logoFile')->fileInput([
        'accept' => 'image/jpeg,image/png,image/webp,image/gif,image/avif',
    ])->hint('支持 JPG、PNG、WebP、GIF、AVIF，最大 5 MB。上传新图片会替换旧文件。') ?>
    <?php if ($model->logo): ?>
        <p class="help-block">当前 Logo：<code><?= Html::encode($model->logo) ?></code></p>
    <?php endif ?>
    <?= $form->field($model, 'intro')->textarea(['rows' => 8, 'maxlength' => 10000]) ?>
    <div class="form-group">
        <?= Html::submitButton('保存团队信息', ['class' => 'btn btn-success']) ?>
        <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
<?php ActiveForm::end(); ?>

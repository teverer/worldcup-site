<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 成员编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\Member $model
 */

use common\models\Member;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->errorSummary($model) ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => 50, 'autofocus' => true]) ?>
            <?= $form->field($model, 'student_id')->textInput(['maxlength' => 20]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'role')->dropDownList(Member::roleOptions()) ?>
            <?= $form->field($model, 'duty')->textInput(['maxlength' => 100]) ?>
        </div>
    </div>
    <?= $form->field($model, 'avatarFile')->fileInput([
        'accept' => 'image/jpeg,image/png,image/webp,image/gif,image/avif',
    ])->hint('支持 JPG、PNG、WebP、GIF、AVIF，最大 5 MB。') ?>
    <?php if ($model->avatar): ?>
        <p class="help-block">当前照片：<code><?= Html::encode($model->avatar) ?></code></p>
    <?php endif ?>
    <?= $form->field($model, 'intro')->textarea(['rows' => 6, 'maxlength' => 10000]) ?>
    <div class="form-group">
        <?= Html::submitButton('保存成员', ['class' => 'btn btn-success']) ?>
        <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
<?php ActiveForm::end(); ?>

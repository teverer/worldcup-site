<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 作业文件上传表单。
 *
 * @var yii\web\View $this
 * @var common\models\Work $model
 */

use common\models\Work;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->errorSummary($model) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => 100, 'autofocus' => true]) ?>
    <?= $form->field($model, 'type')->dropDownList(Work::typeOptions()) ?>
    <?= $form->field($model, 'description')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'workFile')->fileInput([
        'accept' => '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.7z,.txt,.md',
    ])->hint('支持 PDF、Office、常见压缩包和文本文件，最大 20 MB。') ?>
    <?php if (!$model->isNewRecord && $model->file_path): ?>
        <p class="help-block">当前文件：<code><?= Html::encode($model->file_path) ?></code></p>
    <?php endif ?>
    <div class="form-group">
        <?= Html::submitButton('保存作业文件', ['class' => 'btn btn-success']) ?>
        <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
<?php ActiveForm::end(); ?>

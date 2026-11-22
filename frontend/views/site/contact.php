<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 联系我们
 *
 * @var yii\web\View $this
 * @var yii\bootstrap\ActiveForm $form
 * @var frontend\models\ContactForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '联系我们';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-page form-page--wide site-contact">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>有问题或建议？填写下面的表单，我们会尽快回复。</p>

    <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>

    <?= $form->field($model, 'name')->textInput(['autofocus' => true])->label('姓名') ?>

    <?= $form->field($model, 'email')->label('邮箱') ?>

    <?= $form->field($model, 'subject')->label('主题') ?>

    <?= $form->field($model, 'body')->textarea(['rows' => 6])->label('内容') ?>

    <div class="form-group">
        <?= Html::submitButton('发送', ['class' => 'btn btn-primary', 'name' => 'contact-button']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

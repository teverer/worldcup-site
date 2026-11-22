<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 重发验证邮件
 *
 * @var yii\web\View $this
 * @var yii\bootstrap\ActiveForm $form
 * @var frontend\models\ResetPasswordForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '重发验证邮件';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-page site-resend-verification-email">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>填写注册邮箱，我们会重新发送验证邮件。</p>

    <?php $form = ActiveForm::begin(['id' => 'resend-verification-email-form']); ?>

    <?= $form->field($model, 'email')->textInput(['autofocus' => true])->label('邮箱') ?>

    <div class="form-group">
        <?= Html::submitButton('重新发送验证邮件', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <p class="form-page-footer"><?= Html::a('返回登录', ['/site/login']) ?></p>
</div>

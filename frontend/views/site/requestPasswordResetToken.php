<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 申请重置密码
 *
 * @var yii\web\View $this
 * @var yii\bootstrap\ActiveForm $form
 * @var frontend\models\PasswordResetRequestForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '找回密码';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-page site-request-password-reset">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>填写注册邮箱，我们会把重置密码的链接发送到那里。</p>

    <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

    <?= $form->field($model, 'email')->textInput(['autofocus' => true])->label('邮箱') ?>

    <div class="form-group">
        <?= Html::submitButton('发送重置链接', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <p class="form-page-footer"><?= Html::a('返回登录', ['/site/login']) ?></p>
</div>

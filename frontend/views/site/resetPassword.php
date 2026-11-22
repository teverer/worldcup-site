<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 重置密码
 *
 * @var yii\web\View $this
 * @var yii\bootstrap\ActiveForm $form
 * @var frontend\models\ResetPasswordForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '设置新密码';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-page site-reset-password">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>请输入新的密码。</p>

    <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>

    <?= $form->field($model, 'password')->passwordInput(['autofocus' => true])->label('新密码') ?>

    <div class="form-group">
        <?= Html::submitButton('保存新密码', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <p class="form-page-footer"><?= Html::a('返回登录', ['/site/login']) ?></p>
</div>

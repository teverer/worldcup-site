<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 登录页
 *
 * @var yii\web\View $this
 * @var yii\bootstrap\ActiveForm $form
 * @var common\models\LoginForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '登录';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-page site-login">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>登录后可以发表留言、回复和点赞。</p>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

    <?= $form->field($model, 'username')->textInput(['autofocus' => true])->label('用户名') ?>

    <?= $form->field($model, 'password')->passwordInput()->label('密码') ?>

    <div class="form-group">
        <?= Html::submitButton('登录', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <p class="form-page-footer">
        还没有账号？<?= Html::a('立即注册', ['/site/signup']) ?>
        <span aria-hidden="true">·</span>
        <?= Html::a('不登录，回到记分牌墙', Yii::$app->homeUrl) ?>
    </p>
</div>

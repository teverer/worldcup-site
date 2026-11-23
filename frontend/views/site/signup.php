<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 注册页
 *
 * @var yii\web\View $this
 * @var yii\bootstrap\ActiveForm $form
 * @var frontend\models\SignupForm $model
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '注册';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-page site-signup">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>创建账号后即可参与讨论。</p>

    <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

    <?= $form->field($model, 'username')->textInput(['autofocus' => true])->label('用户名') ?>

    <?= $form->field($model, 'nickname')->textInput()->label('昵称') ?>

    <?= $form->field($model, 'password')->passwordInput()->label('密码') ?>

    <div class="form-group">
        <?= Html::submitButton('注册', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <p class="form-page-footer">
        已有账号？<?= Html::a('去登录', ['/site/login']) ?>
        <span aria-hidden="true">·</span>
        <?= Html::a('不注册，回到记分牌墙', Yii::$app->homeUrl) ?>
    </p>
</div>

<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '后台登录';
$frontendHome = rtrim((string) (Yii::$app->params['frontendUrl'] ?? 'http://localhost:8080'), '/') . '/';
?>
<?php /* 与前台登录同一张表单卡：标题、说明、字段、按钮、页脚一条线。
         必要差别只有没有注册 / 找回密码；颜色换成工作间的冷纸。 */ ?>
<div class="form-page site-login backend-login">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>使用管理员账号继续。</p>

    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
        <?= $form->field($model, 'username')->textInput(['autofocus' => true, 'placeholder' => '用户名'])->label('用户名') ?>
        <?= $form->field($model, 'password')->passwordInput(['placeholder' => '密码'])->label('密码') ?>
        <div class="form-group">
            <?= Html::submitButton('登录', ['class' => 'btn btn-primary btn-block', 'name' => 'login-button']) ?>
        </div>
    <?php ActiveForm::end(); ?>

    <p class="form-page-footer">
        <?= Html::a('查看前台', $frontendHome) ?>
    </p>
</div>

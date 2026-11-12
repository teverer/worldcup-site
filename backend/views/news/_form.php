<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261112
 * 模块:留言与资讯 —— 后台资讯编辑与封面上传表单。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

use common\models\News;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->errorSummary($model) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => 100, 'autofocus' => true]) ?>
    <?= $form->field($model, 'author')->textInput(['maxlength' => 50]) ?>
    <?= $form->field($model, 'status')->dropDownList(News::statusOptions()) ?>
    <?= $form->field($model, 'content')->textarea([
        'rows' => 14,
        'maxlength' => 20000,
    ])->hint('支持基础 HTML；保存时会清理脚本和危险标签。') ?>
    <?= $form->field($model, 'coverFile')->fileInput([
        'accept' => 'image/jpeg,image/png,image/webp,image/gif',
    ])->hint('支持 JPG、PNG、WebP、GIF，最大 5 MB。') ?>
    <?php if ($model->cover): ?>
        <div class="form-group">
            <label>当前封面路径</label>
            <p class="form-control-static"><code><?= Html::encode($model->cover) ?></code></p>
            <p class="help-block">上传新封面后会替换当前文件。</p>
        </div>
    <?php endif ?>
    <div class="form-group">
        <?= Html::submitButton('保存资讯', ['class' => 'btn btn-success']) ?>
        <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
<?php ActiveForm::end(); ?>

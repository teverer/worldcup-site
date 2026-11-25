<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 资料编辑表单。
 * @var yii\web\View $this
 * @var yii\db\ActiveRecord $model
 */
use common\models\Team;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$form = ActiveForm::begin();
echo $form->errorSummary($model);
?>
<?php if ($model instanceof Team): ?>
    <div class="form-grid">
        <?= $form->field($model, 'name')->textInput(['maxlength' => 50, 'autofocus' => true]) ?>
        <?= $form->field($model, 'code')->textInput(['maxlength' => 10, 'placeholder' => '如 ARG']) ?>
    </div>
    <div class="form-grid">
        <?= $form->field($model, 'continent')->dropDownList(array_combine(Team::CONTINENTS, Team::CONTINENTS), ['prompt' => '请选择大洲']) ?>
        <?= $form->field($model, 'flag')->textInput(['maxlength' => 255, 'placeholder' => '如 uploads/team/bra.png']) ?>
    </div>
    <?= $form->field($model, 'logo')->textInput(['maxlength' => 255, 'placeholder' => '如 uploads/team/bra-logo.png']) ?>
<?php else: ?>
    <div class="form-grid">
        <?= $form->field($model, 'name')->textInput(['maxlength' => 50, 'autofocus' => true]) ?>
        <?= $form->field($model, 'name_zh')->textInput(['maxlength' => 50, 'placeholder' => '选填，前台优先显示']) ?>
    </div>
    <div class="form-grid">
        <?= $form->field($model, 'birth_date')->input('date', ['max' => date('Y-m-d')]) ?>
        <?= $form->field($model, 'nationality')->textInput(['maxlength' => 50, 'placeholder' => '如 阿根廷']) ?>
    </div>
    <div class="form-grid">
        <?= $form->field($model, 'position')->textInput(['maxlength' => 20, 'placeholder' => '门将 / 后卫 / 中场 / 前锋']) ?>
        <?= $form->field($model, 'photo')->textInput(['maxlength' => 255, 'placeholder' => '如 uploads/player/messi.png']) ?>
    </div>
<?php endif ?>
<?= $form->field($model, 'intro')->textarea(['rows' => 6, 'maxlength' => 15000, 'placeholder' => '选填；一两句话说明这名' . ($model instanceof Team ? '球队' : '球员') . '。']) ?>
<div class="form-group">
    <?= Html::submitButton('保存资料', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

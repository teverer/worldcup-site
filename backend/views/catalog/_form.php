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
echo $form->field($model, 'name')->textInput(['maxlength' => 50]);
if ($model instanceof Team) {
    echo $form->field($model, 'code')->textInput(['maxlength' => 10]);
    echo $form->field($model, 'continent')->dropDownList(array_combine(Team::CONTINENTS, Team::CONTINENTS), ['prompt' => '请选择大洲']);
    echo $form->field($model, 'flag')->textInput(['maxlength' => 255])->hint('填写前台 images/ 或 uploads/ 下的图片相对路径，例如 uploads/team/bra.png。');
    echo $form->field($model, 'logo')->textInput(['maxlength' => 255]);
} else {
    echo $form->field($model, 'birth_date')->input('date', ['max' => date('Y-m-d')]);
    echo $form->field($model, 'nationality')->textInput(['maxlength' => 50]);
    echo $form->field($model, 'position')->textInput(['maxlength' => 20])->hint('例如：门将、后卫、中场、前锋。');
    echo $form->field($model, 'photo')->textInput(['maxlength' => 255])->hint('填写前台 images/ 或 uploads/ 下的图片相对路径。');
}
echo $form->field($model, 'intro')->textarea(['rows' => 6, 'maxlength' => 15000]);
?>
<div class="form-group">
    <?= Html::submitButton('保存资料', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

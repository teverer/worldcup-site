<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 届次新增/编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\Tournament $model
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin();
echo $form->errorSummary($model);
echo $form->field($model, 'year')->input('number', ['min' => 1900, 'max' => 2100])
    ->hint('例如 2022,同一届只能出现一次。');
echo $form->field($model, 'name')->textInput(['maxlength' => 100])
    ->hint('例如 2022年卡塔尔世界杯。');
echo $form->field($model, 'host_country')->textInput(['maxlength' => 100])
    ->hint('多国合办时用“/”分隔,如 韩国/日本。');
echo $form->field($model, 'team_count')->dropDownList([24 => '24 队', 32 => '32 队', 48 => '48 队'], ['prompt' => '未填写']);
echo $form->field($model, 'format')->textInput(['maxlength' => 100])
    ->hint('例如 32队/8组/小组前2晋级。');
echo $form->field($model, 'win_points')->dropDownList([2 => '2 分(1986/1990)', 3 => '3 分(1994 起)'], ['prompt' => '请选择']);
echo $form->field($model, 'start_date')->input('date');
echo $form->field($model, 'end_date')->input('date')
    ->hint('闭幕日期不能早于开幕日期。');
?>
<div class="form-group">
    <?= Html::submitButton('保存届次', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

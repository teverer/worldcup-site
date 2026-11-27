<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 参赛队新增/编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\TournamentTeam $model
 */

use common\models\Team;
use common\models\Tournament;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$tournamentOptions = ArrayHelper::map(
    Tournament::find()->orderBy(['year' => SORT_DESC])->all(),
    'id',
    'name'
);
$teamOptions = ArrayHelper::map(
    Team::find()->orderBy(['name' => SORT_ASC])->all(),
    'id',
    'name'
);

$form = ActiveForm::begin();
echo $form->errorSummary($model);
echo $form->field($model, 'tournament_id')->dropDownList($tournamentOptions, ['prompt' => '请选择届次']);
echo $form->field($model, 'team_id')->dropDownList($teamOptions, ['prompt' => '请选择球队']);
echo $form->field($model, 'group_name')->textInput(['maxlength' => 10])
    ->hint('例如 A、B,不同届次的小组数量不同。');
?>
<div class="form-group">
    <?= Html::submitButton('保存参赛队', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

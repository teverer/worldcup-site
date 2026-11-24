<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 比赛新增/编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\Game $model
 */

use common\models\Game;
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
$matchTimeValue = '';
if ($model->match_time && strtotime($model->match_time) !== false) {
    $matchTimeValue = date('Y-m-d\TH:i', strtotime($model->match_time));
}

$form = ActiveForm::begin();
echo $form->errorSummary($model);
echo $form->field($model, 'tournament_id')->dropDownList($tournamentOptions, ['prompt' => '请选择届次']);
?>
<div class="form-grid">
    <?= $form->field($model, 'stage')->dropDownList(Game::stageOptions(), ['prompt' => '请选择比赛阶段']) ?>
    <?= $form->field($model, 'status')->dropDownList(Game::statusOptions()) ?>
</div>
<?= $form->field($model, 'match_time')->input('datetime-local', ['value' => $matchTimeValue])
    ->hint('例如 2026-06-11 18:00，可留空。') ?>
<div class="form-grid">
    <?= $form->field($model, 'home_team_id')->dropDownList($teamOptions, ['prompt' => '请选择主队']) ?>
    <?= $form->field($model, 'away_team_id')->dropDownList($teamOptions, ['prompt' => '请选择客队'])
        ->hint('主队和客队不能相同。') ?>
</div>
<div class="form-grid">
    <?= $form->field($model, 'home_score')->input('number', ['min' => 0, 'max' => 99])
        ->hint('120 分钟（含加时）比分；已结束时必填。') ?>
    <?= $form->field($model, 'away_score')->input('number', ['min' => 0, 'max' => 99])
        ->hint('120 分钟（含加时）比分；已结束时必填。') ?>
</div>
<div class="form-grid">
    <?= $form->field($model, 'home_penalty_score')->input('number', ['min' => 0, 'max' => 30])
        ->hint('没有点球大战请留空。') ?>
    <?= $form->field($model, 'away_penalty_score')->input('number', ['min' => 0, 'max' => 30])
        ->hint('必须与主队成对填写。') ?>
</div>
<?= $form->field($model, 'venue')->textInput(['maxlength' => 50])
    ->hint('例如 卢塞尔球场。') ?>
<div class="form-group">
    <?= Html::submitButton('保存比赛', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

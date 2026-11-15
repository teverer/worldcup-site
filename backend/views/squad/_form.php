<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 参赛名单新增/编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\Squad $model
 */

use common\models\Player;
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
$playerOptions = ArrayHelper::map(
    Player::find()->orderBy(['name' => SORT_ASC])->all(),
    'id',
    function ($player) {
        return $player->nationality
            ? $player->name . '(' . $player->nationality . ')'
            : $player->name;
    }
);

$form = ActiveForm::begin();
echo $form->errorSummary($model);
echo $form->field($model, 'tournament_id')->dropDownList($tournamentOptions, ['prompt' => '请选择届次']);
echo $form->field($model, 'team_id')->dropDownList($teamOptions, ['prompt' => '请选择球队'])
    ->hint('球队必须已经报名参加所选届次。');
echo $form->field($model, 'player_id')->dropDownList($playerOptions, ['prompt' => '请选择球员'])
    ->hint('同一球员在同一届赛事只能属于一支球队。');
echo $form->field($model, 'shirt_number')->input('number', ['min' => 1, 'max' => 99])
    ->hint('填写 1 至 99;同一届同一球队内不能重号,可留空。');
echo $form->field($model, 'position')->textInput(['maxlength' => 20])
    ->hint('可留空,默认参考球员的主要位置。');
?>
<div class="form-group">
    <?= Html::submitButton('保存名单', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 进球记录录入/编辑表单。
 *
 * @var yii\web\View $this
 * @var common\models\Goal $model
 */

use common\models\Game;
use common\models\Player;
use common\models\Team;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$gameOptions = ArrayHelper::map(
    Game::find()
        ->with(['tournament', 'homeTeam', 'awayTeam'])
        ->orderBy(['match_time' => SORT_DESC, 'id' => SORT_DESC])
        ->all(),
    'id',
    function ($game) {
        $prefix = $game->tournament === null ? '' : $game->tournament->year . ' ';
        $time = $game->match_time === null ? '' : '(' . $game->match_time . ')';
        return $prefix . $game->matchTitle . $time;
    }
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
echo $form->field($model, 'game_id')->dropDownList($gameOptions, ['prompt' => '请选择比赛']);
echo $form->field($model, 'team_id')->dropDownList($teamOptions, ['prompt' => '请选择进球归属球队'])
    ->hint('必须在本场比赛的主队和客队中选择;乌龙球请选择受益球队。');
echo $form->field($model, 'player_id')->dropDownList($playerOptions, ['prompt' => '请选择进球球员'])
    ->hint('乌龙球请选择防守方球员。');
echo $form->field($model, 'assist_player_id')->dropDownList($playerOptions, ['prompt' => '无助攻'])
    ->hint('可留空;助攻球员不能与进球球员相同。');
echo $form->field($model, 'minute')->input('number', ['min' => 1, 'max' => 130])
    ->hint('常规时间 1 至 90,加时赛可填至 120,点球大战可填至 130。');
echo $form->field($model, 'is_penalty')->checkbox()->hint('常规比赛中的点球进球。');
echo $form->field($model, 'is_own_goal')->checkbox()->hint('乌龙球,归属球队请选择受益方。');
echo $form->field($model, 'is_shootout')->checkbox()->hint('点球大战进球,统计时会被排除。');
?>
<div class="form-group">
    <?= Html::submitButton('保存进球', ['class' => 'btn btn-success']) ?>
    <?= Html::a('取消', ['index'], ['class' => 'btn btn-default']) ?>
</div>
<?php ActiveForm::end(); ?>

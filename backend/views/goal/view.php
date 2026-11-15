<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台进球记录详情视图。
 *
 * @var yii\web\View $this
 * @var common\models\Goal $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = '进球记录 #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => '进球管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'method' => 'post',
            'confirm' => '确定删除这条进球记录吗?',
        ],
    ]) ?>
    <?php if ($model->game !== null): ?>
        <?= Html::a('返回比赛', ['/game/view', 'id' => $model->game_id], ['class' => 'btn btn-default']) ?>
    <?php endif ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        [
            'attribute' => 'game_id',
            'label' => '比赛',
            'format' => 'raw',
            'value' => $model->game === null
                ? ''
                : Html::a(Html::encode($model->game->matchTitle), ['/game/view', 'id' => $model->game_id]),
        ],
        [
            'label' => '届次',
            'value' => $model->game === null || $model->game->tournament === null
                ? ''
                : $model->game->tournament->name,
        ],
        [
            'attribute' => 'team_id',
            'label' => '进球归属球队',
            'format' => 'raw',
            'value' => $model->team === null
                ? ''
                : Html::a(Html::encode($model->team->name), ['/team/view', 'id' => $model->team_id]),
        ],
        [
            'attribute' => 'player_id',
            'label' => '进球球员',
            'format' => 'raw',
            'value' => $model->player === null
                ? ''
                : Html::a(Html::encode($model->player->name), ['/player/view', 'id' => $model->player_id]),
        ],
        [
            'attribute' => 'assist_player_id',
            'label' => '助攻球员',
            'format' => 'raw',
            'value' => $model->assistPlayer === null
                ? '无'
                : Html::a(Html::encode($model->assistPlayer->name), ['/player/view', 'id' => $model->assist_player_id]),
        ],
        [
            'attribute' => 'minute',
            'value' => $model->minute === null ? '—' : $model->minute,
        ],
        [
            'attribute' => 'is_penalty',
            'value' => (int) $model->is_penalty === 1 ? '是' : '否',
        ],
        [
            'attribute' => 'is_own_goal',
            'value' => (int) $model->is_own_goal === 1 ? '是' : '否',
        ],
        [
            'attribute' => 'is_shootout',
            'value' => (int) $model->is_shootout === 1 ? '是' : '否',
        ],
        'create_time:datetime',
    ],
]) ?>

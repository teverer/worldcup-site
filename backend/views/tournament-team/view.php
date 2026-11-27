<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛队详情视图。
 *
 * @var yii\web\View $this
 * @var common\models\TournamentTeam $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = ($model->team === null ? '参赛队' : $model->team->name)
    . ' - ' . ($model->tournament === null ? '未知届次' : $model->tournament->name);
$this->params['breadcrumbs'][] = ['label' => '参赛队管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'method' => 'post',
            'confirm' => '确定删除这条参赛队记录吗?',
        ],
    ]) ?>
    <?= Html::a('查看名单', ['/squad/index', 'tournamentId' => $model->tournament_id, 'teamId' => $model->team_id],
        ['class' => 'btn btn-default']) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        [
            'attribute' => 'tournament_id',
            'label' => '届次',
            'value' => $model->tournament === null ? '' : $model->tournament->name,
        ],
        [
            'attribute' => 'team_id',
            'label' => '球队',
            'format' => 'raw',
            'value' => $model->team === null
                ? ''
                : Html::a(Html::encode($model->team->name), ['/team/view', 'id' => $model->team_id]),
        ],
        'group_name',
        'create_time:datetime',
    ],
]) ?>

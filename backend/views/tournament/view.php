<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台届次详情视图。
 *
 * @var yii\web\View $this
 * @var common\models\Tournament $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '届次管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'method' => 'post',
            'confirm' => '删除本届次将级联删除其参赛队、名单、比赛和进球记录,且不可恢复。确定继续吗?',
        ],
    ]) ?>
    <?= Html::a('参赛队', ['/tournament-team/index', 'tournamentId' => $model->id], ['class' => 'btn btn-default']) ?>
    <?= Html::a('参赛名单', ['/squad/index', 'tournamentId' => $model->id], ['class' => 'btn btn-default']) ?>
    <?= Html::a('比赛', ['/game/index', 'tournamentId' => $model->id], ['class' => 'btn btn-default']) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'year',
        'name',
        'host_country',
        'team_count',
        'format',
        'win_points',
        'start_date:date',
        'end_date:date',
        'create_time:datetime',
    ],
]) ?>

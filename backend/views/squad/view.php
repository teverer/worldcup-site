<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛名单详情视图。
 *
 * @var yii\web\View $this
 * @var common\models\Squad $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = ($model->player === null ? '球员' : $model->player->name)
    . ' - ' . ($model->tournament === null ? '未知届次' : $model->tournament->name);
$this->params['breadcrumbs'][] = ['label' => '参赛名单管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'method' => 'post',
            'confirm' => '确定删除这条名单记录吗?',
        ],
    ]) ?>
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
        [
            'attribute' => 'player_id',
            'label' => '球员',
            'format' => 'raw',
            'value' => $model->player === null
                ? ''
                : Html::a(Html::encode($model->player->name), ['/player/view', 'id' => $model->player_id]),
        ],
        'shirt_number',
        'position',
        'create_time:datetime',
    ],
]) ?>

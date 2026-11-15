<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛队列表视图。
 *
 * @var yii\web\View $this
 * @var common\models\TournamentTeamSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */

use common\models\Tournament;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '参赛队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('新增参赛队', ['create'], ['class' => 'btn btn-success']) ?></p>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin:20px 0']) ?>
    <div class="form-group">
        <?= Html::label('关键词', 'tt-q', ['class' => 'sr-only']) ?>
        <?= Html::activeTextInput($searchModel, 'q', [
            'name' => 'q',
            'id' => 'tt-q',
            'class' => 'form-control',
            'placeholder' => '届次 / 球队 / 主教练',
            'maxlength' => 100,
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeDropDownList($searchModel, 'tournamentId', Tournament::options(), [
            'name' => 'tournamentId',
            'class' => 'form-control',
            'prompt' => '全部届次',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeDropDownList($searchModel, 'teamId', $teams, [
            'name' => 'teamId',
            'class' => 'form-control',
            'prompt' => '全部球队',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeTextInput($searchModel, 'groupName', [
            'name' => 'groupName',
            'class' => 'form-control',
            'placeholder' => '小组',
            'style' => 'width:90px',
            'maxlength' => 10,
        ]) ?>
    </div>
    <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 支参赛队,当前显示 {begin}–{end} 条',
        'emptyText' => '暂无符合条件的参赛队。',
        'columns' => [
            'id',
            [
                'attribute' => 'tournament_year',
                'label' => '届次',
                'value' => function ($model) {
                    return $model->tournament === null ? '' : $model->tournament->name;
                },
            ],
            [
                'attribute' => 'team_name',
                'label' => '球队',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->team === null
                        ? ''
                        : Html::a(Html::encode($model->team->name), ['/team/view', 'id' => $model->team_id]);
                },
            ],
            'group_name',
            'coach',
            'fifa_rank',
            'final_rank',
            [
                'class' => ActionColumn::class,
                'header' => '操作',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url) {
                        return Html::a('查看', $url, ['class' => 'btn btn-xs btn-default']);
                    },
                    'update' => function ($url) {
                        return Html::a('编辑', $url, ['class' => 'btn btn-xs btn-primary']);
                    },
                    'delete' => function ($url) {
                        return Html::a('删除', $url, [
                            'class' => 'btn btn-xs btn-danger',
                            'data' => [
                                'method' => 'post',
                                'confirm' => '确定删除这条参赛队记录吗?',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

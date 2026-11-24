<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台比赛列表视图。
 *
 * @var yii\web\View $this
 * @var common\models\GameSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */

use common\models\Game;
use common\models\Tournament;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '比赛管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('新增比赛', ['create'], ['class' => 'btn btn-success']) ?></p>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin:20px 0']) ?>
    <div class="form-group">
        <?= Html::label('关键词', 'game-q', ['class' => 'sr-only']) ?>
        <?= Html::activeTextInput($searchModel, 'q', [
            'name' => 'q',
            'id' => 'game-q',
            'class' => 'form-control',
            'placeholder' => '球队 / 场地',
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
        <?= Html::activeDropDownList($searchModel, 'stage', Game::stageOptions(), [
            'name' => 'stage',
            'class' => 'form-control',
            'prompt' => '全部阶段',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeDropDownList($searchModel, 'status', Game::statusOptions(), [
            'name' => 'status',
            'class' => 'form-control',
            'prompt' => '全部状态',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeTextInput($searchModel, 'year', [
            'name' => 'year',
            'class' => 'form-control',
            'placeholder' => '年份',
            'style' => 'width:90px',
        ]) ?>
    </div>
    <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 场比赛,当前显示 {begin}–{end} 条',
        'emptyText' => '暂无符合条件的比赛。',
        'columns' => [
            [
                'attribute' => 'tournament_year',
                'label' => '届次',
                'format' => 'raw',
                'value' => function ($model) {
                    $name = $model->tournament === null ? '' : (string) $model->tournament->name;
                    return Html::tag('span', Html::encode(\yii\helpers\StringHelper::truncate($name, 12)), [
                        'title' => $name,
                        'class' => 'dash-ellipsis',
                    ]);
                },
            ],
            [
                'attribute' => 'match_time',
                'label' => '比赛时间',
                // 比赛时间是“墙上时间”，按库里存的字面值显示；交给 Formatter 会被时区平移。
                'value' => function ($model) {
                    return $model->match_time !== null
                        ? substr((string) $model->match_time, 0, 16)
                        : null;
                },
            ],
            'stage',
            [
                'attribute' => 'home_team_name',
                'label' => '对阵',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::encode($model->homeTeamName)
                        . ' <span class="text-muted">vs</span> '
                        . Html::encode($model->awayTeamName);
                },
            ],
            [
                'label' => '比分',
                'value' => function ($model) {
                    return $model->scoreText;
                },
            ],
            [
                'attribute' => 'status',
                'label' => '状态',
                'format' => 'raw',
                'value' => function ($model) {
                    $status = (int) $model->status;
                    $class = $status === Game::STATUS_FINISHED
                        ? 'label-success'
                        : ($status === Game::STATUS_LIVE ? 'label-warning' : 'label-default');
                    return Html::tag('span', Html::encode($model->statusText), ['class' => 'label ' . $class]);
                },
            ],
            [
                'attribute' => 'venue',
                'format' => 'raw',
                'value' => function ($model) {
                    $venue = (string) $model->venue;
                    return Html::tag('span', Html::encode($venue), [
                        'title' => $venue,
                        'class' => 'dash-ellipsis',
                    ]);
                },
            ],
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
                                'confirm' => '删除比赛会同时删除其全部进球记录,确定继续吗?',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

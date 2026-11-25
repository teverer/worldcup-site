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

$tournamentOptions = Tournament::options();
$stageOptions = Game::stageOptions();
$statusOptions = Game::statusOptions();

// 当前生效的筛选,用来在表单下方给出可清除的摘要;
// 只写实际选中的条件,不把“全部”也当成条件。
$activeFilters = [];
foreach ([
    ['label' => '届次', 'value' => $searchModel->tournamentId, 'options' => $tournamentOptions],
    ['label' => '球队', 'value' => $searchModel->teamId, 'options' => $teams],
    ['label' => '阶段', 'value' => $searchModel->stage, 'options' => $stageOptions],
    ['label' => '状态', 'value' => $searchModel->status, 'options' => $statusOptions],
] as $filter) {
    $key = (string) $filter['value'];
    if ($key !== '' && isset($filter['options'][$key])) {
        $activeFilters[] = $filter['label'] . '：' . $filter['options'][$key];
    }
}
$year = trim((string) $searchModel->year);
if ($year !== '') {
    $activeFilters[] = '年份：' . $year;
}
$keyword = trim((string) $searchModel->q);
if ($keyword !== '') {
    $activeFilters[] = '关键词：' . $keyword;
}
?>
<div class="admin-page-head">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?= Html::a('新增比赛', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'id' => 'game-q',
                'class' => 'form-control',
                'placeholder' => '球队或球场，如 阿根廷、卢塞尔',
                'maxlength' => 100,
            ]) ?>
        </label>
        <label class="admin-filter-field admin-filter-field--tournament">
            <span class="admin-filter-label">届次</span>
            <?= Html::activeDropDownList($searchModel, 'tournamentId', $tournamentOptions, [
                'name' => 'tournamentId',
                'class' => 'form-control',
                'prompt' => '全部届次',
            ]) ?>
        </label>
        <label class="admin-filter-field">
            <span class="admin-filter-label">球队</span>
            <?= Html::activeDropDownList($searchModel, 'teamId', $teams, [
                'name' => 'teamId',
                'class' => 'form-control',
                'prompt' => '全部球队',
            ]) ?>
        </label>
        <label class="admin-filter-field">
            <span class="admin-filter-label">阶段</span>
            <?= Html::activeDropDownList($searchModel, 'stage', $stageOptions, [
                'name' => 'stage',
                'class' => 'form-control',
                'prompt' => '全部阶段',
            ]) ?>
        </label>
        <label class="admin-filter-field">
            <span class="admin-filter-label">状态</span>
            <?= Html::activeDropDownList($searchModel, 'status', $statusOptions, [
                'name' => 'status',
                'class' => 'form-control',
                'prompt' => '全部状态',
            ]) ?>
        </label>
        <label class="admin-filter-field admin-filter-field--year">
            <span class="admin-filter-label">年份</span>
            <?= Html::activeTextInput($searchModel, 'year', [
                'name' => 'year',
                'class' => 'form-control',
                'placeholder' => '如 2022',
                'inputmode' => 'numeric',
            ]) ?>
        </label>
        <div class="admin-filter-actions">
            <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
<?= Html::endForm() ?>

<?php if ($activeFilters): ?>
    <div class="admin-filter-active" aria-label="当前筛选">
        <span class="admin-filter-active-label">当前筛选</span>
        <?php foreach ($activeFilters as $filterText): ?>
            <span class="admin-chip"><?= Html::encode($filterText) ?></span>
        <?php endforeach ?>
        <?= Html::a('清除全部', ['index'], ['class' => 'admin-chip-clear']) ?>
    </div>
<?php endif ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 场比赛',
        'emptyText' => '<div class="admin-empty"><strong>没有符合条件的比赛。</strong>'
            . '<p>换一个届次或球队试试，或</p>'
            . Html::a('清除全部筛选', ['index'], ['class' => 'btn btn-default btn-sm'])
            . '</div>',
        'columns' => [
            [
                'attribute' => 'tournament_year',
                'label' => '届次',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->tournament === null) {
                        return '';
                    }
                    return Html::tag('span', Html::encode((string) $model->tournament->year), [
                        'title' => $model->tournament->name,
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

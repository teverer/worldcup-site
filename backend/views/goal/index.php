<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台进球记录列表视图。
 *
 * @var yii\web\View $this
 * @var common\models\GoalSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */

use common\models\Tournament;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '进球管理';
$this->params['breadcrumbs'][] = $this->title;

$tournamentOptions = Tournament::options();

$activeFilters = [];
foreach ([
    ['label' => '届次', 'value' => $searchModel->tournamentId, 'options' => $tournamentOptions],
    ['label' => '球队', 'value' => $searchModel->teamId, 'options' => $teams],
] as $filter) {
    $key = (string) $filter['value'];
    if ($key !== '' && isset($filter['options'][$key])) {
        $activeFilters[] = $filter['label'] . '：' . $filter['options'][$key];
    }
}
$gameId = trim((string) $searchModel->gameId);
if ($gameId !== '') {
    $activeFilters[] = '比赛编号：' . $gameId;
}
$keyword = trim((string) $searchModel->q);
if ($keyword !== '') {
    $activeFilters[] = '关键词：' . $keyword;
}
?>
<div class="admin-page-head">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="admin-page-sub">按球员、球队、届次或比赛编号定位一条进球；乌龙球与点球单独标注。</p>
    </div>
    <?= Html::a('录入进球', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'id' => 'goal-q',
                'class' => 'form-control',
                'placeholder' => '球员或球队，如 梅西、阿根廷',
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
        <label class="admin-filter-field admin-filter-field--year">
            <span class="admin-filter-label">比赛编号</span>
            <?= Html::activeTextInput($searchModel, 'gameId', [
                'name' => 'gameId',
                'class' => 'form-control',
                'placeholder' => '如 1032',
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
        'summary' => '共 {totalCount} 条进球记录,当前显示 {begin}–{end} 条',
        'emptyText' => '暂无符合条件的进球记录。',
        'columns' => [
            'id',
            [
                'attribute' => 'match_time',
                'label' => '比赛时间',
                'format' => ['datetime', 'php:Y-m-d H:i'],
                'value' => function ($model) {
                    return $model->game === null ? null : $model->game->match_time;
                },
            ],
            [
                'attribute' => 'tournament_year',
                'label' => '届次',
                'value' => function ($model) {
                    return $model->game === null || $model->game->tournament === null
                        ? ''
                        : $model->game->tournament->name;
                },
            ],
            [
                'attribute' => 'game_id',
                'label' => '比赛',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->game === null
                        ? ''
                        : Html::a(Html::encode($model->game->matchTitle), ['/game/view', 'id' => $model->game_id]);
                },
            ],
            [
                'attribute' => 'team_name',
                'label' => '归属球队',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->team === null
                        ? ''
                        : Html::a(Html::encode($model->team->name), ['/team/view', 'id' => $model->team_id]);
                },
            ],
            [
                'attribute' => 'player_name',
                'label' => '进球球员',
                'format' => 'raw',
                'value' => function ($model) {
                    $name = $model->player === null ? '未知球员' : $model->player->name;
                    $link = $model->player === null
                        ? Html::encode($name)
                        : Html::a(Html::encode($name), ['/player/view', 'id' => $model->player_id]);
                    return (int) $model->is_own_goal === 1 ? $link . '(乌龙)' : $link;
                },
            ],
            [
                'label' => '助攻',
                'value' => function ($model) {
                    return $model->assistPlayer === null ? '—' : $model->assistPlayer->name;
                },
            ],
            [
                'attribute' => 'minute',
                'label' => '分钟',
                'value' => function ($model) {
                    return $model->minute === null ? '—' : $model->minute . "'";
                },
            ],
            [
                'label' => '标记',
                'format' => 'raw',
                'value' => function ($model) {
                    $tags = [];
                    if ((int) $model->is_penalty === 1) {
                        $tags[] = Html::tag('span', '点球', ['class' => 'label label-info']);
                    }
                    if ((int) $model->is_own_goal === 1) {
                        $tags[] = Html::tag('span', '乌龙', ['class' => 'label label-warning']);
                    }
                    if ((int) $model->is_shootout === 1) {
                        $tags[] = Html::tag('span', '点球大战', ['class' => 'label label-default']);
                    }
                    return $tags === [] ? '—' : implode(' ', $tags);
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
                                'confirm' => '确定删除这条进球记录吗?',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

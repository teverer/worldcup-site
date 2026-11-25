<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台届次列表视图。
 *
 * @var yii\web\View $this
 * @var common\models\TournamentSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */

use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '届次管理';
$this->params['breadcrumbs'][] = $this->title;

$activeFilters = [];
$keyword = trim((string) $searchModel->q);
if ($keyword !== '') {
    $activeFilters[] = '关键词：' . $keyword;
}
$year = trim((string) $searchModel->year);
if ($year !== '') {
    $activeFilters[] = '年份：' . $year;
}
?>
<div class="admin-page-head">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?= Html::a('新增届次', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'id' => 'tournament-q',
                'class' => 'form-control',
                'placeholder' => '届次名称、东道主或赛制',
                'maxlength' => 100,
            ]) ?>
        </label>
        <label class="admin-filter-field admin-filter-field--year">
            <span class="admin-filter-label">年份</span>
            <?= Html::activeTextInput($searchModel, 'year', [
                'name' => 'year',
                'id' => 'tournament-year',
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
        'summary' => '共 {totalCount} 届赛事',
        'emptyText' => '<div class="admin-empty"><strong>没有符合条件的届次。</strong><p>换一个年份，或</p>' . Html::a('清除全部筛选', ['index'], ['class' => 'btn btn-default btn-sm']) . '</div>',
        'columns' => [
            'year',
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function ($model) {
                    $full = (string) $model->name;
                    $short = preg_replace('/^\d{4}年/u', '', $full);
                    return Html::a(
                        Html::encode($short !== '' ? $short : $full),
                        ['view', 'id' => $model->id],
                        ['title' => $full]
                    );
                },
            ],
            'host_country',
            'team_count',
            [
                'label' => '赛期',
                'format' => 'raw',
                'value' => function ($model) {
                    $start = $model->start_date !== null ? substr((string) $model->start_date, 0, 10) : '';
                    $end = $model->end_date !== null ? substr((string) $model->end_date, 0, 10) : '';
                    return Html::encode($start) . ' – ' . Html::encode($end);
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
                                'confirm' => '删除本届次将级联删除其参赛队、名单、比赛和进球记录,且不可恢复。确定继续吗?',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

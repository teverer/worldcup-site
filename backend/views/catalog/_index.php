<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 后台资料目录。
 * @var yii\web\View $this
 * @var yii\base\Model $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 * @var bool $isTeam
 */
use common\components\AdminActionColumn;
use common\models\Team;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = $isTeam ? '球队管理' : '球员管理';
$this->params['breadcrumbs'][] = $this->title;
$columns = [
    [
        'attribute' => 'name',
        'format' => 'raw',
        'value' => static function ($model) {
            return Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
        },
    ],
];
foreach ($isTeam ? ['code', 'continent'] : ['nationality', 'position', 'birth_date'] as $attribute) {
    $columns[] = $attribute;
}
$columns[] = ['class' => AdminActionColumn::class];

// 球员位置是受控词表;后台只做筛选,不在这里新增词典。
$positionOptions = [
    '前锋' => '前锋',
    '中场' => '中场',
    '后卫' => '后卫',
    '门将' => '门将',
    '待补' => '待补',
];

$activeFilters = [];
$keyword = trim((string) $searchModel->q);
if ($keyword !== '') {
    $activeFilters[] = '关键词：' . $keyword;
}
if ($isTeam) {
    $continent = trim((string) $searchModel->continent);
    if ($continent !== '') {
        $activeFilters[] = '大洲：' . $continent;
    }
} else {
    $teamId = (string) $searchModel->teamId;
    if ($teamId !== '' && isset($teams[$teamId])) {
        $activeFilters[] = '球队：' . $teams[$teamId];
    }
    $position = trim((string) $searchModel->position);
    if ($position !== '') {
        $activeFilters[] = '位置：' . $position;
    }
}
?>
<div class="admin-page-head">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?= Html::a($isTeam ? '新增球队' : '新增球员', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'id' => 'admin-q',
                'class' => 'form-control',
                'placeholder' => $isTeam ? '球队名称或代码，如 阿根廷、ARG' : '姓名或国籍，如 梅西、阿根廷',
                'maxlength' => 100,
            ]) ?>
        </label>
        <?php if ($isTeam): ?>
            <label class="admin-filter-field">
                <span class="admin-filter-label">所属大洲</span>
                <?= Html::activeDropDownList(
                    $searchModel,
                    'continent',
                    array_combine(Team::CONTINENTS, Team::CONTINENTS),
                    [
                        'name' => 'continent',
                        'id' => 'admin-continent',
                        'class' => 'form-control',
                        'prompt' => '全部大洲',
                    ]
                ) ?>
            </label>
        <?php else: ?>
            <label class="admin-filter-field">
                <span class="admin-filter-label">代表球队</span>
                <?= Html::activeDropDownList($searchModel, 'teamId', $teams, [
                    'name' => 'teamId',
                    'id' => 'admin-team',
                    'class' => 'form-control',
                    'prompt' => '全部球队',
                ]) ?>
            </label>
            <label class="admin-filter-field">
                <span class="admin-filter-label">位置</span>
                <?= Html::activeDropDownList($searchModel, 'position', $positionOptions, [
                    'name' => 'position',
                    'id' => 'admin-position',
                    'class' => 'form-control',
                    'prompt' => '全部位置',
                ]) ?>
            </label>
        <?php endif ?>
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
        'columns' => $columns,
        'emptyText' => '<div class="admin-empty"><strong>没有符合条件的记录。</strong>'
            . '<p>换一个关键词或筛选条件，或</p>'
            . Html::a('清除全部筛选', ['index'], ['class' => 'btn btn-default btn-sm'])
            . '</div>',
        'summary' => '共 {totalCount} 条记录',
    ]) ?>
</div>

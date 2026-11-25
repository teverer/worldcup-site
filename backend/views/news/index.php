<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261112
 * 模块:留言与资讯 —— 后台资讯管理列表。
 *
 * @var yii\web\View $this
 * @var common\models\NewsSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use common\components\AdminActionColumn;
use common\models\News;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '资讯管理';
$this->params['breadcrumbs'][] = $this->title;

$statusOptions = News::statusOptions();
$activeFilters = [];
$keyword = trim((string) $searchModel->q);
if ($keyword !== '') {
    $activeFilters[] = '关键词：' . $keyword;
}
$status = (string) $searchModel->status;
if ($status !== '' && isset($statusOptions[$status])) {
    $activeFilters[] = '状态：' . $statusOptions[$status];
}
?>
<div class="admin-page-head">
    <div>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?= Html::a('新增资讯', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'class' => 'form-control',
                'placeholder' => '标题或作者',
                'maxlength' => 100,
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
        'summary' => '共 {totalCount} 条资讯',
        'emptyText' => '<div class="admin-empty"><strong>没有符合条件的资讯。</strong><p>换一个关键词或状态，或</p>' . Html::a('清除全部筛选', ['index'], ['class' => 'btn btn-default btn-sm']) . '</div>',
        'columns' => [
            [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->title), ['view', 'id' => $model->id]);
                },
            ],
            'author',
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    $class = $model->isPublished() ? 'label-success' : 'label-default';
                    return Html::tag('span', Html::encode(News::statusOptions()[$model->status]), [
                        'class' => 'label ' . $class,
                    ]);
                },
            ],
            'publish_time:datetime',
            ['class' => AdminActionColumn::class],
        ],
    ]) ?>
</div>

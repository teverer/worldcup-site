<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261117
 * 模块:留言与资讯 —— 后台留言筛选与删除列表。
 *
 * @var yii\web\View $this
 * @var backend\models\CommentSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use backend\models\CommentSearch;
use common\components\CommentTarget;
use common\models\Comment;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\StringHelper;

$this->title = '留言管理';
$this->params['breadcrumbs'][] = $this->title;

$targetLabels = CommentTarget::labels();
// 资讯留言已下线，筛选只列当前支持的留言目标；历史资讯留言仍按 label() 显示。
$targetOptions = array_intersect_key($targetLabels, array_flip(CommentTarget::types()));
$statusOptions = CommentSearch::statusOptions();
$activeFilters = [];
$keyword = trim((string) $searchModel->q);
if ($keyword !== '') {
    $activeFilters[] = '关键词：' . $keyword;
}
$targetType = (string) $searchModel->targetType;
if ($targetType !== '' && isset($targetLabels[$targetType])) {
    $activeFilters[] = '目标：' . $targetLabels[$targetType];
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
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'class' => 'form-control',
                'maxlength' => 100,
                'placeholder' => '留言内容、用户名或昵称',
            ]) ?>
        </label>
        <label class="admin-filter-field">
            <span class="admin-filter-label">目标</span>
            <?= Html::activeDropDownList($searchModel, 'targetType', $targetOptions, [
                'name' => 'targetType',
                'class' => 'form-control',
                'prompt' => '全部目标',
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
        'summary' => '共 {totalCount} 条留言记录',
        'emptyText' => '<div class="admin-empty"><strong>没有符合条件的留言。</strong><p>换一个关键词、目标或状态，或</p>' . Html::a('清除全部筛选', ['index'], ['class' => 'btn btn-default btn-sm']) . '</div>',
        'rowOptions' => function ($model) {
            return $model->status === Comment::STATUS_DELETED ? ['class' => 'warning'] : [];
        },
        'columns' => [
            [
                'label' => '用户',
                'value' => function ($model) {
                    if ($model->user === null) {
                        return '未知用户';
                    }
                    return $model->user->nickname ?: $model->user->username;
                },
            ],
            [
                'label' => '目标',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::tag(
                        'span',
                        Html::encode(CommentTarget::label($model->target_type))
                            . ' #' . (int) $model->target_id,
                        ['class' => 'label label-info']
                    );
                },
            ],
            [
                'attribute' => 'content',
                'format' => 'raw',
                'value' => function ($model) {
                    $text = StringHelper::truncate($model->content, 100, '…', null, true);
                    return Html::tag('span', Html::encode($text), ['title' => $model->content]);
                },
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    $options = CommentSearch::statusOptions();
                    $class = $model->status === Comment::STATUS_ACTIVE
                        ? 'label-success'
                        : 'label-default';
                    return Html::tag('span', Html::encode($options[$model->status]), [
                        'class' => 'label ' . $class,
                    ]);
                },
            ],
            'create_time:datetime',
            [
                'class' => ActionColumn::class,
                'header' => '操作',
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model) {
                        if ($model->status === Comment::STATUS_DELETED) {
                            return Html::tag('span', '已删除', ['class' => 'text-muted']);
                        }
                        return Html::a('删除', $url, [
                            'class' => 'btn btn-danger btn-xs',
                            'data' => [
                                'confirm' => '这条留言及其全部回复会一并删除，无法恢复。',
                                'confirm-title' => '删除这条留言？',
                                'confirm-ok' => '删除',
                                'confirm-tone' => 'danger',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

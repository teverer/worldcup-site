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
?>
<h1><?= Html::encode($this->title) ?></h1>

<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline']) ?>
    <div class="form-group">
        <?= Html::activeTextInput($searchModel, 'q', [
            'name' => 'q',
            'class' => 'form-control',
            'maxlength' => 100,
            'placeholder' => '留言内容、用户名或昵称',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeDropDownList(
            $searchModel,
            'targetType',
            CommentTarget::labels(),
            ['name' => 'targetType', 'class' => 'form-control', 'prompt' => '全部目标']
        ) ?>
    </div>
    <div class="form-group">
        <?= Html::activeDropDownList(
            $searchModel,
            'status',
            CommentSearch::statusOptions(),
            ['name' => 'status', 'class' => 'form-control', 'prompt' => '全部状态']
        ) ?>
    </div>
    <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 条留言记录',
        'emptyText' => '暂无符合条件的信息。',
        'rowOptions' => function ($model) {
            return $model->status === Comment::STATUS_DELETED ? ['class' => 'warning'] : [];
        },
        'columns' => [
            'id',
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
                                'confirm' => '确定删除这条留言及其全部回复吗？',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

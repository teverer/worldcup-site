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

use common\models\News;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '资讯管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('新增资讯', ['create'], ['class' => 'btn btn-success']) ?></p>

<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline']) ?>
    <div class="form-group">
        <?= Html::activeTextInput($searchModel, 'q', [
            'name' => 'q',
            'class' => 'form-control',
            'placeholder' => '标题或作者',
            'maxlength' => 100,
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::activeDropDownList(
            $searchModel,
            'status',
            News::statusOptions(),
            ['name' => 'status', 'class' => 'form-control', 'prompt' => '全部状态']
        ) ?>
    </div>
    <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 条资讯',
        'emptyText' => '暂无符合条件的信息。',
        'columns' => [
            'id',
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
            'create_time:datetime',
            [
                'class' => ActionColumn::class,
                'header' => '操作',
            ],
        ],
    ]) ?>
</div>

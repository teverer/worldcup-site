<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台作业文件列表。
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $q
 * @var string $type
 */

use common\components\AdminActionColumn;
use common\models\Work;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '作业管理';
$this->params['breadcrumbs'][] = '团队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('上传作业文件', ['create'], ['class' => 'btn btn-success']) ?></p>

<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin-bottom:20px']) ?>
    <div class="form-group">
        <?= Html::textInput('q', $q, [
            'class' => 'form-control',
            'placeholder' => '作业名称或说明',
            'maxlength' => 100,
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::dropDownList('type', $type, Work::typeOptions(), [
            'class' => 'form-control',
            'prompt' => '全部类型',
        ]) ?>
    </div>
    <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 个作业文件',
        'emptyText' => '暂无符合条件的作业文件。',
        'columns' => [
            'id',
            [
                'attribute' => 'title',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->title), ['view', 'id' => $model->id]);
                },
            ],
            'type',
            'description',
            'create_time:datetime',
            ['class' => AdminActionColumn::class],
        ],
    ]) ?>
</div>

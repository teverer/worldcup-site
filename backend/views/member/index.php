<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台成员列表。
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $q
 */

use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '成员管理';
$this->params['breadcrumbs'][] = '团队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('新增成员', ['create'], ['class' => 'btn btn-success']) ?></p>

<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin-bottom:20px']) ?>
    <div class="form-group">
        <?= Html::textInput('q', $q, [
            'class' => 'form-control',
            'placeholder' => '姓名、学号或分工',
            'maxlength' => 100,
        ]) ?>
    </div>
    <?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 位成员',
        'emptyText' => '暂无符合条件的成员。',
        'columns' => [
            'id',
            'name',
            'student_id',
            'role',
            'duty',
            'create_time:datetime',
            ['class' => ActionColumn::class, 'header' => '操作'],
        ],
    ]) ?>
</div>

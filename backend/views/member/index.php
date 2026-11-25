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

use common\components\AdminActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '成员管理';
$this->params['breadcrumbs'][] = '团队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-page-head">
    <div><h1><?= Html::encode($this->title) ?></h1></div>
    <?= Html::a('新增成员', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::textInput('q', $q, [
                'class' => 'form-control',
                'placeholder' => '姓名、学号或分工',
                'maxlength' => 100,
            ]) ?>
        </label>
        <div class="admin-filter-actions">
            <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 位成员',
        'emptyText' => '暂无符合条件的成员。',
        'columns' => [
            'name',
            'student_id',
            'role',
            'duty',
            ['class' => AdminActionColumn::class],
        ],
    ]) ?>
</div>

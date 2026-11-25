<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 后台资料详情。
 * @var yii\web\View $this
 * @var yii\db\ActiveRecord $model
 */
use common\models\Team;
use yii\helpers\Html;
use yii\widgets\DetailView;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => $model instanceof Team ? '球队管理' : '球员管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$attributes = $model instanceof Team
    ? ['name', 'code', 'continent', 'flag', 'logo']
    : ['name', 'name_zh', 'birth_date', 'nationality', 'position', 'photo'];
$attributes[] = 'intro:ntext';
?>
<div class="admin-page-head">
    <div><h1><?= Html::encode($this->title) ?></h1></div>
    <div class="admin-page-actions">
        <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
        <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('删除', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'method' => 'post',
                'confirm' => '确定删除这条记录吗？有关联历史数据的记录不会被删除。',
            ],
        ]) ?>
    </div>
</div>
<?= DetailView::widget(['model' => $model, 'attributes' => $attributes]) ?>

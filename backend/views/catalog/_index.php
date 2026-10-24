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
use yii\grid\GridView;
use yii\helpers\Html;
$this->title = $isTeam ? '球队管理' : '球员管理';
$this->params['breadcrumbs'][] = $this->title;
$columns = array_merge(['id', 'name'], $isTeam ? ['code', 'continent'] : ['nationality', 'position', 'birth_date']);
$columns[] = ['class' => 'yii\grid\ActionColumn', 'header' => '操作'];
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a($isTeam ? '新增球队' : '新增球员', ['create'], ['class' => 'btn btn-success']) ?></p>
<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin:20px 0']) ?>
<?= Html::label($searchModel->getAttributeLabel('q'), 'admin-q', ['class' => 'sr-only']) ?>
<?= Html::activeTextInput($searchModel, 'q', ['name' => 'q', 'id' => 'admin-q', 'class' => 'form-control', 'placeholder' => $searchModel->getAttributeLabel('q')]) ?>
<?php if ($isTeam): ?>
<?= Html::label('所属大洲', 'admin-continent', ['class' => 'sr-only']) ?>
<?= Html::activeDropDownList($searchModel, 'continent', array_combine(common\models\Team::CONTINENTS, common\models\Team::CONTINENTS),
    ['name' => 'continent', 'id' => 'admin-continent', 'class' => 'form-control', 'prompt' => '全部大洲']) ?>
<?php else: ?>
<?= Html::label('代表球队', 'admin-team', ['class' => 'sr-only']) ?>
<?= Html::activeDropDownList($searchModel, 'teamId', $teams, ['name' => 'teamId', 'id' => 'admin-team', 'class' => 'form-control', 'prompt' => '全部球队']) ?>
<?php endif ?>
<?= Html::submitButton('搜索', ['class' => 'btn btn-primary']) ?>
<?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>
<div class="table-responsive">
<?= GridView::widget(['dataProvider' => $dataProvider, 'columns' => $columns, 'emptyText' => '暂无符合条件的记录。',
    'summary' => '共 {totalCount} 条记录，当前显示 {begin}–{end} 条']) ?>
</div>

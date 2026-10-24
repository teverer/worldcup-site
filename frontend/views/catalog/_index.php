<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 公开目录。
 * @var yii\web\View $this
 * @var yii\base\Model $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 * @var bool $isTeam
 */
use yii\grid\GridView;
use yii\helpers\Html;
$this->title = $isTeam ? '球队目录' : '球员目录';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$columns = [
    ['attribute' => 'name', 'format' => 'raw', 'value' => function ($model) {
        return Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
    }],
];
$columns = array_merge($columns, $isTeam ? ['code', 'continent'] : ['nationality', 'position', 'birth_date']);
?>
<section class="catalog-page">
    <header class="catalog-heading">
        <p class="catalog-kicker">世界杯数据档案</p>
        <h1><?= Html::encode($this->title) ?></h1>
        <p><?= $isTeam ? '浏览国家队资料，按届次查看参赛球员。' : '查找球员资料，了解历届参赛经历、进球与助攻。' ?></p>
    </header>
    <?= $this->render('_filters', ['searchModel' => $searchModel, 'teams' => $teams]) ?>
    <div class="table-responsive">
    <?= GridView::widget(['dataProvider' => $dataProvider, 'columns' => $columns,
        'summary' => '共 {totalCount} 条记录，当前显示 {begin}–{end} 条',
        'emptyText' => '暂无符合条件的数据，请调整筛选或稍后再来。',
        'tableOptions' => ['class' => 'table table-hover catalog-table']]) ?>
    </div>
</section>

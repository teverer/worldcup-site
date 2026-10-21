<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球队详情和跨届球员名单。
 * @var yii\web\View $this
 * @var common\models\Team $model
 * @var array $tournaments
 * @var string|null $tournamentId
 * @var yii\data\ActiveDataProvider $dataProvider
 */
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '球队目录', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
?>
<article class="catalog-page">
    <header class="catalog-heading">
        <?php if ($model->flag): ?><?= Html::img('@web/' . $model->flag, ['alt' => $model->name . '国旗', 'class' => 'catalog-portrait']) ?><?php endif ?>
        <h1><?= Html::encode($model->name) ?></h1>
        <p><?= Html::encode(implode(' · ', array_filter([$model->code, $model->continent]))) ?></p>
    </header>
    <h2>球队介绍</h2>
    <p class="catalog-intro"><?= $model->intro ? nl2br(Html::encode($model->intro)) : '暂无球队介绍。' ?></p>
    <h2>参赛球员</h2>
    <?= Html::beginForm(['view'], 'get', ['class' => 'catalog-filters']) ?>
    <?= Html::hiddenInput('id', $model->id) ?>
    <div class="form-group">
        <?= Html::label('世界杯届次', 'roster-tournament') ?>
        <?= Html::dropDownList('tournamentId', $tournamentId, ArrayHelper::map($tournaments, 'id', 'name'),
            ['id' => 'roster-tournament', 'class' => 'form-control', 'prompt' => '所有届次（球员不重复）']) ?>
    </div>
    <div class="form-group catalog-filter-actions"><?= Html::submitButton('查看名单', ['class' => 'btn btn-success']) ?></div>
    <?= Html::endForm() ?>
    <div class="table-responsive">
    <?= GridView::widget(['dataProvider' => $dataProvider, 'emptyText' => '尚未录入参赛名单。',
        'summary' => '共 {totalCount} 位球员', 'columns' => [
            ['attribute' => 'name', 'format' => 'raw', 'value' => function ($player) {
                return Html::a(Html::encode($player->name), ['/player/view', 'id' => $player->id]);
            }], 'nationality', 'position',
        ], 'tableOptions' => ['class' => 'table table-hover catalog-table']]) ?>
    </div>
</article>

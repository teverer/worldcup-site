<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261108
 * 模块:球队与球员、留言与资讯 —— 球队详情、跨届球员名单和留言区。
 * @var yii\web\View $this
 * @var common\models\Team $model
 * @var array $tournaments
 * @var string|null $tournamentId
 * @var yii\data\ActiveDataProvider $dataProvider
 */
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use common\components\CommentTarget;
use frontend\widgets\CommentSection;
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
    <?php if ($model->intro): ?>
        <h2 class="section-title">球队介绍</h2>
        <p class="catalog-intro"><?= nl2br(Html::encode($model->intro)) ?></p>
    <?php endif ?>
    <h2 class="section-title">参赛球员</h2>
    <?= Html::beginForm(['view'], 'get', ['class' => 'catalog-filters']) ?>
    <?= Html::hiddenInput('id', $model->id) ?>
    <div class="form-group">
        <?= Html::label('世界杯届次', 'roster-tournament') ?>
        <?= Html::dropDownList('tournamentId', $tournamentId, ArrayHelper::map($tournaments, 'id', 'name'),
            ['id' => 'roster-tournament', 'class' => 'form-control', 'prompt' => '所有届次（球员不重复）']) ?>
    </div>
    <div class="form-group catalog-filter-actions"><?= Html::submitButton('查看名单', ['class' => 'btn btn-primary']) ?></div>
    <?= Html::endForm() ?>
    <div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'caption' => '该队世界杯参赛球员名单',
        'captionOptions' => ['class' => 'sr-only'],
        'emptyText' => '<div class="empty-state"><h3>暂无参赛名单</h3><p>该队的世界杯参赛球员尚未录入,稍后再来。</p></div>',
        'summary' => '共 {totalCount} 位球员',
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'headerOptions' => ['scope' => 'col'],
                'value' => function ($player) {
                    return Html::a(Html::encode($player->name), ['/player/view', 'id' => $player->id]);
                },
            ],
            ['attribute' => 'position', 'headerOptions' => ['scope' => 'col']],
        ],
        'tableOptions' => ['class' => 'table table-hover catalog-table'],
        'pager' => ['prevPageLabel' => '上一页', 'nextPageLabel' => '下一页'],
    ]) ?>
    </div>
</article>

<?= CommentSection::widget([
    'targetType' => CommentTarget::TEAM,
    'targetId' => $model->id,
]) ?>

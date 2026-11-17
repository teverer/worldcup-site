<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台届次列表视图。
 *
 * @var yii\web\View $this
 * @var common\models\TournamentSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */

use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '届次管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p><?= Html::a('新增届次', ['create'], ['class' => 'btn btn-success']) ?></p>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'form-inline', 'style' => 'margin:20px 0']) ?>
    <div class="form-group">
        <?= Html::label('关键词', 'tournament-q', ['class' => 'sr-only']) ?>
        <?= Html::activeTextInput($searchModel, 'q', [
            'name' => 'q',
            'id' => 'tournament-q',
            'class' => 'form-control',
            'placeholder' => '届次名称 / 东道主 / 赛制',
            'maxlength' => 100,
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::label('年份', 'tournament-year', ['class' => 'sr-only']) ?>
        <?= Html::activeTextInput($searchModel, 'year', [
            'name' => 'year',
            'id' => 'tournament-year',
            'class' => 'form-control',
            'placeholder' => '年份',
            'style' => 'width:100px',
        ]) ?>
    </div>
    <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => '共 {totalCount} 届赛事,当前显示 {begin}–{end} 条',
        'emptyText' => '暂无符合条件的届次。',
        'columns' => [
            'id',
            'year',
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->name), ['view', 'id' => $model->id]);
                },
            ],
            'host_country',
            'team_count',
            'format',
            'win_points',
            'start_date:date',
            'end_date:date',
            [
                'class' => ActionColumn::class,
                'header' => '操作',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url) {
                        return Html::a('查看', $url, ['class' => 'btn btn-xs btn-default']);
                    },
                    'update' => function ($url) {
                        return Html::a('编辑', $url, ['class' => 'btn btn-xs btn-primary']);
                    },
                    'delete' => function ($url) {
                        return Html::a('删除', $url, [
                            'class' => 'btn btn-xs btn-danger',
                            'data' => [
                                'method' => 'post',
                                'confirm' => '删除本届次将级联删除其参赛队、名单、比赛和进球记录,且不可恢复。确定继续吗?',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]) ?>
</div>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛队列表视图。
 *
 * @var yii\web\View $this
 * @var common\models\TournamentTeamSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */

use common\components\AdminActionColumn;
use common\models\Tournament;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '参赛队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-page-head">
    <div><h1><?= Html::encode($this->title) ?></h1></div>
    <?= Html::a('新增参赛队', ['create'], ['class' => 'btn btn-primary admin-page-action']) ?>
</div>

<?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
<?= Html::beginForm(['index'], 'get', ['class' => 'admin-filter']) ?>
    <div class="admin-filter-grid">
        <label class="admin-filter-field admin-filter-field--wide">
            <span class="admin-filter-label">关键词</span>
            <?= Html::activeTextInput($searchModel, 'q', [
                'name' => 'q',
                'id' => 'tt-q',
                'class' => 'form-control',
                'placeholder' => '球队 / 主教练',
                'maxlength' => 100,
            ]) ?>
        </label>
        <label class="admin-filter-field admin-filter-field--tournament">
            <span class="admin-filter-label">届次</span>
            <?= Html::activeDropDownList($searchModel, 'tournamentId', Tournament::options(), [
                'name' => 'tournamentId',
                'class' => 'form-control',
                'prompt' => '全部届次',
            ]) ?>
        </label>
        <label class="admin-filter-field">
            <span class="admin-filter-label">球队</span>
            <?= Html::activeDropDownList($searchModel, 'teamId', $teams, [
                'name' => 'teamId',
                'class' => 'form-control',
                'prompt' => '全部球队',
            ]) ?>
        </label>
        <label class="admin-filter-field admin-filter-field--year">
            <span class="admin-filter-label">小组</span>
            <?= Html::activeTextInput($searchModel, 'groupName', [
                'name' => 'groupName',
                'class' => 'form-control',
                'placeholder' => '如 A',
                'maxlength' => 10,
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
        'summary' => '共 {totalCount} 支参赛队',
        'emptyText' => '暂无符合条件的参赛队。',
        'columns' => [
            [
                'attribute' => 'tournament_year',
                'label' => '届次',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->tournament === null) {
                        return '';
                    }
                    return Html::tag('span', Html::encode((string) $model->tournament->year), [
                        'title' => $model->tournament->name,
                    ]);
                },
            ],
            [
                'attribute' => 'team_name',
                'label' => '球队',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->team === null
                        ? ''
                        : Html::a(Html::encode($model->team->name), ['/team/view', 'id' => $model->team_id]);
                },
            ],
            'group_name',
            ['class' => AdminActionColumn::class],
        ],
    ]) ?>
</div>

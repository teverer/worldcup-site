<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台比赛详情视图(含进球记录与录入入口)。
 *
 * @var yii\web\View $this
 * @var common\models\Game $model
 * @var common\models\Goal[] $goals
 */

use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->matchTitle;
$this->params['breadcrumbs'][] = ['label' => '比赛管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('录入进球', ['/goal/create', 'game_id' => $model->id], ['class' => 'btn btn-success']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'method' => 'post',
            'confirm' => '删除比赛会同时删除其全部进球记录,确定继续吗?',
        ],
    ]) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        [
            'attribute' => 'tournament_id',
            'label' => '届次',
            'value' => $model->tournament === null ? '' : $model->tournament->name,
        ],
        'stage',
        [
            'attribute' => 'status',
            'value' => $model->statusText,
        ],
        [
            'attribute' => 'match_time',
            'value' => function ($model) {
                return $model->match_time !== null
                    ? substr((string) $model->match_time, 0, 16)
                    : null;
            },
        ],
        'venue',
        [
            'attribute' => 'home_team_id',
            'label' => '主队',
            'format' => 'raw',
            'value' => $model->homeTeam === null
                ? ''
                : Html::a(Html::encode($model->homeTeam->name), ['/team/view', 'id' => $model->home_team_id]),
        ],
        [
            'attribute' => 'away_team_id',
            'label' => '客队',
            'format' => 'raw',
            'value' => $model->awayTeam === null
                ? ''
                : Html::a(Html::encode($model->awayTeam->name), ['/team/view', 'id' => $model->away_team_id]),
        ],
        [
            'attribute' => 'home_score',
            'value' => $model->home_score === null ? '—' : $model->home_score,
        ],
        [
            'attribute' => 'away_score',
            'value' => $model->away_score === null ? '—' : $model->away_score,
        ],
        [
            'attribute' => 'home_penalty_score',
            'value' => $model->home_penalty_score === null ? '—' : $model->home_penalty_score,
        ],
        [
            'attribute' => 'away_penalty_score',
            'value' => $model->away_penalty_score === null ? '—' : $model->away_penalty_score,
        ],
        [
            'label' => '比分汇总',
            'value' => $model->scoreText,
        ],
        'create_time:datetime',
    ],
]) ?>

<h2>进球记录</h2>
<?php if (empty($goals)): ?>
    <p class="text-muted">本场比赛暂无进球记录。</p>
<?php else: ?>
    <div class="table-responsive">
        <?= GridView::widget([
            'dataProvider' => new ArrayDataProvider([
                'allModels' => $goals,
                'pagination' => false,
                'sort' => false,
            ]),
            'summary' => '',
            'columns' => [
                [
                    'label' => '分钟',
                    'value' => function ($goal) {
                        return $goal->minute === null ? '—' : $goal->minute . "'";
                    },
                ],
                [
                    'label' => '归属球队',
                    'format' => 'raw',
                    'value' => function ($goal) {
                        return $goal->team === null
                            ? ''
                            : Html::a(Html::encode($goal->team->name), ['/team/view', 'id' => $goal->team_id]);
                    },
                ],
                [
                    'label' => '进球球员',
                    'format' => 'raw',
                    'value' => function ($goal) {
                        $name = $goal->player === null ? '未知球员' : $goal->player->name;
                        $link = $goal->player === null
                            ? Html::encode($name)
                            : Html::a(Html::encode($name), ['/player/view', 'id' => $goal->player_id]);
                        return (int) $goal->is_own_goal === 1 ? $link . '(乌龙)' : $link;
                    },
                ],
                [
                    'label' => '助攻',
                    'value' => function ($goal) {
                        return $goal->assistPlayer === null ? '—' : $goal->assistPlayer->name;
                    },
                ],
                [
                    'label' => '标记',
                    'format' => 'raw',
                    'value' => function ($goal) {
                        $tags = [];
                        if ((int) $goal->is_penalty === 1) {
                            $tags[] = Html::tag('span', '点球', ['class' => 'label label-info']);
                        }
                        if ((int) $goal->is_own_goal === 1) {
                            $tags[] = Html::tag('span', '乌龙', ['class' => 'label label-warning']);
                        }
                        if ((int) $goal->is_shootout === 1) {
                            $tags[] = Html::tag('span', '点球大战', ['class' => 'label label-default']);
                        }
                        return $tags === [] ? '—' : implode(' ', $tags);
                    },
                ],
                [
                    'label' => '操作',
                    'format' => 'raw',
                    'value' => function ($goal) {
                        return Html::a('编辑', ['/goal/update', 'id' => $goal->id],
                                ['class' => 'btn btn-xs btn-primary'])
                            . ' '
                            . Html::a('删除', ['/goal/delete', 'id' => $goal->id], [
                                'class' => 'btn btn-xs btn-danger',
                                'data' => [
                                    'method' => 'post',
                                    'confirm' => '确定删除这条进球记录吗?',
                                ],
                            ]);
                    },
                ],
            ],
        ]) ?>
    </div>
<?php endif ?>

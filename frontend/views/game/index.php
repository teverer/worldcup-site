<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 比赛列表、届次/阶段/小组筛选与分页。
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var int|null $tournamentId
 * @var string|null $stage
 * @var string|null $group
 * @var array $tournamentOptions
 * @var array $stageOptions
 * @var array $groupOptions
 * @var array $statusLabels
 */
use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = '比赛目录';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$this->registerCssFile('@web/css/game.css');

$games = $dataProvider->getModels();
$statusClasses = [
    0 => 'label label-default',
    1 => 'label label-warning',
    2 => 'label label-success',
];
?>
<section class="catalog-page game-page">
    <header class="catalog-heading">
        <p class="catalog-kicker">世界杯数据档案</p>
        <h1>比赛目录</h1>
        <p>按届次、阶段和小组筛选比赛，查看比分与进球详情。</p>
    </header>

    <?= Html::beginForm(['index'], 'get', ['class' => 'catalog-filters game-filters']) ?>
    <div class="form-group">
        <?= Html::label('届次', 'game-tournament') ?>
        <?= Html::dropDownList('tournamentId', $tournamentId, $tournamentOptions, [
            'id' => 'game-tournament',
            'class' => 'form-control',
            'prompt' => '全部届次',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::label('阶段', 'game-stage') ?>
        <?= Html::dropDownList('stage', $stage, $stageOptions, [
            'id' => 'game-stage',
            'class' => 'form-control',
            'prompt' => '全部阶段',
        ]) ?>
    </div>
    <div class="form-group">
        <?= Html::label('小组', 'game-group') ?>
        <?= Html::dropDownList('group', $group, $groupOptions, [
            'id' => 'game-group',
            'class' => 'form-control',
            'prompt' => '全部小组',
        ]) ?>
    </div>
    <div class="form-group catalog-filter-actions">
        <?= Html::submitButton('筛选', ['class' => 'btn btn-success']) ?>
        <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
    <?= Html::endForm() ?>

    <p class="game-summary">共 <?= (int) $dataProvider->totalCount ?> 场比赛</p>

    <div class="table-responsive">
        <table class="table table-hover catalog-table game-table">
            <thead>
            <tr>
                <th>届次</th>
                <th>阶段</th>
                <th>对阵</th>
                <th>比分</th>
                <th>比赛时间</th>
                <th>场地</th>
                <th>状态</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$games): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">暂无符合条件的比赛，请调整筛选条件。</td>
                </tr>
            <?php endif ?>
            <?php foreach ($games as $game): ?>
                <?php
                $homeName = $game->homeTeam !== null ? $game->homeTeam->name : '待定';
                $awayName = $game->awayTeam !== null ? $game->awayTeam->name : '待定';
                $tournamentName = $game->tournament !== null ? $game->tournament->name : '待定';
                $scoreText = '—';
                if ($game->home_score !== null && $game->away_score !== null) {
                    $scoreText = $game->home_score . '-' . $game->away_score;
                    if ($game->home_penalty_score !== null && $game->away_penalty_score !== null) {
                        $scoreText .= '(' . $game->home_penalty_score . '-'
                            . $game->away_penalty_score . ')';
                    }
                }
                $statusValue = (int) $game->status;
                $statusText = isset($statusLabels[$statusValue]) ? $statusLabels[$statusValue] : '未知';
                $statusClass = isset($statusClasses[$statusValue])
                    ? $statusClasses[$statusValue] : 'label label-default';
                $matchTime = $game->match_time !== null && $game->match_time !== ''
                    ? $game->match_time : '待定';
                ?>
                <tr>
                    <td>
                        <?php if ($game->tournament !== null): ?>
                            <?= Html::a(Html::encode($tournamentName), [
                                '/tournament/view',
                                'id' => $game->tournament_id,
                            ]) ?>
                        <?php else: ?>
                            待定
                        <?php endif ?>
                    </td>
                    <td><?= Html::encode($game->stage) ?></td>
                    <td>
                        <?= Html::a(Html::encode($homeName), [
                            '/team/view',
                            'id' => $game->home_team_id,
                        ]) ?>
                        <span class="text-muted">vs</span>
                        <?= Html::a(Html::encode($awayName), [
                            '/team/view',
                            'id' => $game->away_team_id,
                        ]) ?>
                    </td>
                    <td><?= Html::a(Html::encode($scoreText), ['view', 'id' => $game->id],
                        ['class' => 'game-score']) ?></td>
                    <td><?= Html::encode($matchTime) ?></td>
                    <td><?= $game->venue !== null && $game->venue !== ''
                        ? Html::encode($game->venue) : '—' ?></td>
                    <td><span class="<?= Html::encode($statusClass) ?>"><?= Html::encode($statusText) ?></span></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <div class="game-pagination">
        <?= LinkPager::widget([
            'pagination' => $dataProvider->pagination,
            'options' => ['class' => 'pagination'],
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
        ]) ?>
    </div>
</section>

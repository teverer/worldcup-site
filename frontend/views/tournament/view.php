<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 单届详情、分组参赛队、分阶段赛程与动态图表。
 *
 * @var yii\web\View $this
 * @var common\models\Tournament $model
 * @var array $groups
 * @var common\models\Team[] $teams
 * @var array $gamesByStage
 * @var array $statusLabels
 */
use yii\helpers\Html;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '世界杯届次', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$this->registerCssFile('@web/css/game.css');

$dateParts = array_filter([$model->start_date, $model->end_date], static function ($date) {
    return $date !== null && $date !== '';
});
$dateRange = $dateParts ? implode(' 至 ', $dateParts) : '待定';
?>
<article class="tournament-page">
    <header class="catalog-heading">
        <p class="catalog-kicker">世界杯数据档案 · <?= Html::encode($model->year) ?></p>
        <h1><?= Html::encode($model->name) ?></h1>
        <p><?= Html::encode($model->host_country !== null && $model->host_country !== ''
            ? $model->host_country : '东道主待定') ?></p>
    </header>

    <h2>基本信息</h2>
    <dl class="tournament-detail-list">
        <dt>东道主</dt>
        <dd><?= $model->host_country !== null && $model->host_country !== ''
            ? Html::encode($model->host_country) : '待定' ?></dd>
        <dt>参赛队数</dt>
        <dd><?= $model->team_count !== null ? Html::encode($model->team_count) . ' 支' : '待定' ?></dd>
        <dt>赛制</dt>
        <dd><?= $model->format !== null && $model->format !== ''
            ? Html::encode($model->format) : '待定' ?></dd>
        <dt>胜场积分</dt>
        <dd><?= $model->win_points !== null ? (int) $model->win_points . ' 分' : '待定' ?></dd>
        <dt>时间范围</dt>
        <dd><?= Html::encode($dateRange) ?></dd>
    </dl>

    <h2>分组与参赛队</h2>
    <?php if (!$groups): ?>
        <p class="text-muted">尚未录入该届参赛队。</p>
    <?php else: ?>
        <?php foreach ($groups as $groupName => $members): ?>
            <section class="tournament-group">
                <h3><?= Html::encode($groupName === '未分组' ? '未分组' : $groupName . ' 组') ?></h3>
                <div class="table-responsive">
                    <table class="table table-hover catalog-table">
                        <thead>
                        <tr>
                            <th>球队</th>
                            <th>教练</th>
                            <th>FIFA 排名</th>
                            <th>最终名次</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($members as $member): ?>
                            <?php
                            $teamId = (int) $member->team_id;
                            $team = isset($teams[$teamId]) ? $teams[$teamId] : null;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($team !== null): ?>
                                        <?= Html::a(Html::encode($team->name), [
                                            '/team/view',
                                            'id' => $team->id,
                                            'tournamentId' => $model->id,
                                        ]) ?>
                                    <?php else: ?>
                                        未知球队
                                    <?php endif ?>
                                </td>
                                <td><?= $member->coach !== null && $member->coach !== ''
                                    ? Html::encode($member->coach) : '—' ?></td>
                                <td><?= $member->fifa_rank !== null ? Html::encode($member->fifa_rank) : '—' ?></td>
                                <td><?= $member->final_rank !== null ? Html::encode($member->final_rank) : '—' ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach ?>
    <?php endif ?>

    <h2>赛程与赛果</h2>
    <?php if (!$gamesByStage): ?>
        <p class="text-muted">尚未录入该届比赛。</p>
    <?php else: ?>
        <?php foreach ($gamesByStage as $stage => $stageGames): ?>
            <section class="tournament-group">
                <h3><?= Html::encode($stage) ?></h3>
                <div class="table-responsive">
                    <table class="table table-hover catalog-table game-table">
                        <thead>
                        <tr>
                            <th>比赛时间</th>
                            <th>对阵</th>
                            <th>比分</th>
                            <th>场地</th>
                            <th>状态</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stageGames as $game): ?>
                            <?php
                            $homeName = $game->homeTeam !== null ? $game->homeTeam->name : '待定';
                            $awayName = $game->awayTeam !== null ? $game->awayTeam->name : '待定';
                            $scoreText = '—';
                            if ($game->home_score !== null && $game->away_score !== null) {
                                $scoreText = $game->home_score . '-' . $game->away_score;
                                if ($game->home_penalty_score !== null && $game->away_penalty_score !== null) {
                                    $scoreText .= '(' . $game->home_penalty_score . '-'
                                        . $game->away_penalty_score . ')';
                                }
                            }
                            $statusText = isset($statusLabels[(int) $game->status])
                                ? $statusLabels[(int) $game->status] : '未知';
                            $matchTime = $game->match_time !== null && $game->match_time !== ''
                                ? $game->match_time : '待定';
                            ?>
                            <tr>
                                <td><?= Html::encode($matchTime) ?></td>
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
                                <td><?= Html::a(Html::encode($scoreText), [
                                    '/game/view',
                                    'id' => $game->id,
                                ], ['class' => 'game-score']) ?></td>
                                <td><?= $game->venue !== null && $game->venue !== ''
                                    ? Html::encode($game->venue) : '—' ?></td>
                                <td><?= Html::encode($statusText) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endforeach ?>
    <?php endif ?>

    <h2>数据图表</h2>
    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>
</article>

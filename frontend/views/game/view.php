<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * Coding by 李行阳 2411353, 20261123
 * 模块:届次与比赛 —— 比赛详情：比分板、基本信息、进球时间线。
 *
 * 2026-11-23 重排：比分板本身作为页面 h1，去掉「标题 + 比分板」重复；
 * 时间线之后给出下一步（晋级图 / 同届比赛 / 返回列表），不留死路。
 *
 * @var yii\web\View $this
 * @var common\models\Game $model
 * @var common\models\Goal[] $goals
 * @var common\models\Player[] $players
 * @var array $statusLabels
 */
use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

$homeName = $model->homeTeam !== null ? $model->homeTeam->name : '待定';
$awayName = $model->awayTeam !== null ? $model->awayTeam->name : '待定';
$tournamentName = $model->tournament !== null ? $model->tournament->name : '待定';
$tournamentId = $model->tournament_id;
$statusValue = (int) $model->status;
$statusText = isset($statusLabels[$statusValue]) ? $statusLabels[$statusValue] : '未知';
$matchTime = $model->match_time !== null && $model->match_time !== ''
    ? $model->match_time : null;
$venue = $model->venue !== null && $model->venue !== '' ? $model->venue : null;
$hasScore = $model->home_score !== null && $model->away_score !== null;
$mainScore = $hasScore ? $model->home_score . '–' . $model->away_score : 'VS';
$penaltyText = $model->home_penalty_score !== null && $model->away_penalty_score !== null
    ? '点球 ' . $model->home_penalty_score . '–' . $model->away_penalty_score
    : null;

$this->title = $homeName . ' vs ' . $awayName;
$this->params['breadcrumbs'][] = ['label' => '比赛', 'url' => ['index']];
if ($model->tournament !== null) {
    $this->params['breadcrumbs'][] = [
        'label' => $model->tournament->name,
        'url' => ['/tournament/view', 'id' => $tournamentId],
    ];
}
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$this->registerCssFile('@web/css/game.css');

$scoreLabel = $hasScore ? $model->home_score . ' 比 ' . $model->away_score : '比分待补';
if ($penaltyText !== null) {
    $scoreLabel .= '，' . $penaltyText;
}

// 点球大战行不是常规进球:单独成段、单独计数，也不参与“加时”判断。
$lastGoalMinute = 0;
$regularGoals = [];
$shootoutGoals = [];
foreach ($goals as $goal) {
    if ((int) $goal->is_shootout === 1) {
        $shootoutGoals[] = $goal;
        continue;
    }
    $regularGoals[] = $goal;
    $lastGoalMinute = max($lastGoalMinute, (int) $goal->minute);
}
$regularGoalCount = count($regularGoals);
$shootoutGoalCount = count($shootoutGoals);
$wentToExtraTime = $lastGoalMinute > 90;

$renderGoalRow = static function ($goal, array $players, $model, $homeName, $awayName) {
    $player = isset($players[(int) $goal->player_id])
        ? $players[(int) $goal->player_id] : null;
    $assist = $goal->assist_player_id !== null
        && isset($players[(int) $goal->assist_player_id])
        ? $players[(int) $goal->assist_player_id] : null;
    $isShootout = (int) $goal->is_shootout === 1;
    $goalTeamName = (int) $goal->team_id === (int) $model->home_team_id ? $homeName : $awayName;

    if ($isShootout) {
        $minuteLabel = '点球';
    } elseif ($goal->minute !== null) {
        $minuteLabel = $goal->minute . "'";
    } else {
        $minuteLabel = '—';
    }

    $html = '<li class="goal-item">';
    $html .= '<span class="goal-minute">' . Html::encode($minuteLabel) . '</span>';
    $html .= '<span class="goal-player"'
        . ($player !== null ? ' title="' . Html::encode($player->name) . '"' : '') . '>'
        . Html::encode($player !== null ? $player->displayName() : '未知球员') . '</span>';
    $html .= '<span class="goal-team">' . Html::encode($goalTeamName) . '</span>';
    if (!$isShootout && (int) $goal->is_penalty === 1) {
        $html .= '<span class="label label-info">点球</span>';
    }
    if (!$isShootout && (int) $goal->is_own_goal === 1) {
        $html .= '<span class="label label-warning">乌龙球</span>';
    }
    if ($assist !== null) {
        $html .= '<span class="goal-assist" title="' . Html::encode($assist->name) . '">助攻：'
            . Html::encode($assist->displayName()) . '</span>';
    }

    return $html . '</li>';
};

// 无进球有三种情况，空态要分别说清楚，不能一律说“数据待补”。
if ($statusValue === Game::STATUS_PENDING) {
    $noGoalMessage = '比赛尚未开始，暂无进球。';
} elseif ($hasScore && (int) $model->home_score === 0 && (int) $model->away_score === 0) {
    $noGoalMessage = '本场 0–0，没有进球。';
} else {
    $noGoalMessage = '这场比赛的进球会在数据补齐后显示；比分与赛果以上方比分为准。';
}
?>
<article class="catalog-page game-detail">
    <header class="game-hero">
        <p class="game-context">
            <?php if ($model->tournament !== null): ?>
                <?= Html::a(Html::encode($tournamentName), ['/tournament/view', 'id' => $tournamentId]) ?>
                <span aria-hidden="true">·</span>
            <?php endif ?>
            <?= Html::encode($model->stage) ?>
        </p>

        <h1 class="game-scoreboard" aria-label="<?= Html::encode($homeName . ' 对 ' . $awayName . '，' . $scoreLabel) ?>">
            <span class="game-team">
                <?php if ($model->homeTeam !== null): ?>
                    <?= Html::a(Html::encode($homeName), ['/team/view', 'id' => $model->home_team_id]) ?>
                <?php else: ?>
                    <?= Html::encode($homeName) ?>
                <?php endif ?>
            </span>
            <span class="game-score-main">
                <span class="game-score-value"><?= Html::encode($mainScore) ?></span>
                <?php if ($penaltyText !== null): ?>
                    <span class="game-penalty"><?= Html::encode($penaltyText) ?></span>
                <?php endif ?>
            </span>
            <span class="game-team">
                <?php if ($model->awayTeam !== null): ?>
                    <?= Html::a(Html::encode($awayName), ['/team/view', 'id' => $model->away_team_id]) ?>
                <?php else: ?>
                    <?= Html::encode($awayName) ?>
                <?php endif ?>
            </span>
        </h1>

        <dl class="game-facts">
            <div>
                <dt>开球</dt>
                <dd><?= $matchTime !== null ? Html::encode($matchTime) : '时间待补' ?></dd>
            </div>
            <div>
                <dt>场地</dt>
                <dd><?= $venue !== null ? Html::encode($venue) : '场地待补' ?></dd>
            </div>
            <div>
                <dt>状态</dt>
                <dd>
                    <?= Html::encode($statusText) ?>
                    <?php if ($wentToExtraTime): ?>
                        <span aria-hidden="true">·</span> 加时
                    <?php endif ?>
                </dd>
            </div>
        </dl>
    </header>

    <?= $this->render('_score_progress', [
        'regularGoals' => $regularGoals,
        'players' => $players,
        'model' => $model,
        'homeName' => $homeName,
        'awayName' => $awayName,
    ]) ?>

    <section class="game-section" aria-labelledby="game-goals-title">
        <div class="game-section-head">
            <h2 id="game-goals-title"><?= $regularGoalCount > 0 ? '进球时间线' : ($shootoutGoalCount > 0 ? '点球大战' : '进球时间线') ?></h2>
            <?php if ($regularGoalCount > 0 || $shootoutGoalCount > 0): ?>
                <p>
                    <?php if ($regularGoalCount > 0): ?>
                        共 <?= $regularGoalCount ?> 个进球，按分钟排列。
                        <?php if ($shootoutGoalCount > 0): ?>
                            点球大战另记 <?= $shootoutGoalCount ?> 罚。
                        <?php endif ?>
                    <?php elseif ($shootoutGoalCount > 0): ?>
                        共 <?= $shootoutGoalCount ?> 罚，仅记录命中。
                    <?php endif ?>
                </p>
            <?php endif ?>
        </div>

        <?php if (!$regularGoals && !$shootoutGoals): ?>
            <div class="empty-state">
                <h3><?= $statusValue === Game::STATUS_PENDING ? '比赛尚未开始' : '暂无进球记录' ?></h3>
                <p><?= Html::encode($noGoalMessage) ?></p>
            </div>
        <?php else: ?>
            <?php if ($regularGoals): ?>
                <ul class="goal-timeline">
                    <?php foreach ($regularGoals as $goal): ?>
                        <?= $renderGoalRow($goal, $players, $model, $homeName, $awayName) ?>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>

            <?php if ($shootoutGoals): ?>
                <div class="game-shootout">
                    <?php if ($regularGoals): ?>
                        <h3 class="game-shootout-title">
                            点球大战
                            <span>共 <?= $shootoutGoalCount ?> 罚，仅记录命中</span>
                        </h3>
                    <?php endif ?>
                    <ul class="goal-timeline goal-timeline--shootout">
                        <?php foreach ($shootoutGoals as $goal): ?>
                            <?= $renderGoalRow($goal, $players, $model, $homeName, $awayName) ?>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>
        <?php endif ?>
    </section>

    <nav class="game-next" aria-label="接下来">
        <?php if ($model->tournament !== null): ?>
            <?php if ($model->stage === Game::STAGE_GROUP): ?>
                <?= Html::a('看小组积分', ['/tournament/view', 'id' => $tournamentId, '#' => 'groups'], [
                    'class' => 'btn btn-primary',
                ]) ?>
                <?= Html::a('同届小组赛', ['index', 'tournamentId' => $tournamentId, 'stage' => '小组赛'], [
                    'class' => 'btn btn-default',
                ]) ?>
            <?php else: ?>
                <?= Html::a('看本届晋级图', ['/tournament/view', 'id' => $tournamentId, '#' => 'knockout'], [
                    'class' => 'btn btn-primary',
                ]) ?>
                <?= Html::a('同届全部比赛', ['index', 'tournamentId' => $tournamentId], [
                    'class' => 'btn btn-default',
                ]) ?>
            <?php endif ?>
        <?php endif ?>
        <?= Html::a('返回比赛列表', ['index'], ['class' => 'game-next-back']) ?>
    </nav>
</article>

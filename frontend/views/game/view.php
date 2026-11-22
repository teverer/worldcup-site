<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 比赛详情、比分点球、进球时间线与留言区。
 *
 * @var yii\web\View $this
 * @var common\models\Game $model
 * @var common\models\Goal[] $goals
 * @var common\models\Player[] $players
 * @var array $statusLabels
 */
use common\components\CommentTarget;
use frontend\widgets\CommentSection;
use yii\helpers\Html;

$homeName = $model->homeTeam !== null ? $model->homeTeam->name : '待定';
$awayName = $model->awayTeam !== null ? $model->awayTeam->name : '待定';
$tournamentName = $model->tournament !== null ? $model->tournament->name : '待定';
$statusValue = (int) $model->status;
$statusText = isset($statusLabels[$statusValue]) ? $statusLabels[$statusValue] : '未知';
$matchTime = $model->match_time !== null && $model->match_time !== ''
    ? $model->match_time : '待定';
$venue = $model->venue !== null && $model->venue !== '' ? $model->venue : '场地待定';
$hasScore = $model->home_score !== null && $model->away_score !== null;
$mainScore = $hasScore ? $model->home_score . ' - ' . $model->away_score : 'VS';
$penaltyText = $model->home_penalty_score !== null && $model->away_penalty_score !== null
    ? '点球 ' . $model->home_penalty_score . ' - ' . $model->away_penalty_score
    : null;

$this->title = $homeName . ' vs ' . $awayName;
$this->params['breadcrumbs'][] = ['label' => '比赛目录', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$this->registerCssFile('@web/css/game.css');
?>
<article class="catalog-page game-detail">
    <header class="catalog-heading">
        <p class="catalog-kicker">
            <?= Html::a(Html::encode($tournamentName), ['/tournament/view', 'id' => $model->tournament_id]) ?>
            · <?= Html::encode($model->stage) ?>
        </p>
        <h1><?= Html::encode($homeName) ?> <span class="text-muted">vs</span> <?= Html::encode($awayName) ?></h1>
        <p><?= Html::encode($matchTime) ?> · <?= Html::encode($venue) ?> · <?= Html::encode($statusText) ?></p>
    </header>

    <section class="game-scoreboard" aria-label="比赛比分">
        <div class="game-team">
            <?= Html::a(Html::encode($homeName), ['/team/view', 'id' => $model->home_team_id]) ?>
        </div>
        <div class="game-score-main">
            <span class="game-score-value"><?= Html::encode($mainScore) ?></span>
            <?php if ($penaltyText !== null): ?>
                <span class="game-penalty"><?= Html::encode($penaltyText) ?></span>
            <?php endif ?>
        </div>
        <div class="game-team">
            <?= Html::a(Html::encode($awayName), ['/team/view', 'id' => $model->away_team_id]) ?>
        </div>
    </section>

    <section class="game-section" aria-labelledby="game-goals-title">
    <h2 class="section-title" id="game-goals-title">进球时间线</h2>
    <?php if (!$goals): ?>
        <div class="empty-state">
            <h3>暂无进球记录</h3>
            <p>该场比赛的进球会在数据补齐后显示。</p>
        </div>
    <?php else: ?>
        <ul class="goal-timeline">
            <?php foreach ($goals as $goal): ?>
                <?php
                $player = isset($players[(int) $goal->player_id])
                    ? $players[(int) $goal->player_id] : null;
                $assist = $goal->assist_player_id !== null
                    && isset($players[(int) $goal->assist_player_id])
                    ? $players[(int) $goal->assist_player_id] : null;
                $isHomeGoal = (int) $goal->team_id === (int) $model->home_team_id;
                if ($isHomeGoal) {
                    $goalTeamName = $homeName;
                } else {
                    $goalTeamName = $awayName;
                }
                if ((int) $goal->is_shootout === 1) {
                    $minuteLabel = '点球大战';
                } elseif ($goal->minute !== null) {
                    $minuteLabel = $goal->minute . "'";
                } else {
                    $minuteLabel = '—';
                }
                ?>
                <li class="goal-item">
                    <span class="goal-minute"><?= Html::encode($minuteLabel) ?></span>
                    <span class="goal-player"><?= Html::encode($player !== null
                        ? $player->name : '未知球员') ?></span>
                    <span class="goal-team"><?= Html::encode($goalTeamName) ?></span>
                    <?php if ((int) $goal->is_penalty === 1 && (int) $goal->is_shootout !== 1): ?>
                        <span class="label label-info">点球</span>
                    <?php endif ?>
                    <?php if ((int) $goal->is_own_goal === 1): ?>
                        <span class="label label-warning">乌龙球</span>
                    <?php endif ?>
                    <?php if ((int) $goal->is_shootout === 1): ?>
                        <span class="label label-default">点球大战</span>
                    <?php endif ?>
                    <?php if ($assist !== null): ?>
                        <span class="goal-assist">助攻：<?= Html::encode($assist->name) ?></span>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    </section>
</article>

<?= CommentSection::widget([
    'targetType' => CommentTarget::GAME,
    'targetId' => $model->id,
]) ?>

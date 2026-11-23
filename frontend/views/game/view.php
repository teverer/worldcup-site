<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * Coding by 李行阳 2411353, 20261123
 * 模块:届次与比赛 —— 比赛详情：比分板、基本信息、进球时间线与留言区。
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
use common\components\CommentTarget;
use frontend\widgets\CommentSection;
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
                <dd><?= Html::encode($statusText) ?></dd>
            </div>
        </dl>
    </header>

    <section class="game-section" aria-labelledby="game-goals-title">
        <div class="game-section-head">
            <h2 id="game-goals-title">进球时间线</h2>
            <?php if ($goals): ?>
                <p>共 <?= count($goals) ?> 个进球，按分钟排列。</p>
            <?php endif ?>
        </div>

        <?php if (!$goals): ?>
            <div class="empty-state">
                <h3>暂无进球记录</h3>
                <p>这场比赛的进球会在数据补齐后显示；比分与赛果以上方比分为准。</p>
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
                    $goalTeamName = $isHomeGoal ? $homeName : $awayName;

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

    <nav class="game-next" aria-label="接下来">
        <?php if ($model->tournament !== null): ?>
            <?= Html::a('看本届晋级图', ['/tournament/view', 'id' => $tournamentId, '#' => 'knockout'], [
                'class' => 'btn btn-primary',
            ]) ?>
            <?= Html::a('同届全部比赛', ['index', 'tournamentId' => $tournamentId], [
                'class' => 'btn btn-default',
            ]) ?>
        <?php endif ?>
        <?= Html::a('返回比赛列表', ['index'], ['class' => 'game-next-back']) ?>
    </nav>
</article>

<?= CommentSection::widget([
    'targetType' => CommentTarget::GAME,
    'targetId' => $model->id,
]) ?>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:届次与比赛 —— 比赛详情「比分走势」:按进球分钟累计的阶梯线。
 *
 * 只画常规进球(不含点球大战);不足两个进球时返回,交给下方时间线。
 *
 * @var yii\web\View $this
 * @var common\models\Goal[] $regularGoals
 * @var common\models\Player[] $players
 * @var common\models\Game $model
 * @var string $homeName
 * @var string $awayName
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

if ($model->home_team_id === null || $model->away_team_id === null) {
    return;
}

$events = [];
foreach ($regularGoals as $goal) {
    if ($goal->minute === null) {
        continue;
    }
    $isHome = (int) $goal->team_id === (int) $model->home_team_id;
    $player = isset($players[(int) $goal->player_id]) ? $players[(int) $goal->player_id] : null;
    $events[] = [
        'minute' => (int) $goal->minute,
        'team' => $isHome ? 'home' : 'away',
        'teamName' => $isHome ? $homeName : $awayName,
        'player' => $player !== null ? $player->displayName() : '未知球员',
        'penalty' => (int) $goal->is_penalty === 1,
        'ownGoal' => (int) $goal->is_own_goal === 1,
    ];
}

if (count($events) < 2) {
    return;
}

usort($events, function ($a, $b) {
    return $a['minute'] <=> $b['minute'];
});

$homeScore = 0;
$awayScore = 0;
$homeSeries = [[0, 0]];
$awaySeries = [[0, 0]];
$marks = [];
$lastMinute = 0;

foreach ($events as $event) {
    if ($event['team'] === 'home') {
        $homeScore++;
    } else {
        $awayScore++;
    }
    $lastMinute = max($lastMinute, $event['minute']);
    $homeSeries[] = [$event['minute'], $homeScore];
    $awaySeries[] = [$event['minute'], $awayScore];
    $marks[] = [
        'minute' => $event['minute'],
        'score' => $event['team'] === 'home' ? $homeScore : $awayScore,
        'team' => $event['team'],
        'teamName' => $event['teamName'],
        'player' => $event['player'],
        'penalty' => $event['penalty'],
        'ownGoal' => $event['ownGoal'],
        'homeScore' => $homeScore,
        'awayScore' => $awayScore,
    ];
}

$maxMinute = $lastMinute > 90 ? 120 : 90;
$homeSeries[] = [$maxMinute, $homeScore];
$awaySeries[] = [$maxMinute, $awayScore];

$this->registerCssFile('@web/css/charts.css');
$this->registerCssFile('@web/css/chart-stage.css');
$this->registerCssFile('@web/css/game-chart.css');
$this->registerJsFile('@web/js/charts.js', [
    'position' => View::POS_END,
    'id' => 'charts-js',
]);

$payload = [
    'homeName' => $homeName,
    'awayName' => $awayName,
    'maxMinute' => $maxMinute,
    'homeSeries' => $homeSeries,
    'awaySeries' => $awaySeries,
    'marks' => $marks,
];
?>
<section class="game-section game-progress" aria-labelledby="game-progress-title">
    <div class="game-section-head">
        <h2 id="game-progress-title">比分走势</h2>
        <p>按进球分钟累计；点球大战不计入。</p>
    </div>
    <div id="game-score-chart" class="chart-canvas chart-canvas--game"
         role="img" data-echarts-src="<?= Html::encode(Url::to('@web/js/vendor/echarts.min.js')) ?>"
         aria-label="比赛比分随时间变化的阶梯图">
        <p class="chart-loading">图表加载中…</p>
    </div>
    <script type="application/json" id="game-score-data"><?= json_encode($payload, JSON_UNESCAPED_UNICODE) ?></script>
</section>

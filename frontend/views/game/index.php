<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * Coding by 李行阳 2411353, 20261123
 * 模块:届次与比赛 —— 比赛列表：默认十一场决赛，再按条件找任意一场。
 *
 * 2026-11-23 重排：原来的「一张 708 行表 + 7 列」改成
 * 「身份头 → 筛选行 → 默认决赛名单 / 筛选结果」；比分是行里的主角。
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var int|null $tournamentId
 * @var string|null $stage
 * @var string|null $group
 * @var string|null $teamKeyword
 * @var bool $hasFilters
 * @var bool $showingAll
 * @var int $totalGames
 * @var array $tournamentOptions
 * @var array $stageOptions
 * @var array $groupOptions
 */
use common\components\TeamFlag;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = '比赛';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$this->registerCssFile('@web/css/game.css');

$games = $dataProvider->getModels();

// 把当前条件翻译成可读的一行，避免访客忘了自己筛了什么。
$activeFilters = [];
if ($tournamentId !== null && isset($tournamentOptions[$tournamentId])) {
    $activeFilters[] = $tournamentOptions[$tournamentId];
}
if ($stage !== null) {
    $activeFilters[] = $stage;
}
if ($group !== null) {
    $activeFilters[] = $group . ' 组';
}
if ($teamKeyword !== null) {
    $activeFilters[] = '球队含「' . $teamKeyword . '」';
}
?>
<section class="catalog-page game-page">
    <header class="game-heading">
        <div class="game-heading-main">
            <h1>比赛</h1>
        </div>
        <div class="idx-total" aria-label="共 <?= (int) $totalGames ?> 场比赛，四十年">
            <b><?= (int) $totalGames ?></b><span>场比赛 · 四十年</span>
        </div>
    </header>

    <?php $filterCount = count($activeFilters); ?>
    <?= Html::beginForm(['index'], 'get', ['class' => 'pl-search', 'role' => 'search']) ?>
    <div class="pl-search-main">
        <label class="sr-only" for="game-team">球队</label>
        <?= Html::input('search', 'team', $teamKeyword, [
            'id' => 'game-team',
            'class' => 'form-control pl-input',
            'maxlength' => 50,
            'placeholder' => '搜索球队，如 阿根廷',
        ]) ?>
        <?= Html::submitButton('查找比赛', ['class' => 'btn btn-primary']) ?>
    </div>
    <div class="pl-search-filters">
        <label class="sr-only" for="game-tournament">届次</label>
        <?= Html::dropDownList('tournamentId', $tournamentId, $tournamentOptions, [
            'id' => 'game-tournament',
            'class' => 'form-control',
            'prompt' => '全部届次',
        ]) ?>
        <label class="sr-only" for="game-stage">阶段</label>
        <?= Html::dropDownList('stage', $stage, $stageOptions, [
            'id' => 'game-stage',
            'class' => 'form-control',
            'prompt' => '全部阶段',
        ]) ?>
        <label class="sr-only" for="game-group">小组</label>
        <?= Html::dropDownList('group', $group, $groupOptions, [
            'id' => 'game-group',
            'class' => 'form-control',
            'prompt' => '全部小组',
        ]) ?>
        <?php if ($filterCount > 0): ?>
            <?= Html::a('清除条件', ['index'], ['class' => 'pl-clear']) ?>
        <?php endif ?>
    </div>
    <?= Html::endForm() ?>

    <div class="game-list-head">
        <div>
            <?php if (!$showingAll): ?>
                <h2>十一场决赛</h2>
            <?php elseif ($hasFilters): ?>
                <h2>筛选结果</h2>
                <p>
                    共 <?= (int) $dataProvider->totalCount ?> 场
                    <?php foreach ($activeFilters as $filter): ?>
                        <span class="game-chip"><?= Html::encode($filter) ?></span>
                    <?php endforeach ?>
                </p>
            <?php else: ?>
                <h2>全部比赛</h2>
                <p>共 <?= (int) $totalGames ?> 场，按比赛时间倒序。</p>
            <?php endif ?>
        </div>
        <?php if ($showingAll): ?>
            <?= Html::a('回到十一场决赛', ['index'], ['class' => 'game-list-alt']) ?>
        <?php elseif ($hasFilters): ?>
            <?= Html::a('看全部比赛', ['index', 'scope' => 'all'], [
                'class' => 'game-list-alt',
            ]) ?>
        <?php endif ?>
    </div>

    <?php if (!$games): ?>
        <div class="empty-state">
            <h2>没有找到比赛</h2>
            <p>试试减少条件，或从十一场决赛开始。</p>
            <?= Html::a('回到十一场决赛', ['index'], ['class' => 'btn btn-primary']) ?>
        </div>
    <?php else: ?>
        <ol class="game-list">
            <?php foreach ($games as $game): ?>
                <?php
                $homeName = $game->homeTeam !== null ? $game->homeTeam->name : '待定';
                $awayName = $game->awayTeam !== null ? $game->awayTeam->name : '待定';
                $tournamentName = $game->tournament !== null ? (string) $game->tournament->name : '';
                $year = $game->tournament !== null ? (string) $game->tournament->year : '';
                $editionName = preg_replace('/^\d{4}年/u', '', $tournamentName);
                $date = $game->match_time !== null && $game->match_time !== ''
                    ? substr((string) $game->match_time, 0, 10) : '';
                // 卡片左上角已经有年份;这里只留月日,完整日期放进 title。
                $dateShort = $date !== '' ? substr($date, 5) : '';

                $hasScore = $game->home_score !== null && $game->away_score !== null;
                $scoreText = $hasScore
                    ? (int) $game->home_score . '–' . (int) $game->away_score
                    : 'VS';

                // 胜者只加粗队名，不加颜色，避免页面上出现第二种强调。
                $winner = '';
                if ($hasScore) {
                    if ((int) $game->home_score > (int) $game->away_score) {
                        $winner = 'home';
                    } elseif ((int) $game->home_score < (int) $game->away_score) {
                        $winner = 'away';
                    } elseif ($game->home_penalty_score !== null && $game->away_penalty_score !== null) {
                        $winner = (int) $game->home_penalty_score > (int) $game->away_penalty_score
                            ? 'home' : 'away';
                    }
                }

                $penaltyText = null;
                if ($game->home_penalty_score !== null && $game->away_penalty_score !== null) {
                    $penaltyText = '点球 ' . (int) $game->home_penalty_score
                        . '–' . (int) $game->away_penalty_score;
                }
                ?>
                <li class="game-item">
                    <a class="game-item-link" href="<?= Html::encode(Url::to(['view', 'id' => $game->id])) ?>">
                        <span class="game-item-year"><?= Html::encode($year) ?></span>
                        <span class="game-item-main">
                            <span class="game-item-stage">
                                <?= Html::encode(trim($editionName)) ?>
                                <span aria-hidden="true">·</span>
                                <?= Html::encode($game->stage) ?>
                            </span>
                            <span class="game-item-teams">
                                <span class="game-item-team<?= $winner === 'home' ? ' is-winner' : '' ?>" title="<?= Html::encode($homeName) ?>">
                                    <?= TeamFlag::html($game->homeTeam !== null ? $game->homeTeam->code : '', $homeName) ?>
                                    <span class="game-item-team-name"><?= Html::encode($homeName) ?></span>
                                </span>
                                <span class="game-item-score"><?= Html::encode($scoreText) ?></span>
                                <span class="game-item-team<?= $winner === 'away' ? ' is-winner' : '' ?>" title="<?= Html::encode($awayName) ?>">
                                    <?= TeamFlag::html($game->awayTeam !== null ? $game->awayTeam->code : '', $awayName) ?>
                                    <span class="game-item-team-name"><?= Html::encode($awayName) ?></span>
                                </span>
                            </span>
                            <?php if ($penaltyText !== null): ?>
                                <span class="game-item-note"><?= Html::encode($penaltyText) ?></span>
                            <?php endif ?>
                        </span>
                        <span class="game-item-date"<?= $date !== '' ? ' title="' . Html::encode($date) . '"' : '' ?>><?= Html::encode($dateShort) ?></span>
                    </a>
                </li>
            <?php endforeach ?>
            <?php // 十一场决赛正好差一格；第 12 格做成“全部比赛”，两列收齐，不留孤行。 ?>
            <?php if (!$showingAll && !$hasFilters): ?>
                <li class="game-item game-item--all">
                    <a class="game-all-card" href="<?= Html::encode(Url::to(['index', 'scope' => 'all'])) ?>">
                        <span class="game-all-kicker">全部</span>
                        <strong class="game-all-title">浏览全部 <?= (int) $totalGames ?> 场比赛</strong>
                    </a>
                </li>
            <?php endif ?>
        </ol>

        <?php if ($dataProvider->pagination !== false && $dataProvider->pagination->getPageCount() > 1): ?>
            <div class="game-pagination">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->pagination,
                    'options' => ['class' => 'pagination'],
                    'prevPageLabel' => '上一页',
                    'nextPageLabel' => '下一页',
                ]) ?>
            </div>
        <?php endif ?>
    <?php endif ?>
</section>

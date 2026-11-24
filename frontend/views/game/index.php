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
            <p>先看十一场决赛，或按届次、阶段、球队找到任意一场。</p>
        </div>
        <div class="idx-total" aria-label="共 <?= (int) $totalGames ?> 场比赛，十一届">
            <b><?= (int) $totalGames ?></b><span>场比赛 · 十一届</span>
        </div>
    </header>

    <?php $filterCount = count($activeFilters); ?>
    <details class="game-filter-panel"<?= $filterCount > 0 ? ' open' : '' ?>>
        <summary class="game-filter-summary">
            <span>筛选比赛</span>
            <span class="game-filter-summary-note">
                <?= $filterCount > 0 ? '已应用 ' . (int) $filterCount . ' 项条件' : '按届次、阶段、小组、球队' ?>
            </span>
        </summary>
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
    <div class="form-group game-filter-group">
        <?= Html::label('小组', 'game-group') ?>
        <?= Html::dropDownList('group', $group, $groupOptions, [
            'id' => 'game-group',
            'class' => 'form-control',
            'prompt' => '全部小组',
        ]) ?>
    </div>
    <div class="form-group game-filter-team">
        <?= Html::label('球队', 'game-team') ?>
        <?= Html::textInput('team', $teamKeyword, [
            'id' => 'game-team',
            'class' => 'form-control',
            'maxlength' => 50,
            'placeholder' => '如：阿根廷',
        ]) ?>
    </div>
    <div class="form-group catalog-filter-actions">
        <?= Html::submitButton('筛选', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('重置', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
    <?= Html::endForm() ?>
    </details>

    <div class="game-list-head">
        <div>
            <?php if (!$showingAll): ?>
                <h2>十一场决赛</h2>
                <p>每一届的最后一战；点一行看进球时间线。</p>
            <?php elseif ($hasFilters): ?>
                <h2>筛选结果</h2>
                <p>
                    共 <?= (int) $dataProvider->totalCount ?> 场
                    <?php foreach ($activeFilters as $filter): ?>
                        <span class="game-chip"><?= Html::encode($filter) ?></span>
                    <?php endforeach ?>
                    <?= Html::a('清除条件', ['index'], ['class' => 'game-clear']) ?>
                </p>
            <?php else: ?>
                <h2>全部比赛</h2>
                <p>共 <?= (int) $totalGames ?> 场，按比赛时间倒序。</p>
            <?php endif ?>
        </div>
        <?php if ($showingAll): ?>
            <?= Html::a('回到十一场决赛', ['index'], ['class' => 'game-list-alt']) ?>
        <?php else: ?>
            <?= Html::a('查看全部 ' . (int) $totalGames . ' 场', ['index', 'scope' => 'all'], [
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
                        <span class="game-item-date"><?= Html::encode($date) ?></span>
                    </a>
                </li>
            <?php endforeach ?>
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

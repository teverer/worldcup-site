<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261117
 * 模块:界面设计 —— 首页
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;
use frontend\models\HomeStats;

$this->title = '用数据看懂世界杯';

$this->registerCss(<<<'CSS'
.home-hero .hero-kicker { margin-bottom: var(--space-2); color: var(--color-accent); font-size: var(--fs-sm); font-weight: 600; letter-spacing: .08em; }
.home-hero .hero-actions { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.home-hero .hero-actions .btn { text-decoration: none; }
.match-date { color: var(--color-muted); font-size: var(--fs-sm); white-space: nowrap; }
.match-score { color: var(--color-text); text-decoration: none; }
.match-score:hover,
.match-score:focus { color: var(--color-primary); text-decoration: none; }
.match-penalty { margin-left: 2px; color: var(--color-muted); font-size: var(--fs-sm); font-weight: 500; }
.tournament-name,
.news-title { margin: var(--space-1) 0; font-size: var(--fs-lg); line-height: 1.35; }
.tournament-meta { margin: 0; color: var(--color-muted); font-size: var(--fs-sm); }
.news-meta { display: flex; justify-content: space-between; gap: var(--space-2); margin: var(--space-2) 0 0; color: var(--color-muted); font-size: var(--fs-sm); }
@media (max-width: 991px) {
    .home-panels > .home-panel { grid-column: span 12; }
}
@media (max-width: 767px) {
    /* 手机端 4 个数据方块排成 2×2,避免占满一整屏 */
    .home-stats { gap: var(--space-2); }
    .home-stats > .stat { grid-column: span 6; }
    /* 该区块只展示已结束比赛,手机上省略重复的状态标签以压缩卡片高度 */
    .home-page .match-status { display: none; }
    /* 射手榜在手机上隐藏球队列,四列压缩为三列,无需横向滚动 */
    body .home-page .scorer-table { min-width: 0; }
    .home-page .scorer-table .scorer-team { display: none; }
}
CSS
);

$overview = HomeStats::overview();
$recentGames = HomeStats::recentGames(6);

// 射手榜默认展示最近一届有已结束比赛的届次,届次动态查询,不写死编号。
$scorerTournament = HomeStats::latestFinishedTournament();
$topScorers = $scorerTournament !== null
    ? HomeStats::topScorers($scorerTournament->id, 5)
    : [];
// 届次名称去掉年份前缀后与年份拼接,如「2022 卡塔尔世界杯」。
$scorerName = $scorerTournament !== null
    ? trim(preg_replace(
        '/^' . preg_quote((string) $scorerTournament->year, '/') . '年?/u',
        '',
        $scorerTournament->name
    ))
    : '';
$scorerTitle = $scorerTournament !== null
    ? $scorerTournament->year . ($scorerName !== '' ? ' ' . $scorerName : '') . ' · 射手榜 Top 5'
    : '射手榜 Top 5';

$recentTournaments = HomeStats::recentTournaments(4);
$latestNews = HomeStats::latestNews(3);

$statItems = [
    ['value' => $overview['tournaments'], 'label' => '届次'],
    ['value' => $overview['teams'], 'label' => '球队数'],
    ['value' => $overview['games'], 'label' => '比赛场次'],
    ['value' => $overview['goals'], 'label' => '进球总数'],
];
?>
<div class="site-index home-page">

    <section class="hero home-hero">
        <div class="hero-content">
            <p class="hero-kicker">1986–2026 · 十一届世界杯</p>
            <h1 class="hero-title">用数据看懂世界杯</h1>
            <p class="hero-lead">从墨西哥到美加墨,回看四十年的比分、射手与经典瞬间。</p>
            <div class="hero-actions">
                <?= Html::a('浏览历届世界杯', ['/tournament/index'], ['class' => 'btn btn-accent']) ?>
                <?= Html::a('查看比赛记录', ['/game/index'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </section>

    <section class="section home-overview">
        <div class="section-heading">
            <h2 class="section-title">数据概览</h2>
            <p class="section-subtitle">全部数据均来自站内数据库实时统计。</p>
        </div>
        <div class="grid home-stats">
            <?php foreach ($statItems as $item): ?>
                <div class="stat span-3">
                    <span class="stat-value"><?= Html::encode((string) $item['value']) ?></span>
                    <span class="stat-label"><?= Html::encode($item['label']) ?></span>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <div class="grid home-panels">
        <section class="section home-panel span-6">
            <div class="section-heading section-heading-inline">
                <h2 class="section-title">最近比赛</h2>
                <?= Html::a('全部比赛', ['/game/index'], ['class' => 'btn btn-ghost btn-sm']) ?>
            </div>

            <?php if (!$recentGames): ?>
                <div class="empty-state">暂无已结束的比赛。</div>
            <?php else: ?>
                <div class="match-list stack-sm">
                    <?php foreach ($recentGames as $game): ?>
                        <?php
                        $homeName = $game->homeTeam !== null ? $game->homeTeam->name : '待定';
                        $awayName = $game->awayTeam !== null ? $game->awayTeam->name : '待定';
                        $matchDate = $game->match_time !== null && $game->match_time !== ''
                            ? substr($game->match_time, 0, 10) : '时间待定';
                        $scoreText = $game->home_score !== null && $game->away_score !== null
                            ? $game->home_score . '-' . $game->away_score : '—';
                        $hasPenalty = $game->home_penalty_score !== null && $game->away_penalty_score !== null;
                        ?>
                        <article class="card match-row">
                            <div class="card-body match-row-body"
                                 style="display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;">
                                <div class="match-meta" style="display: flex; align-items: center; gap: .5rem; min-width: 8rem;">
                                    <span class="match-date"><?= Html::encode($matchDate) ?></span>
                                    <span class="badge"><?= Html::encode($game->stage) ?></span>
                                </div>
                                <div class="match-fixture"
                                     style="display: flex; align-items: center; gap: .75rem; flex: 1 1 16rem; min-width: 0;">
                                    <span class="match-team match-team-home"
                                          style="flex: 1; text-align: right;"><?= Html::encode($homeName) ?></span>
                                    <a class="match-score" href="<?= Html::encode(Url::to(['/game/view', 'id' => $game->id])) ?>"
                                       style="font-variant-numeric: tabular-nums; font-weight: 700; white-space: nowrap; text-align: center;">
                                        <?= Html::encode($scoreText) ?><?php if ($hasPenalty): ?><span
                                            class="match-penalty">(<?= Html::encode($game->home_penalty_score . '-' . $game->away_penalty_score) ?>)</span><?php endif ?>
                                    </a>
                                    <span class="match-team match-team-away"
                                          style="flex: 1; text-align: left;"><?= Html::encode($awayName) ?></span>
                                </div>
                                <div class="match-status" style="text-align: right; margin-left: auto;">
                                    <span class="badge badge-primary">已结束</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>

        <section class="section home-panel span-6">
            <div class="section-heading section-heading-inline">
                <h2 class="section-title"><?= Html::encode($scorerTitle) ?></h2>
                <div style="display: flex; flex-wrap: wrap; gap: .5rem;">
                    <?php if ($scorerTournament !== null): ?>
                        <?= Html::a('查看该届', ['/tournament/view', 'id' => $scorerTournament->id], ['class' => 'btn btn-ghost btn-sm']) ?>
                    <?php endif ?>
                    <?= Html::a('查看球员', ['/player/index'], ['class' => 'btn btn-ghost btn-sm']) ?>
                </div>
            </div>

            <?php if ($scorerTournament === null): ?>
                <div class="empty-state">暂无比赛数据</div>
            <?php elseif (!$topScorers): ?>
                <div class="empty-state">暂无进球数据。</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table scorer-table">
                        <caption class="sr-only">本届赛事射手榜前五名</caption>
                        <thead>
                        <tr>
                            <th scope="col" style="width: 4.5rem;">排名</th>
                            <th scope="col">球员</th>
                            <th scope="col" class="scorer-team">球队</th>
                            <th scope="col" class="num">进球</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($topScorers as $index => $scorer): ?>
                            <?php $isTopThree = $index < 3; ?>
                            <tr>
                                <td>
                                    <span class="badge<?= $isTopThree ? ' badge-accent' : '' ?>"><?= Html::encode((string) ($index + 1)) ?></span>
                                </td>
                                <td><?= Html::encode($scorer['player_name']) ?></td>
                                <td class="scorer-team"><?= Html::encode($scorer['team_name'] !== null ? $scorer['team_name'] : '待定') ?></td>
                                <td class="num"><?= Html::encode((string) $scorer['goals']) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </section>
    </div>

    <section class="section home-tournaments">
        <div class="section-heading section-heading-inline">
            <h2 class="section-title">历届世界杯</h2>
            <?= Html::a('全部届次', ['/tournament/index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>

        <?php if (!$recentTournaments): ?>
            <div class="empty-state">暂无届次数据。</div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($recentTournaments as $tournament): ?>
                    <a class="card card-hover tournament-card span-3"
                       href="<?= Html::encode(Url::to(['/tournament/view', 'id' => $tournament->id])) ?>"
                       style="color: inherit; text-decoration: none;">
                        <div class="card-body">
                            <span class="badge"><?= Html::encode((string) $tournament->year) ?></span>
                            <h3 class="tournament-name"><?= Html::encode($tournament->name) ?></h3>
                            <p class="tournament-meta">东道主:<?= Html::encode($tournament->host_country !== null && $tournament->host_country !== ''
                                ? $tournament->host_country : '待定') ?></p>
                            <p class="tournament-meta">参赛队:<?= Html::encode($tournament->team_count !== null
                                ? $tournament->team_count . ' 支' : '待定') ?></p>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>

    <section class="section home-news">
        <div class="section-heading section-heading-inline">
            <h2 class="section-title">最新资讯</h2>
            <?= Html::a('全部资讯', ['/news/index'], ['class' => 'btn btn-ghost btn-sm']) ?>
        </div>

        <?php if (!$latestNews): ?>
            <div class="empty-state">暂无已发布资讯。</div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($latestNews as $news): ?>
                    <?php
                    $newsDate = $news->publish_time !== null && $news->publish_time !== ''
                        ? substr($news->publish_time, 0, 10) : substr($news->create_time, 0, 10);
                    ?>
                    <a class="card card-hover news-card span-4"
                       href="<?= Html::encode(Url::to(['/news/view', 'id' => $news->id])) ?>"
                       style="color: inherit; text-decoration: none;">
                        <div class="card-body">
                            <h3 class="news-title"><?= Html::encode($news->title) ?></h3>
                            <p class="news-meta">
                                <span><?= Html::encode($news->author !== null && $news->author !== ''
                                    ? $news->author : '佚名') ?></span>
                                <span><?= Html::encode($newsDate) ?></span>
                            </p>
                        </div>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>

</div>

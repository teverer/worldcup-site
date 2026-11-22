<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次列表页
 *
 * @var yii\web\View $this
 * @var array $decades 按年代分组的届次数据
 * @var array $stats 页头统计
 */
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '历届世界杯';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/tournament-index.css');

$latestId = null;
if ($decades) {
    $firstDecade = reset($decades);
    $firstEdition = reset($firstDecade);
    $latestId = $firstEdition['id'];
}
?>
<div class="tournament-chronicle">
    <header class="chronicle-hero">
        <div class="chronicle-hero-main">
            <p class="chronicle-kicker">
                <span class="chronicle-kicker-rule" aria-hidden="true"></span>
                FIFA WORLD CUP · 1986—2026
            </p>
            <h1 class="chronicle-title">
                十一届世界杯
                <span class="chronicle-title-dot" aria-hidden="true">·</span>
                <span class="chronicle-title-gold">四十年荣耀</span>
            </h1>
            <p class="chronicle-lead">
                四十年，十一届，只有一座大力神杯。每一次决赛，都是一个国家把名字刻进历史的夜晚。
            </p>
        </div>

        <dl class="chronicle-stats" aria-label="世界杯数据统计">
            <?php foreach ($stats as $stat): ?>
                <div class="chronicle-stat">
                    <dt class="chronicle-stat-label"><?= Html::encode($stat['label']) ?></dt>
                    <dd class="chronicle-stat-value"><?= Html::encode((string) $stat['value']) ?></dd>
                </div>
            <?php endforeach ?>
        </dl>
    </header>

    <div class="chronicle-intro">
        <div>
            <h2 class="chronicle-intro-title">届次编年</h2>
            <p class="chronicle-intro-sub">从美加墨回望墨西哥，一年一年，读一座奖杯的来路。</p>
        </div>
        <p class="chronicle-intro-order">按年份倒序 · 2026 → 1986</p>
    </div>

    <?php if (!$decades): ?>
        <div class="empty-state">
            <h2>暂无届次数据</h2>
            <p>数据库中还没有可展示的世界杯届次。</p>
        </div>
    <?php else: ?>
        <div class="chronicle-wall">
            <?php foreach ($decades as $decade => $editions): ?>
                <section class="decade-section decade-<?= Html::encode((string) $decade) ?>"
                         aria-labelledby="decade-<?= Html::encode((string) $decade) ?>">
                    <header class="decade-heading">
                        <h2 class="decade-label" id="decade-<?= Html::encode((string) $decade) ?>">
                            <?= Html::encode((string) $decade) ?>s
                        </h2>
                        <span class="decade-rule" aria-hidden="true"></span>
                        <span class="decade-count"><?= Html::encode((string) count($editions)) ?> 届</span>
                    </header>

                    <?php foreach ($editions as $edition): ?>
                        <?php $final = $edition['final']; ?>
                        <a class="edition-row<?= $edition['id'] === $latestId ? ' is-latest' : '' ?>"
                           href="<?= Html::encode(Url::to(['view', 'id' => $edition['id']])) ?>">
                            <div class="edition-year">
                                <span class="edition-year-number"><?= Html::encode((string) $edition['year']) ?></span>
                                <span class="edition-ordinal">第 <?= Html::encode((string) $edition['ordinal']) ?> 届</span>
                            </div>

                            <div class="edition-body">
                                <h3 class="edition-name"><?= Html::encode($edition['name']) ?></h3>
                                <dl class="edition-meta">
                                    <div class="edition-fact">
                                        <dt>东道主</dt>
                                        <dd><?= Html::encode($edition['host']) ?></dd>
                                    </div>
                                    <div class="edition-fact">
                                        <dt>赛期</dt>
                                        <dd><?= Html::encode($edition['date_range']) ?></dd>
                                    </div>
                                    <div class="edition-fact">
                                        <dt>规模</dt>
                                        <dd><?= $edition['team_count'] !== null
                                            ? Html::encode((string) $edition['team_count']) . ' 支'
                                            : '待定' ?></dd>
                                    </div>
                                </dl>
                                <?php if ($edition['format'] !== ''): ?>
                                    <p class="edition-format">
                                        <span>赛制</span><?= Html::encode($edition['format']) ?>
                                    </p>
                                <?php endif ?>
                                <p class="edition-volume">
                                    <?= Html::encode((string) $edition['games']) ?> 场比赛
                                    <span aria-hidden="true">·</span>
                                    <?= Html::encode((string) $edition['goals']) ?> 粒进球
                                </p>
                            </div>

                            <div class="edition-honor">
                                <?php if ($final !== null && $final['champion'] !== null): ?>
                                    <p class="honor-title">
                                        <span class="honor-caption">冠军</span>
                                        <strong class="honor-champion"><?= Html::encode($final['champion']) ?></strong>
                                    </p>
                                    <p class="honor-final">
                                        <span class="honor-final-label">决赛</span>
                                        <span class="honor-scoreline">
                                            <b><?= Html::encode($final['champion_score'] . '-' . $final['opponent_score']) ?></b>
                                            <span class="honor-opponent"><?= Html::encode($final['opponent']) ?></span>
                                        </span>
                                        <?php if ($final['extra_time']): ?>
                                            <span class="honor-badge">加时</span>
                                        <?php endif ?>
                                        <?php if ($final['has_penalty']): ?>
                                            <span class="honor-badge honor-badge-penalty">
                                                点球 <?= Html::encode($final['penalty_text']) ?>
                                            </span>
                                        <?php endif ?>
                                    </p>
                                    <p class="honor-meta">
                                        <span class="honor-meta-item">
                                            <span class="honor-meta-label">亚军</span>
                                            <strong><?= Html::encode($final['runner_up']) ?></strong>
                                        </span>
                                    </p>
                                <?php else: ?>
                                    <p class="honor-title">
                                        <span class="honor-caption">荣誉</span>
                                    </p>
                                    <p class="honor-pending">决赛数据待补</p>
                                    <p class="honor-meta">
                                        <span class="honor-meta-item">
                                            <span class="honor-meta-label">场次</span>
                                            <strong><?= Html::encode((string) $edition['games']) ?></strong>
                                        </span>
                                        <span class="honor-meta-item">
                                            <span class="honor-meta-label">总进球</span>
                                            <strong class="honor-meta-goals"><?= Html::encode((string) $edition['goals']) ?></strong>
                                        </span>
                                    </p>
                                <?php endif ?>
                            </div>

                            <div class="edition-action">
                                <span class="edition-action-text">查看该届</span>
                                <span class="edition-arrow" aria-hidden="true">→</span>
                            </div>
                        </a>
                    <?php endforeach ?>
                </section>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

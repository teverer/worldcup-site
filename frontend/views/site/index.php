<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 根页记分牌墙
 *
 * @var yii\web\View $this
 * @var array $editions 按年份倒序的届次数据
 * @var array $latestComments 首页“最新留言”摘要
 */

use frontend\widgets\CommentSection;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '世界杯档案 · 1986—2026';
$this->registerCssFile('@web/css/root-wall.css');

$latestId = $editions !== [] ? (int) $editions[0]['id'] : null;
$dataUrl = $latestId !== null
    ? Url::to(['/tournament/view', 'id' => $latestId]) . '#charts'
    : Url::to(['/game/index']);
?>
<div class="root-wall">
    <header class="wall-head">
        <div class="wall-head-main">
            <h1 class="wall-title">
                <span class="wall-range">1986—2026</span>
                <span class="wall-subtitle">十一届世界杯</span>
            </h1>
            <p class="wall-lead">
                从墨西哥到美加墨，四十年，十一届。选一届，进入那一届的晋级图、积分与射手。
            </p>
        </div>

    </header>

    <section class="wall" aria-label="历届世界杯">
        <?php if (!$editions): ?>
            <div class="wall-empty">
                <strong>暂无届次数据</strong>
                <p>数据库中还没有可展示的世界杯届次。</p>
            </div>
        <?php else: ?>
            <?php foreach ($editions as $index => $edition): ?>
                <?php
                $final = $edition['final'];
                $isLit = $index === 0;
                $hasChampion = $final !== null && $final['champion'] !== null;
                $boardLabel = $edition['year'] . ' ' . $edition['name'];
                if ($hasChampion) {
                    $boardLabel .= '，冠军 ' . $final['champion'];
                }
                ?>
                <a class="wall-board<?= $isLit ? ' is-lit' : '' ?>"
                   href="<?= Html::encode(Url::to(['/tournament/view', 'id' => $edition['id']])) ?>"
                   aria-label="<?= Html::encode($boardLabel . '，进入本届') ?>">
                    <span class="board-year"><?= Html::encode((string) $edition['year']) ?></span>

                    <span class="board-main">
                        <span class="board-name"><?= Html::encode($edition['name']) ?></span>
                        <span class="board-host">
                            东道主 <?= Html::encode($edition['host']) ?>
                            <span aria-hidden="true">·</span>
                            第 <?= Html::encode((string) $edition['ordinal']) ?> 届
                        </span>
                        <span class="board-final">
                            <?php if ($hasChampion): ?>
                                <span>冠军 <strong class="board-champion"><?= Html::encode($final['champion']) ?></strong></span>
                                <span>
                                    决赛
                                    <span class="board-score"><?= Html::encode($final['champion_score'] . '–' . $final['opponent_score']) ?></span>
                                    <?= Html::encode($final['opponent']) ?>
                                </span>
                                <?php if ($final['has_penalty']): ?>
                                    <span class="board-badge">点球 <?= Html::encode(str_replace('-', '–', $final['penalty_text'])) ?></span>
                                <?php elseif ($final['extra_time']): ?>
                                    <span class="board-badge">加时</span>
                                <?php endif ?>
                            <?php else: ?>
                                <span>决赛数据待补</span>
                            <?php endif ?>
                        </span>
                    </span>

                    <span class="board-volume">
                        <?= Html::encode((string) $edition['games']) ?> 场 /
                        <?= Html::encode((string) $edition['goals']) ?> 球
                    </span>
                    <span class="board-enter">进入</span>
                </a>
            <?php endforeach ?>
        <?php endif ?>
    </section>

    <nav class="wall-ways" aria-label="换一种找法">
        <span class="wall-ways-label">换一种找法</span>
        <?= Html::a('按球队', ['/team/index']) ?>
        <?= Html::a('按球员', ['/player/index']) ?>
        <?= Html::a('按比赛', ['/game/index']) ?>
        <?= Html::a('看数据', $dataUrl) ?>
    </nav>

    <section class="wall-comments" aria-labelledby="wall-comments-title">
        <header class="wall-comments-head">
            <h2 class="wall-comments-title" id="wall-comments-title">最新留言</h2>
            <span class="wall-comments-note">来自各届、资讯与全站</span>
            <?= Html::a('去世界杯留言簿', ['/site/discussion'], ['class' => 'wall-comments-more']) ?>
        </header>
        <?php if (!empty($latestComments)): ?>
            <div class="wall-comments-list">
                <?php foreach ($latestComments as $item): ?>
                    <a class="wall-comment" href="<?= Html::encode($item['url']) ?>">
                        <span class="wall-comment-source"><?= Html::encode($item['source']) ?></span>
                        <span class="wall-comment-meta">
                            <span class="seal wall-comment-seal"
                                  style="background:<?= Html::encode(CommentSection::sealColor($item['userId'])) ?>"
                                  aria-hidden="true"><?= Html::encode(CommentSection::sealInitial($item['author'])) ?></span>
                            <span class="wall-comment-name"><?= Html::encode($item['author']) ?></span>
                            <time class="wall-comment-time"
                                  datetime="<?= Html::encode($item['datetime']) ?>"><?= Html::encode($item['date']) ?></time>
                        </span>
                        <span class="wall-comment-text"><?= Html::encode($item['excerpt']) ?></span>
                    </a>
                <?php endforeach ?>
            </div>
        <?php else: ?>
            <p class="wall-comments-empty">还没有人留言。<?= Html::a('来写第一句', ['/site/discussion']) ?></p>
        <?php endif ?>
    </section>

    <p class="wall-about"><?= Html::a('关于与文档', ['/team-info/index']) ?></p>
</div>

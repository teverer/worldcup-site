<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 根页记分牌墙
 *
 * @var yii\web\View $this
 * @var array $editions 按年份倒序的届次数据
 */

use common\components\CommentTarget;
use common\components\TeamFlag;
use frontend\widgets\CommentSection;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '世界杯档案 · 1986—2026';
$this->registerCssFile('@web/css/root-wall.css');

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
                        <span class="board-meta">
                            <span class="board-host">
                                东道主 <?= Html::encode($edition['host']) ?>
                            </span>
                            <span class="board-volume">
                                <?= Html::encode((string) $edition['games']) ?> 场 /
                                <?= Html::encode((string) $edition['goals']) ?> 球
                            </span>
                        </span>
                    </span>

                    <?php if ($hasChampion): ?>
                        <span class="board-final">
                            <span class="board-side board-side--champion">
                                <?= TeamFlag::html($final['champion_code'], $final['champion'], 'board-flag') ?>
                                <span class="board-side-text">
                                    <span class="board-role">冠军</span>
                                    <span class="board-team"><?= Html::encode($final['champion']) ?></span>
                                </span>
                            </span>
                            <span class="board-score-wrap">
                                <span class="board-score"><?= Html::encode($final['champion_score'] . '–' . $final['opponent_score']) ?></span>
                                <?php if ($final['has_penalty']): ?>
                                    <span class="board-badge">点球 <?= Html::encode(str_replace('-', '–', $final['penalty_text'])) ?></span>
                                <?php elseif ($final['extra_time']): ?>
                                    <span class="board-badge">加时</span>
                                <?php endif ?>
                            </span>
                            <span class="board-side board-side--runner">
                                <span class="board-side-text">
                                    <span class="board-role">亚军</span>
                                    <span class="board-team"><?= Html::encode($final['opponent']) ?></span>
                                </span>
                                <?= TeamFlag::html($final['opponent_code'], $final['opponent'], 'board-flag') ?>
                            </span>
                        </span>
                    <?php else: ?>
                        <span class="board-final board-final--pending">决赛数据待补</span>
                    <?php endif ?>
                </a>
            <?php endforeach ?>
        <?php endif ?>
    </section>

    <?php // 收场不是墙尾的“更多入口”：墙看完了，这里只做一件事——把记忆留下。
    // 首页直接嵌入全站留言区本身，输入后就在本页落到自己的记录上，不再跳走。 ?>
    <section class="wall-closing" id="comments" aria-label="留言簿">
        <div class="wall-coda-inner">
            <?= CommentSection::widget([
                'targetType' => CommentTarget::SITE,
                'targetId' => CommentTarget::SITE_ID,
                'heading' => '继续看，或者留一句',
                'headingTag' => 'h2',
                'emptyTitle' => '留言簿还是空的。',
            ]) ?>
        </div>
    </section>
</div>

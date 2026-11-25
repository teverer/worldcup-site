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
use frontend\models\EditionImage;
use frontend\widgets\CommentSection;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '世界杯40年概展 · 1986—2026';
$this->registerCssFile('@web/css/root-wall.css');

// 首页小缩略图：设 false 即可整块回滚，不改变墙的网格。
$showBoardThumbs = true;

?>
<div class="root-wall">
    <header class="wall-head">
        <div class="wall-head-main">
            <h1 class="wall-title">
                <span class="wall-range">1986—2026</span>
                <span class="wall-subtitle">四十年十一届世界杯,从墨西哥到美加墨,足球的最高荣誉一直在等待着国足的到来
                </span>
            </h1>
        </div>

    </header>

    <section class="wall" aria-label="历届世界杯">
        <?php if (!$editions): ?>
            <div class="wall-empty">
                <strong>暂无届次数据</strong>
                <p>数据库中还没有可展示的世界杯届次。</p>
            </div>
        <?php else: ?>
            <?php foreach ($editions as $edition): ?>
                <?php
                $final = $edition['final'];
                $hasChampion = $final !== null && $final['champion'] !== null;
                $boardLabel = $edition['year'] . ' ' . $edition['name'];
                if ($hasChampion) {
                    $boardLabel .= '，冠军 ' . $final['champion'];
                }
                ?>
                <a class="wall-board"
                   href="<?= Html::encode(Url::to(['/tournament/view', 'id' => $edition['id']])) ?>"
                   aria-label="<?= Html::encode($boardLabel . '，进入本届') ?>">
                    <span class="board-year"><?= Html::encode((string) $edition['year']) ?></span>

                    <span class="board-main">
                        <?php
                        $thumb = $showBoardThumbs
                            ? EditionImage::forYear((int) $edition['year'])
                            : null;
                        ?>
                        <?php if ($thumb !== null): ?>
                            <span class="board-thumb" aria-hidden="true">
                                <img src="<?= Html::encode($thumb['thumb_url']) ?>"
                                     alt="" width="112" height="63"
                                     loading="lazy" decoding="async">
                            </span>
                        <?php endif ?>
                        <span class="board-main-text">
                            <span class="board-name"><?= Html::encode($edition['name']) ?></span>
                            <span class="board-meta">
                                <span class="board-host">
                                    东道主 <?= Html::encode($edition['host']) ?>
                                </span>
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
                'heading' => '全站留言簿',
                'headingTag' => 'h2',
                'emptyTitle' => '留言簿还是空的。',
            ]) ?>
        </div>
    </section>
</div>

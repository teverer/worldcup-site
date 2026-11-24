<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:纪录页 —— 黑金颁奖：四十年之最、十一场决赛、历届总射手
 *
 * @var yii\web\View $this
 * @var array $totals
 * @var array $moments
 * @var array $finals
 * @var array $scorers
 */

use common\components\TeamFlag;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '纪录';
?>
<div class="record-page">

    <section class="record-hero">
        <div class="record-wrap">
            <p class="record-kicker">1986 — 2026 · 十一届世界杯</p>
            <div class="record-hero-row">
                <div class="record-hero-main">
                    <h1>纪录</h1>
                    <p class="record-lede">四十年，只留下几个数字。每一个都能回到那一届、那一场，或那个人。</p>
                </div>
                <dl class="record-totals">
                    <div>
                        <dt>届次</dt>
                        <dd><?= (int) $totals['editions'] ?><small>届</small></dd>
                    </div>
                    <div>
                        <dt>场次</dt>
                        <dd><?= (int) $totals['games'] ?><small>场</small></dd>
                    </div>
                    <div>
                        <dt>总进球</dt>
                        <dd><?= (int) $totals['goals'] ?><small>球</small></dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="record-section record-section--moments" aria-labelledby="record-moments-title">
        <div class="record-wrap">
            <header class="record-section-head">
                <h2 id="record-moments-title">四十年之最</h2>
                <p>只列数据能证明的事；点进去，看它发生在哪一夜。</p>
            </header>
            <ol class="moment-grid">
                <?php foreach ($moments as $moment): ?>
                    <li class="moment">
                        <a class="moment-link" href="<?= Html::encode(Url::to($moment['url'])) ?>">
                            <span class="moment-value">
                                <?= Html::encode($moment['value']) ?><small><?= Html::encode($moment['unit']) ?></small>
                            </span>
                            <span class="moment-body">
                                <strong class="moment-title"><?= Html::encode($moment['title']) ?></strong>
                                <span class="moment-text"><?= Html::encode($moment['copy']) ?></span>
                                <span class="moment-meta"><?= Html::encode($moment['meta']) ?></span>
                            </span>
                            <span class="moment-action"><?= Html::encode($moment['action']) ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ol>
        </div>
    </section>

    <section class="record-section record-section--finals" aria-labelledby="record-finals-title">
        <div class="record-wrap">
            <header class="record-section-head">
                <h2 id="record-finals-title">冠军的十一夜</h2>
                <p>十一场决赛，从马拉多纳的 1986 到 2026。金色一行是捧杯的人。</p>
            </header>
            <ol class="final-list">
                <?php foreach ($finals as $final): ?>
                    <li class="final-row">
                        <a href="<?= Html::encode(Url::to(['/game/view', 'id' => $final['game_id']])) ?>">
                            <span class="final-year"><?= (int) $final['year'] ?></span>
                            <span class="final-champ">
                                <?php if ($final['champion_code'] !== null): ?>
                                    <?= TeamFlag::html($final['champion_code'], (string) $final['champion_name'], 'final-flag') ?>
                                <?php endif ?>
                                <strong><?= Html::encode($final['champion_name'] !== null ? $final['champion_name'] : '待补') ?></strong>
                                <span class="final-champ-label">冠军</span>
                            </span>
                            <span class="final-score">
                                <?= Html::encode($final['score']) ?>
                                <?php if ($final['penalty'] !== null): ?>
                                    <small>点球 <?= Html::encode($final['penalty']) ?></small>
                                <?php endif ?>
                            </span>
                            <span class="final-runner">
                                <span><?= Html::encode($final['runner_name'] !== null ? $final['runner_name'] : '待补') ?></span>
                                <?php if ($final['runner_code'] !== null): ?>
                                    <?= TeamFlag::html($final['runner_code'], (string) $final['runner_name'], 'final-flag') ?>
                                <?php endif ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ol>
        </div>
    </section>

    <section class="record-section record-section--scorers" aria-labelledby="record-scorers-title">
        <div class="record-wrap">
            <header class="record-section-head">
                <h2 id="record-scorers-title">历届总射手</h2>
                <p>跨十一届累计；不含乌龙球与点球大战进球。按进球数排序，同数先看最近一届。</p>
            </header>
            <ol class="scorer-list">
                <?php foreach ($scorers as $scorer): ?>
                    <li class="scorer-row">
                        <a href="<?= Html::encode(Url::to(['/player/view', 'id' => $scorer['player_id']])) ?>">
                            <span class="scorer-rank"><?= str_pad((string) $scorer['rank'], 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="scorer-name">
                                <strong><?= Html::encode($scorer['display_name']) ?></strong>
                                <small>
                                    <?php if ($scorer['code'] !== ''): ?>
                                        <?= TeamFlag::html($scorer['code'], $scorer['nationality'], 'scorer-flag') ?>
                                    <?php endif ?>
                                    <?= Html::encode($scorer['nationality']) ?>
                                    · <?= (int) $scorer['first_year'] ?>–<?= (int) $scorer['last_year'] ?>
                                    · <?= (int) $scorer['editions'] ?> 届
                                </small>
                            </span>
                            <span class="scorer-goals"><?= (int) $scorer['goals'] ?><small>球</small></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ol>
        </div>
    </section>

    <section class="record-outro">
        <div class="record-wrap">
            <p>纪录只是结论。四十年真正的样子，在十一块记分牌里。</p>
            <a class="record-cta" href="<?= Html::encode(Yii::$app->homeUrl) ?>">回到记分牌墙</a>
        </div>
    </section>
</div>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:纪录页 —— 黑金颁奖：四十年之最、十一场决赛、历届总射手
 *
 * @var yii\web\View $this
 * @var array $totals
 * @var array $moments
 * @var array $clock
 * @var array $finals
 * @var array $nations
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

    <section class="record-section record-section--clock" aria-labelledby="record-clock-title">
        <div class="record-wrap">
            <header class="record-section-head">
                <h2 id="record-clock-title">十一届的进球时钟</h2>
                <p>每一行是一届；格内为该届进球落在该时段的比例（%），颜色越亮占比越高。点年份进入那一届。</p>
            </header>

            <?php if ($clock['max_late'] !== null && $clock['min_late'] !== null): ?>
                <p class="clock-lede">
                    <?= (int) $clock['max_late']['year'] ?> 年，
                    <?= Html::encode(number_format($clock['max_late']['late_percent'], 1)) ?>% 的进球在下半场；
                    <?= (int) $clock['min_late']['year'] ?> 年只有
                    <?= Html::encode(number_format($clock['min_late']['late_percent'], 1)) ?>%。
                    四十年里，进球时钟并没有一路向后。
                </p>
            <?php endif ?>

            <div class="heat-scroll">
                <table class="goal-clock">
                    <caption class="sr-only">十一届世界杯按 15 分钟时段统计的进球占比，不含点球大战。</caption>
                    <thead>
                        <tr>
                            <th scope="col">届次</th>
                            <?php foreach ($clock['buckets'] as $label): ?>
                                <th scope="col"><?= Html::encode($label) ?></th>
                            <?php endforeach ?>
                            <th scope="col" class="clock-total-head">进球</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clock['editions'] as $edition): ?>
                            <tr>
                                <th scope="row">
                                    <a href="<?= Html::encode(Url::to(['/tournament/view', 'id' => $edition['tournament_id']])) ?>">
                                        <?= (int) $edition['year'] ?>
                                    </a>
                                </th>
                                <?php foreach ($edition['cells'] as $cell): ?>
                                    <td class="heat-cell<?= $cell['percent'] >= 15 ? ' is-hot' : '' ?>"
                                        style="--heat-alpha: <?= Html::encode((string) $cell['alpha']) ?>">
                                        <?= Html::encode(number_format($cell['percent'], 1)) ?>
                                    </td>
                                <?php endforeach ?>
                                <td class="clock-total"><?= (int) $edition['total'] ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <p class="heat-hint">左右滑动看完整时段</p>
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

    <section class="record-section record-section--nations" aria-labelledby="record-nations-title">
        <div class="record-wrap">
            <header class="record-section-head">
                <h2 id="record-nations-title">谁常在决赛</h2>
                <p>把十一场决赛按国家累计。西德计入德国；只统计进过决赛的球队。</p>
            </header>
            <table class="nation-table">
                <caption class="sr-only">按国家统计的世界杯决赛次数</caption>
                <thead>
                    <tr>
                        <th scope="col">国家</th>
                        <th scope="col">冠军</th>
                        <th scope="col">亚军</th>
                        <th scope="col">决赛</th>
                        <th scope="col" class="nation-span-col">跨度</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nations as $nation): ?>
                        <tr>
                            <th scope="row">
                                <?php if ($nation['code'] === 'GER'): ?>
                                    <?php // 西德与德国合并在这一行，任何单个球队页都只覆盖一半，所以不做链接。 ?>
                                    <span class="nation-name">
                                        <?= TeamFlag::html($nation['code'], $nation['name'], 'nation-flag') ?>
                                        <strong><?= Html::encode($nation['name']) ?></strong>
                                    </span>
                                <?php else: ?>
                                    <a class="nation-name" href="<?= Html::encode(Url::to(['/team/view', 'id' => $nation['id']])) ?>">
                                        <?= TeamFlag::html($nation['code'], $nation['name'], 'nation-flag') ?>
                                        <strong><?= Html::encode($nation['name']) ?></strong>
                                    </a>
                                <?php endif ?>
                            </th>
                            <td class="nation-title"><?= (int) $nation['titles'] ?></td>
                            <td class="nation-count"><?= (int) $nation['runner_up'] ?></td>
                            <td class="nation-count"><?= (int) $nation['finals'] ?></td>
                            <td class="nation-span nation-span-col">
                                <?= (int) $nation['first'] ?><?= $nation['first'] !== $nation['last'] ? '–' . (int) $nation['last'] : '' ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
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
            <p class="record-scope">口径：只统计已结束比赛；射手不含乌龙球与点球大战；决赛聚合把西德计入德国。</p>
        </div>
    </section>
</div>

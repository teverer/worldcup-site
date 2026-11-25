<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:届次与比赛 —— 统计页的历届对照
 *
 * 历届总射手 / 谁常在决赛紧接在本届两张图下面，同列对照；四强版图接在后面。
 * 当前届次用金色行、冠军行和「本届 N 球」标出，其余行退后但仍可读。
 *
 * @var yii\web\View $this
 * @var array $history
 * @var array $currentGoals player_id => goals
 * @var array|null $champion
 * @var array|null $runnerUp
 * @var array $stats
 * @var int $currentEditionId
 * @var int $currentYear
 */

use common\components\TeamFlag;
use yii\helpers\Html;
use yii\helpers\Url;

$scorers = isset($history['scorers']) && is_array($history['scorers']) ? $history['scorers'] : [];
$nations = isset($history['nations']) && is_array($history['nations']) ? $history['nations'] : [];
$four = isset($history['four']) && is_array($history['four']) ? $history['four'] : ['editions' => [], 'legends' => []];

// 西德 / 德国在决赛聚合里合成一行；这里也用同一个键比较当前届冠军。
$normCode = static function ($code) {
    $code = strtoupper(trim((string) $code));
    return in_array($code, ['FRG', 'GER'], true) ? 'GER' : $code;
};
$championCode = $champion !== null ? $normCode(isset($champion['code']) ? $champion['code'] : '') : '';
$runnerCode = $runnerUp !== null ? $normCode(isset($runnerUp['code']) ? $runnerUp['code'] : '') : '';

$currentFour = null;
foreach ($four['editions'] as $edition) {
    if ((int) $edition['tournament_id'] === $currentEditionId) {
        $currentFour = $edition;
        break;
    }
}
$contSlug = [
    '欧洲' => 'europe',
    '南美洲' => 'south-america',
    '非洲' => 'africa',
    '亚洲' => 'asia',
    '中北美' => 'north-america',
    '大洋洲' => 'oceania',
    '其他' => 'other',
];
?>
<div class="stats-hist-grid">
    <section class="stats-hsection" id="hist-scorers" aria-labelledby="hist-scorers-title">
        <header class="stats-hsection-head">
            <h3 id="hist-scorers-title">四十年总射手榜</h3>
        </header>

        <table class="scorer-table">
            <caption class="sr-only">四十年总射手榜</caption>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">球员</th>
                    <th scope="col">国家</th>
                    <th scope="col">届次</th>
                    <th scope="col" class="num">总进球</th>
                    <th scope="col" class="num">本届</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scorers as $scorer): ?>
                    <?php $currentGoalsForPlayer = isset($currentGoals[$scorer['player_id']]) ? (int) $currentGoals[$scorer['player_id']] : 0; ?>
                    <tr class="<?= $currentGoalsForPlayer > 0 ? 'is-current' : '' ?>">
                        <td class="scorer-rank"><?= str_pad((string) $scorer['rank'], 2, '0', STR_PAD_LEFT) ?></td>
                        <th scope="row" class="scorer-player">
                            <a href="<?= Html::encode(Url::to(['/player/view', 'id' => $scorer['player_id']])) ?>"><?= Html::encode($scorer['display_name']) ?></a>
                        </th>
                        <td class="scorer-nation">
                            <?php if ($scorer['code'] !== ''): ?>
                                <?= TeamFlag::html($scorer['code'], $scorer['nationality'], 'scorer-flag') ?>
                            <?php endif ?>
                            <?= Html::encode($scorer['nationality']) ?>
                        </td>
                        <td class="scorer-years"><?= (int) $scorer['first_year'] ?>–<?= (int) $scorer['last_year'] ?> · <?= (int) $scorer['editions'] ?> 届</td>
                        <td class="num scorer-total"><?= (int) $scorer['goals'] ?></td>
                        <td class="num scorer-current"><?= $currentGoalsForPlayer > 0 ? $currentGoalsForPlayer : '—' ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>

    <section class="stats-hsection" id="hist-nations" aria-labelledby="hist-nations-title">
        <header class="stats-hsection-head">
            <h3 id="hist-nations-title">国家决赛榜</h3>
            <p>共 <?= count($nations) ?> 个国家进过决赛；西德计入德国</p>
        </header>

        <table class="nation-table">
            <caption class="sr-only">按国家统计的世界杯决赛次数</caption>
            <thead>
                <tr>
                    <th scope="col">国家</th>
                    <th scope="col">冠军</th>
                    <th scope="col">亚军</th>
                    <th scope="col">决赛</th>
                    <th scope="col">本届</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nations as $nation): ?>
                    <?php
                    $nationCode = $normCode($nation['code']);
                    $isChampion = $championCode !== '' && $nationCode === $championCode;
                    $isRunner = $runnerCode !== '' && $nationCode === $runnerCode;
                    $rowClass = $isChampion ? ' is-champion' : ($isRunner ? ' is-runner' : '');
                    ?>
                    <tr class="<?= trim($rowClass) ?>">
                        <th scope="row">
                            <?php if ($nation['code'] === 'GER'): ?>
                                <?php // 西德与德国合成一行，任何单个球队页都只覆盖一半，所以不做链接。 ?>
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
                        <td class="nation-current">
                            <?php if ($isChampion): ?>
                                <span class="hist-tag hist-tag--gold">冠军</span>
                            <?php elseif ($isRunner): ?>
                                <span class="hist-tag hist-tag--silver">亚军</span>
                            <?php else: ?>
                                <span class="hist-empty">—</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>
</div>

<section class="stats-hsection stats-hsection--four" id="hist-four" aria-labelledby="hist-four-title">
    <header class="stats-hsection-head">
        <h3 id="hist-four-title">四强版图</h3>
    </header>

    <?php if ($currentFour !== null): ?>
        <p class="four-current">
            本届四强：<strong><?= Html::encode(implode('、', array_map(static function ($team) {
                return $team['name'];
            }, $currentFour['teams']))) ?></strong>
        </p>
    <?php endif ?>

    <div class="four-legend" aria-hidden="true">
        <?php foreach ($four['legends'] as $continent): ?>
            <span class="four-legend-item">
                <i class="four-swatch cont-<?= Html::encode($contSlug[$continent] ?? 'other') ?>"></i>
                <?= Html::encode($continent) ?>
            </span>
        <?php endforeach ?>
    </div>

    <ol class="four-list<?= $currentFour !== null ? ' has-current' : '' ?>">
        <?php foreach ($four['editions'] as $edition): ?>
            <?php
            $isCurrent = $currentFour !== null && (int) $edition['tournament_id'] === (int) $currentFour['tournament_id'];
            $teamNames = implode('、', array_map(static function ($team) {
                return $team['name'];
            }, $edition['teams']));
            $composition = implode('，', array_map(static function ($segment) {
                return $segment['continent'] . ' ' . $segment['count'] . ' 队';
            }, $edition['segments']));
            ?>
            <li class="four-row<?= $isCurrent ? ' is-current' : '' ?>">
                <a class="four-year"
                   href="<?= Html::encode(Url::to(['/tournament/view', 'id' => $edition['tournament_id']])) ?>"
                   aria-label="<?= Html::encode($edition['year'] . ' 届四强：' . $teamNames) ?>"
                   <?= $isCurrent ? 'aria-current="true"' : '' ?>>
                    <?= (int) $edition['year'] ?>
                    <?php if ($isCurrent): ?><span class="four-tag">本届</span><?php endif ?>
                </a>
                <span class="four-bar" role="img" aria-label="<?= Html::encode($composition) ?>">
                    <?php foreach ($edition['segments'] as $segment): ?>
                        <span class="four-seg cont-<?= Html::encode($contSlug[$segment['continent']] ?? 'other') ?>"
                              style="--w: <?= (int) $segment['percent'] ?>"
                              aria-hidden="true"><?= (int) $segment['count'] ?></span>
                    <?php endforeach ?>
                </span>
                <span class="four-names" title="<?= Html::encode($teamNames) ?>"><?= Html::encode($teamNames) ?></span>
            </li>
        <?php endforeach ?>
    </ol>

</section>

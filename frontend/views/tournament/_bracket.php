<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 淘汰赛晋级图
 *
 * 2026 的 32 强淘汰赛用「四区 + 决赛台」：顶部是半决赛 / 决赛，
 * 下方四个 1/4 区各是一棵 4 场 1/16 → 2 场 1/8 → 1 场 1/4 的小树；
 * 决赛在中央，四个区各产出一个半决赛席位。其它届仍是两侧向中间收拢的 16 强树。
 * 窄屏 / 数据不足八场时退回按轮次纵向排列的名单。
 * 卡片 = 国旗 + 中文队名 + 比分，整卡可点；冠军路径亮金。
 *
 * @var yii\web\View $this
 * @var array $bracket 轮次数组:key/label/matches
 * @var array|null $thirdPlaceMatch 三四名决赛节点
 * @var array $championPath 冠军晋级路径
 */

use common\components\TeamFlag;
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('@web/css/bracket.css');

$rounds = is_array($bracket) ? $bracket : [];
$roundByKey = [];
foreach ($rounds as $round) {
    if (isset($round['key'])) {
        $roundByKey[(string) $round['key']] = $round;
    }
}

/**
 * 按父场的两个参赛队,把子场按“父场主队 / 客队”的顺序排好。
 * 用于四区版式:决赛 → 半决赛 → 1/4 → 1/8 → 1/16 逐层收拢。
 * 任一环节无法由胜者唯一还原时返回 null,调用方回退到时间顺序。
 *
 * @param array $parent
 * @param array $candidates
 * @return array|null
 */
$feedersOf = static function (array $parent, array $candidates) {
    $byWinner = [];
    foreach ($candidates as $match) {
        $winnerId = null;
        if (!empty($match['home']['winner']) && isset($match['home']['team_id'])) {
            $winnerId = (int) $match['home']['team_id'];
        } elseif (!empty($match['away']['winner']) && isset($match['away']['team_id'])) {
            $winnerId = (int) $match['away']['team_id'];
        }
        if ($winnerId !== null) {
            $byWinner[$winnerId] = $match;
        }
    }

    // 卡片把胜者放在上行；子场顺序也跟着卡片的上/下行，
    // 否则会出现「上行写着法国，连过来的却是巴拉圭那场」的错位。
    $topSide = 'home';
    $bottomSide = 'away';
    if (!empty($parent['away']['winner']) && empty($parent['home']['winner'])) {
        $topSide = 'away';
        $bottomSide = 'home';
    }

    $feeders = [];
    foreach ([$topSide, $bottomSide] as $side) {
        $teamId = isset($parent[$side]['team_id']) ? (int) $parent[$side]['team_id'] : 0;
        if ($teamId <= 0 || !isset($byWinner[$teamId])) {
            return null;
        }
        $feeders[] = $byWinner[$teamId];
    }

    $firstId = isset($feeders[0]['id']) ? (int) $feeders[0]['id'] : 0;
    $secondId = isset($feeders[1]['id']) ? (int) $feeders[1]['id'] : 0;

    return $firstId > 0 && $secondId > 0 && $firstId !== $secondId ? $feeders : null;
};

/**
 * 按下一轮对阵还原本轮签表顺序:下一轮主/客队正是本轮两场胜者。
 * 数据层按比赛时间排序,时间顺序不一定等于签表配对,这里只调整展示顺序。
 * 任一环节无法由胜者唯一还原时返回 null,保留原顺序。
 */
$orderMatchesByNextRound = function (array $currentMatches, array $nextMatches) {
    $winnerIndex = [];
    foreach ($currentMatches as $index => $match) {
        $winnerTeamId = null;
        if (!empty($match['home']['winner']) && isset($match['home']['team_id'])) {
            $winnerTeamId = (int) $match['home']['team_id'];
        } elseif (!empty($match['away']['winner']) && isset($match['away']['team_id'])) {
            $winnerTeamId = (int) $match['away']['team_id'];
        }
        if ($winnerTeamId !== null) {
            $winnerIndex[$winnerTeamId] = $index;
        }
    }

    $ordered = [];
    $used = [];
    foreach ($nextMatches as $nextMatch) {
        $feederIndexes = [];
        foreach (['home', 'away'] as $side) {
            $teamId = isset($nextMatch[$side]['team_id'])
                ? (int) $nextMatch[$side]['team_id'] : 0;
            if ($teamId <= 0 || !isset($winnerIndex[$teamId])) {
                return null;
            }
            $feederIndexes[] = $winnerIndex[$teamId];
        }

        $topIndex = $feederIndexes[0];
        $bottomIndex = $feederIndexes[1];
        if ($topIndex === $bottomIndex || isset($used[$topIndex]) || isset($used[$bottomIndex])) {
            return null;
        }
        $used[$topIndex] = true;
        $used[$bottomIndex] = true;
        $ordered[] = $currentMatches[$topIndex];
        $ordered[] = $currentMatches[$bottomIndex];
    }

    return count($ordered) === count($currentMatches) ? $ordered : null;
};

// 从最后一轮向前还原签表顺序;决赛轮保持原样。
$orderedKeys = array_keys($roundByKey);
for ($cursor = count($orderedKeys) - 2; $cursor >= 0; $cursor--) {
    $currentKey = $orderedKeys[$cursor];
    $nextKey = $orderedKeys[$cursor + 1];
    $currentMatches = isset($roundByKey[$currentKey]['matches'])
        && is_array($roundByKey[$currentKey]['matches'])
        ? array_values($roundByKey[$currentKey]['matches']) : [];
    $nextMatches = isset($roundByKey[$nextKey]['matches'])
        && is_array($roundByKey[$nextKey]['matches'])
        ? array_values($roundByKey[$nextKey]['matches']) : [];
    if ($currentMatches === [] || $nextMatches === []) {
        continue;
    }
    $orderedMatches = $orderMatchesByNextRound($currentMatches, $nextMatches);
    if ($orderedMatches !== null) {
        $roundByKey[$currentKey]['matches'] = $orderedMatches;
    }
}

$matchesFor = static function ($key) use ($roundByKey) {
    return isset($roundByKey[$key]['matches']) && is_array($roundByKey[$key]['matches'])
        ? array_values($roundByKey[$key]['matches'])
        : [];
};
$r16Matches = array_slice($matchesFor('r16'), 0, 8);
$qfMatches = array_slice($matchesFor('qf'), 0, 4);
$sfMatches = array_slice($matchesFor('sf'), 0, 2);
$finalMatches = array_slice($matchesFor('final'), -1);
$r32Matches = $matchesFor('r32');

$championPathIds = [];
if (is_array($championPath)) {
    foreach ($championPath as $pathItem) {
        if (isset($pathItem['match_id'])) {
            $championPathIds[(int) $pathItem['match_id']] = true;
        }
    }
}

$championTeamId = null;
$finalMatch = $finalMatches !== [] ? $finalMatches[count($finalMatches) - 1] : null;
if (is_array($finalMatch)) {
    if (!empty($finalMatch['home']['winner'])) {
        $championTeamId = (int) $finalMatch['home']['team_id'];
    } elseif (!empty($finalMatch['away']['winner'])) {
        $championTeamId = (int) $finalMatch['away']['team_id'];
    }
}

$hasMainTree = $r16Matches !== [] || $qfMatches !== [] || $sfMatches !== [] || $finalMatches !== [];
$canTwoSided = count($r16Matches) === 8
    && count($qfMatches) === 4
    && count($sfMatches) === 2
    && count($finalMatches) === 1;

/**
 * 2026 版 32 强淘汰赛:按「决赛 → 半决赛 → 1/4 → 1/8 → 1/16」逐层反推,
 * 得到两个半区、四个 1/4 区。每区是一棵 4 场 1/16 → 2 场 1/8 → 1 场 1/4 的小树。
 * 数据不完整时返回 null,调用方回退到旧的 16 强树 + 折叠首轮。
 *
 * @return array|null
 */
$buildQuads = static function () use (
    $finalMatches,
    $sfMatches,
    $qfMatches,
    $r16Matches,
    $r32Matches,
    $feedersOf
) {
    $sfPair = $feedersOf($finalMatches[0], $sfMatches);
    if ($sfPair === null) {
        return null;
    }

    $halves = [];
    foreach ($sfPair as $sf) {
        $qfPair = $feedersOf($sf, $qfMatches);
        if ($qfPair === null) {
            return null;
        }

        $quads = [];
        foreach ($qfPair as $qf) {
            $r16Pair = $feedersOf($qf, $r16Matches);
            if ($r16Pair === null) {
                return null;
            }

            $r16Nodes = [];
            foreach ($r16Pair as $r16) {
                $r32Pair = $feedersOf($r16, $r32Matches);
                if ($r32Pair === null) {
                    return null;
                }
                $r16Nodes[] = ['match' => $r16, 'r32' => $r32Pair];
            }

            $quads[] = ['qf' => $qf, 'r16' => $r16Nodes];
        }

        $halves[] = ['sf' => $sf, 'quads' => $quads];
    }

    return count($halves) === 2 ? $halves : null;
};

$quadHalves = null;
if ($r32Matches !== [] && $canTwoSided) {
    $quadHalves = $buildQuads();
}
$hasQuads = $quadHalves !== null;
$finalOfQuads = $hasQuads ? $finalMatches[0] : null;

// 四区版式单独一份 CSS，避免和旧树 / 别人未提交的 bracket.css 改动互相覆盖。
if ($hasQuads) {
    $this->registerCssFile('@web/css/bracket-quads.css');
}

/**
 * 一张比赛卡。胜者固定在上行（无胜者时保留原始主客顺序）。
 *
 * @param array $match
 * @param string $level r16|qf|sf|final|r32|consolation
 * @return string
 */
$renderCard = static function (array $match, $level) use ($championPathIds, $championTeamId) {
    $matchId = isset($match['id']) ? (int) $match['id'] : 0;
    $home = isset($match['home']) && is_array($match['home']) ? $match['home'] : [];
    $away = isset($match['away']) && is_array($match['away']) ? $match['away'] : [];

    // 获胜者在上：世界杯淘汰赛主客只是签位，读者更关心谁晋级。
    if (!empty($away['winner']) && empty($home['winner'])) {
        $swapped = $home;
        $home = $away;
        $away = $swapped;
    }

    $homeName = isset($home['name']) && $home['name'] !== '' ? (string) $home['name'] : '待补';
    $awayName = isset($away['name']) && $away['name'] !== '' ? (string) $away['name'] : '待补';
    $homeScore = isset($home['score']) && $home['score'] !== null ? (int) $home['score'] : null;
    $awayScore = isset($away['score']) && $away['score'] !== null ? (int) $away['score'] : null;
    $homeWinner = !empty($home['winner']);
    $awayWinner = !empty($away['winner']);
    $isPath = $matchId > 0 && isset($championPathIds[$matchId]);

    $stageLabel = isset($match['stage']) ? (string) $match['stage'] : '';
    $ariaLabel = $stageLabel . '，' . $homeName . ' 对 ' . $awayName
        . '，比分 ' . ($homeScore !== null ? (string) $homeScore : '待补')
        . ' 比 ' . ($awayScore !== null ? (string) $awayScore : '待补');
    $hasPenalty = !empty($match['penalties'])
        && isset($home['penalty'], $away['penalty'])
        && $home['penalty'] !== null
        && $away['penalty'] !== null;
    if ($hasPenalty) {
        $ariaLabel .= '，点球大战 ' . (int) $home['penalty'] . ' 比 ' . (int) $away['penalty'];
    }
    if ($homeWinner) {
        $ariaLabel .= '，' . $homeName . ' 胜出';
    } elseif ($awayWinner) {
        $ariaLabel .= '，' . $awayName . ' 胜出';
    }
    $ariaLabel .= '。按回车查看比赛详情';

    $cardClasses = ['bt-card', 'bt-card--' . $level];
    if ($isPath) {
        $cardClasses[] = 'is-path';
    }

    $teamRow = static function ($team, $name, $score, $isWinner, $isChampion) {
        $classes = ['bt-team'];
        if ($isWinner) {
            $classes[] = 'is-winner';
        }
        if ($isChampion) {
            $classes[] = 'is-champion';
        }
        $code = isset($team['code']) ? (string) $team['code'] : '';

        return '<span class="' . Html::encode(implode(' ', $classes)) . '">'
            . TeamFlag::html($code, $name)
            . '<span class="bt-name" title="' . Html::encode($name) . '">' . Html::encode($name) . '</span>'
            . '<span class="bt-score' . ($score === null ? ' is-pending' : '') . '">'
            . ($score === null ? '–' : Html::encode((string) $score))
            . '</span>'
            . '</span>';
    };

    $homeIsChampion = $level === 'final'
        && $championTeamId !== null
        && isset($home['team_id'])
        && (int) $home['team_id'] === $championTeamId;
    $awayIsChampion = $level === 'final'
        && $championTeamId !== null
        && isset($away['team_id'])
        && (int) $away['team_id'] === $championTeamId;

    $url = $matchId > 0 ? Url::to(['/game/view', 'id' => $matchId]) : '#';

    return '<a class="' . Html::encode(implode(' ', $cardClasses)) . '"'
        . ' href="' . Html::encode($url) . '"'
        . ' aria-label="' . Html::encode($ariaLabel) . '">'
        . $teamRow($home, $homeName, $homeScore, $homeWinner, $homeIsChampion)
        . $teamRow($away, $awayName, $awayScore, $awayWinner, $awayIsChampion)
        . '</a>';
};

/**
 * 冠军路径连线：父场与某个子场都在冠军路径上时才亮金。
 *
 * @param array|null $parent
 * @param array|null $child
 * @return bool
 */
$isPathEdge = static function ($parent, $child) use ($championPathIds) {
    if (!is_array($parent) || !is_array($child)) {
        return false;
    }
    $parentId = isset($parent['id']) ? (int) $parent['id'] : 0;
    $childId = isset($child['id']) ? (int) $child['id'] : 0;

    return $parentId > 0 && $childId > 0
        && isset($championPathIds[$parentId])
        && isset($championPathIds[$childId]);
};

/**
 * 一条汇流线。坐标是最小公分母：x 从父场到子场，y 0–100。
 *
 * @param int $col 网格列
 * @param int $row 起始行
 * @param int $span 跨行
 * @param string $base 基础路径
 * @param string|null $gold 冠军路径
 * @return string
 */
$renderLink = static function ($col, $row, $span, $base, $gold = null) {
    return '<div class="bt-link" style="grid-column:' . (int) $col . ';grid-row:' . (int) $row
        . ' / span ' . (int) $span . '" aria-hidden="true">'
        . '<svg viewBox="0 0 100 100" preserveAspectRatio="none" focusable="false">'
        . '<path class="bt-link__base" d="' . Html::encode($base) . '" vector-effect="non-scaling-stroke"/>'
        . ($gold !== null
            ? '<path class="bt-link__gold" d="' . Html::encode($gold) . '" vector-effect="non-scaling-stroke"/>'
            : '')
        . '</svg></div>';
};

/**
 * @param int $col
 * @param int $row
 * @param int $span
 * @param array $match
 * @param string $level
 * @return string
 */
$renderNode = static function ($col, $row, $span, array $match, $level) use ($renderCard) {
    return '<div class="bt-node" style="grid-column:' . (int) $col . ';grid-row:' . (int) $row
        . ' / span ' . (int) $span . '">'
        . $renderCard($match, $level)
        . '</div>';
};

/**
 * 窄屏 / 数据不足时的纵向名单：按轮次分组，卡片仍是同一张。
 */
$renderStack = static function (array $roundsForStack, $showNav = false) use ($renderCard) {
    $labels = [
        'final' => '决赛',
        'sf' => '半决赛',
        'qf' => '1/4 决赛',
        'r16' => '1/8 决赛',
        'r32' => '32 强赛（1/16 决赛）',
    ];
    $shortLabels = [
        'final' => '决赛',
        'sf' => '半决赛',
        'qf' => '1/4',
        'r16' => '1/8',
        'r32' => '32 强',
    ];
    $html = '<div class="bt-stack">';

    // 32 强名单比往届长一倍，先给一排轮次锚点，别让人一路滚到 1/16。
    if ($showNav) {
        $nav = '';
        foreach ($labels as $key => $label) {
            if (empty($roundsForStack[$key])) {
                continue;
            }
            $nav .= '<a href="#bt-round-' . Html::encode($key) . '">'
                . Html::encode(isset($shortLabels[$key]) ? $shortLabels[$key] : $label) . '</a>';
        }
        if ($nav !== '') {
            $html .= '<nav class="bt-round-nav" aria-label="淘汰赛轮次">' . $nav . '</nav>';
        }
    }

    foreach ($labels as $key => $label) {
        $matches = isset($roundsForStack[$key]) ? $roundsForStack[$key] : [];
        if ($matches === []) {
            continue;
        }
        $html .= '<section class="bt-stack-round' . ($key === 'final' ? ' bt-stack-round--final' : '')
            . '" id="bt-round-' . Html::encode($key) . '">';
        $html .= '<h3 class="bt-stack-label">' . Html::encode($label) . '</h3>';
        $html .= '<div class="bt-stack-grid' . ($key === 'final' ? ' bt-stack-grid--final' : '') . '">';
        foreach ($matches as $match) {
            $html .= '<div class="bt-stack-slot">' . $renderCard($match, $key) . '</div>';
        }
        $html .= '</div></section>';
    }
    $html .= '</div>';

    return $html;
};
?>
<?php if (!$hasMainTree && $r32Matches === [] && $thirdPlaceMatch === null): ?>
    <div class="bracket-empty empty-state">
        <h3>淘汰赛数据待补</h3>
        <p>该届赛事的淘汰赛对阵会在数据补齐后显示。</p>
    </div>
<?php else: ?>
    <section class="bracket" aria-labelledby="bracket-title">
        <h2 class="sr-only" id="bracket-title"><?= $hasQuads ? '32 强淘汰赛对阵' : '淘汰赛对阵' ?></h2>

        <?php if ($hasQuads): ?>
            <?php
            // 四区版式：孩子的 x=0，父场的 x=100；与旧树共用同一组 SVG 路径。
            $pairLtr = 'M0,25 H50 V75 H0 M50,50 H100';
            $goldFirst = 'M0,25 H50 V50 H100';
            $goldSecond = 'M0,75 H50 V50 H100';
            ?>
            <div class="bt-quads">
                <div class="bt-finalfour" role="group" aria-label="半决赛与决赛">
                    <div class="bt-finalfour-cell">
                        <span class="bt-round-label">半决赛 1</span>
                        <div class="bt-finalfour-card"><?= $renderCard($quadHalves[0]['sf'], 'sf') ?></div>
                        <span class="bt-finalfour-note">1/4 区 1、2</span>
                    </div>
                    <div class="bt-finalfour-cell bt-finalfour-cell--final">
                        <span class="bt-round-label bt-round-label--final">决赛</span>
                        <div class="bt-finalfour-card"><?= $renderCard($finalOfQuads, 'final') ?></div>
                    </div>
                    <div class="bt-finalfour-cell">
                        <span class="bt-round-label">半决赛 2</span>
                        <div class="bt-finalfour-card"><?= $renderCard($quadHalves[1]['sf'], 'sf') ?></div>
                        <span class="bt-finalfour-note">1/4 区 3、4</span>
                    </div>
                </div>

                <div class="bt-quads-grid">
                    <?php foreach ($quadHalves as $halfIndex => $half): ?>
                        <?php foreach ($half['quads'] as $quadInHalf => $quad): ?>
                            <?php
                            $podNo = $halfIndex * 2 + $quadInHalf + 1;
                            $halfNo = $halfIndex + 1;
                            $qf = $quad['qf'];
                            $r16A = $quad['r16'][0]['match'];
                            $r16B = $quad['r16'][1]['match'];
                            $r32A = $quad['r16'][0]['r32'];
                            $r32B = $quad['r16'][1]['r32'];
                            $qfOnPath = $isPathEdge($half['sf'], $qf);
                            ?>
                            <section class="bt-quad" aria-label="1/4 区 <?= Html::encode((string) $podNo) ?>，胜者进入半决赛 <?= Html::encode((string) $halfNo) ?>">
                                <header class="bt-quad-head">
                                    <span class="bt-quad-title">1/4 区 <?= Html::encode((string) $podNo) ?></span>
                                    <span class="bt-quad-to<?= $qfOnPath ? ' is-path' : '' ?>">半决赛 <?= Html::encode((string) $halfNo) ?></span>
                                </header>
                                <div class="bt-quad-labels" aria-hidden="true">
                                    <span style="grid-column:1">32 强</span>
                                    <span style="grid-column:3">1/8</span>
                                    <span style="grid-column:5">1/4</span>
                                </div>
                                <div class="bt-quad-tree">
                                    <?php foreach ([[$r16A, $r32A], [$r16B, $r32B]] as $branchIndex => $branch): ?>
                                        <?php
                                        [$r16, $r32] = $branch;
                                        $row = 1 + $branchIndex * 2;
                                        ?>
                                        <?= $renderNode(1, $row, 1, $r32[0], 'r32') ?>
                                        <?= $renderNode(1, $row + 1, 1, $r32[1], 'r32') ?>
                                        <?= $renderLink(2, $row, 2, $pairLtr,
                                            $isPathEdge($r16, $r32[0]) ? $goldFirst
                                                : ($isPathEdge($r16, $r32[1]) ? $goldSecond : null)) ?>
                                        <?= $renderNode(3, $row, 2, $r16, 'r16') ?>
                                    <?php endforeach ?>
                                    <?= $renderLink(4, 1, 4, $pairLtr,
                                        $isPathEdge($qf, $r16A) ? $goldFirst
                                            : ($isPathEdge($qf, $r16B) ? $goldSecond : null)) ?>
                                    <?= $renderNode(5, 1, 4, $qf, 'qf') ?>
                                </div>
                            </section>
                        <?php endforeach ?>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="bt-mobile">
                <?= $renderStack([
                    'final' => [$finalOfQuads],
                    'sf' => [$quadHalves[0]['sf'], $quadHalves[1]['sf']],
                    'qf' => [
                        $quadHalves[0]['quads'][0]['qf'],
                        $quadHalves[0]['quads'][1]['qf'],
                        $quadHalves[1]['quads'][0]['qf'],
                        $quadHalves[1]['quads'][1]['qf'],
                    ],
                    'r16' => $r16Matches,
                    'r32' => $r32Matches,
                ], true) ?>
            </div>
        <?php elseif ($canTwoSided): ?>
            <?php
            $r16L = array_slice($r16Matches, 0, 4);
            $r16R = array_slice($r16Matches, 4, 4);
            $qfL = array_slice($qfMatches, 0, 2);
            $qfR = array_slice($qfMatches, 2, 2);
            $sfL = [$sfMatches[0]];
            $sfR = [$sfMatches[1]];
            $final = $finalMatches[0];

            // 左半区：孩子 x=0，父场 x=100。
            $pairLtr = 'M0,25 H50 V75 H0 M50,50 H100';
            $joinLtr = 'M0,25 H50 V50 H100';
            // 右半区：父场 x=0，孩子 x=100。
            $pairRtl = 'M100,25 H50 V75 H100 M50,50 H0';
            $joinRtl = 'M0,50 H50 V75 H100';

            $pairsL = [
                [$qfL[0], [$r16L[0], $r16L[1]]],
                [$qfL[1], [$r16L[2], $r16L[3]]],
            ];
            $pairsR = [
                [$qfR[0], [$r16R[0], $r16R[1]]],
                [$qfR[1], [$r16R[2], $r16R[3]]],
            ];
            ?>
            <div class="bt-wrap" role="group" aria-label="淘汰赛对阵：左侧上半区、右侧下半区，决赛在中央">
                <div class="bt-labels">
                    <span class="bt-label" style="grid-column:1">1/8 决赛</span>
                    <span class="bt-label" style="grid-column:3">1/4 决赛</span>
                    <span class="bt-label" style="grid-column:5">半决赛</span>
                    <span class="bt-label bt-label--final" style="grid-column:7">决赛</span>
                    <span class="bt-label" style="grid-column:9">半决赛</span>
                    <span class="bt-label" style="grid-column:11">1/4 决赛</span>
                    <span class="bt-label" style="grid-column:13">1/8 决赛</span>
                </div>
                <div class="bt-grid">
                    <?php // 左半区：每 4 行是一组「两场 1/8 → 一场 1/4」。 ?>
                    <?php foreach ($pairsL as $pairIndex => $pair): ?>
                        <?php
                        [$parent, $children] = $pair;
                        $row = 1 + $pairIndex * 4;
                        $gold = null;
                        if ($isPathEdge($parent, $children[0])) {
                            $gold = 'M0,25 H50 V50 H100';
                        } elseif ($isPathEdge($parent, $children[1])) {
                            $gold = 'M0,75 H50 V50 H100';
                        }
                        ?>
                        <?= $renderLink(2, $row, 4, $pairLtr, $gold) ?>
                    <?php endforeach ?>
                    <?php
                    $goldQfL = $isPathEdge($sfL[0], $qfL[0])
                        ? 'M0,25 H50 V50 H100'
                        : ($isPathEdge($sfL[0], $qfL[1]) ? 'M0,75 H50 V50 H100' : null);
                    $goldSfL = $isPathEdge($final, $sfL[0]) ? 'M0,50 H100' : null;
                    ?>
                    <?= $renderLink(4, 1, 8, $pairLtr, $goldQfL) ?>
                    <?= $renderLink(6, 1, 8, 'M0,50 H100', $goldSfL) ?>

                    <?php foreach ($pairsR as $pairIndex => $pair): ?>
                        <?php
                        [$parent, $children] = $pair;
                        $row = 1 + $pairIndex * 4;
                        $gold = null;
                        if ($isPathEdge($parent, $children[0])) {
                            $gold = 'M100,25 H50 V50 H0';
                        } elseif ($isPathEdge($parent, $children[1])) {
                            $gold = 'M100,75 H50 V50 H0';
                        }
                        ?>
                        <?= $renderLink(12, $row, 4, $pairRtl, $gold) ?>
                    <?php endforeach ?>
                    <?php
                    $goldQfR = $isPathEdge($sfR[0], $qfR[0])
                        ? 'M100,25 H50 V50 H0'
                        : ($isPathEdge($sfR[0], $qfR[1]) ? 'M100,75 H50 V50 H0' : null);
                    $goldSfR = $isPathEdge($final, $sfR[0]) ? 'M0,50 H100' : null;
                    ?>
                    <?= $renderLink(10, 1, 8, $pairRtl, $goldQfR) ?>
                    <?= $renderLink(8, 1, 8, 'M0,50 H100', $goldSfR) ?>

                    <?php foreach ($r16L as $index => $match): ?>
                        <?= $renderNode(1, 1 + $index * 2, 2, $match, 'r16') ?>
                    <?php endforeach ?>
                    <?php foreach ($qfL as $index => $match): ?>
                        <?= $renderNode(3, 1 + $index * 4, 4, $match, 'qf') ?>
                    <?php endforeach ?>
                    <?= $renderNode(5, 1, 8, $sfL[0], 'sf') ?>
                    <?= $renderNode(7, 1, 8, $final, 'final') ?>
                    <?= $renderNode(9, 1, 8, $sfR[0], 'sf') ?>
                    <?php foreach ($qfR as $index => $match): ?>
                        <?= $renderNode(11, 1 + $index * 4, 4, $match, 'qf') ?>
                    <?php endforeach ?>
                    <?php foreach ($r16R as $index => $match): ?>
                        <?= $renderNode(13, 1 + $index * 2, 2, $match, 'r16') ?>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="bt-mobile">
                <?= $renderStack([
                    'final' => $finalMatches,
                    'sf' => $sfMatches,
                    'qf' => $qfMatches,
                    'r16' => $r16Matches,
                ]) ?>
            </div>
        <?php elseif ($hasMainTree): ?>
            <?= $renderStack([
                'final' => $finalMatches,
                'sf' => $sfMatches,
                'qf' => $qfMatches,
                'r16' => $r16Matches,
            ]) ?>
        <?php endif ?>

        <?php if ($r32Matches !== [] && !$hasQuads): ?>
            <details class="bt-extra">
                <summary class="bt-extra__summary">
                    首轮 1/16 · <?= Html::encode((string) count($r32Matches)) ?> 场
                </summary>
                <div class="bt-extra-grid">
                    <?php foreach ($r32Matches as $match): ?>
                        <div class="bt-extra-slot"><?= $renderCard($match, 'r32') ?></div>
                    <?php endforeach ?>
                </div>
            </details>
        <?php endif ?>

        <?php if ($thirdPlaceMatch !== null): ?>
            <div class="bt-third">
                <h3 class="bt-third__title">三四名决赛</h3>
                <div class="bt-third__card"><?= $renderCard($thirdPlaceMatch, 'consolation') ?></div>
            </div>
        <?php endif ?>
    </section>
<?php endif ?>

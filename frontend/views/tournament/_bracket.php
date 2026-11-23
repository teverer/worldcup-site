<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 淘汰赛纵向晋级树
 *
 * 决赛在顶,半决赛 / 1/4 / 1/8 依次向下;1/8 最宽 8 列。
 * 2026 的 1/16 折叠在树下方,4 列紧凑列表。
 * 卡片只显示中文队名与比分,整卡可点,细节进入比赛详情页。
 *
 * @var yii\web\View $this
 * @var array $bracket 轮次数组:key/label/matches
 * @var array|null $thirdPlaceMatch 三四名决赛节点
 * @var array $championPath 冠军晋级路径
 */

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
// 主树只放 1/8 以后;1/16 单独折叠。
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

/**
 * 渲染一张比赛卡:两行中文队名 + 比分,胜者加粗;决赛冠军名字金色。
 * 不加队码、加时 / 点球徽章与详情行,整卡就是链接。
 */
$renderCard = static function (array $match, $level) use ($championPathIds, $championTeamId) {
    $matchId = isset($match['id']) ? (int) $match['id'] : 0;
    $home = isset($match['home']) && is_array($match['home']) ? $match['home'] : [];
    $away = isset($match['away']) && is_array($match['away']) ? $match['away'] : [];

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

    $matchClasses = ['tree-match', 'tree-match--' . $level];
    if ($isPath) {
        $matchClasses[] = 'is-path';
    }

    $teamRow = static function ($name, $score, $isWinner, $isChampion) {
        $classes = ['tree-team'];
        if ($isWinner) {
            $classes[] = 'is-winner';
        }
        if ($isChampion) {
            $classes[] = 'is-champion';
        }

        return '<span class="' . Html::encode(implode(' ', $classes)) . '">'
            . '<span class="tree-team-name" title="' . Html::encode($name) . '">' . Html::encode($name) . '</span>'
            . '<span class="tree-team-score' . ($score === null ? ' is-pending' : '') . '">'
            . ($score === null ? '待补' : Html::encode((string) $score))
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

    return '<a class="' . Html::encode(implode(' ', $matchClasses)) . '"'
        . ' href="' . Html::encode($url) . '"'
        . ' aria-label="' . Html::encode($ariaLabel) . '">'
        . $teamRow($homeName, $homeScore, $homeWinner, $homeIsChampion)
        . $teamRow($awayName, $awayScore, $awayWinner, $awayIsChampion)
        . '</a>';
};

/**
 * 渲染一轮:8 列网格里的卡片行,跨度由调用方给出。
 * 不画轮次标签,轮次靠卡片尺寸与连线表达,树占满舞台宽度。
 */
$renderRound = static function ($key, $level, $span, array $matches, $label = '') use ($renderCard) {
    if ($matches === []) {
        return '';
    }

    $html = '';
    if ($label !== '') {
        $html .= '<h3 class="tree-round-label">' . Html::encode($label) . '</h3>';
    }
    $html .= '<div class="tree-row tree-row--' . Html::encode((string) $key) . '">';
    foreach ($matches as $match) {
        $html .= '<div class="tree-slot" style="grid-column: span ' . (int) $span . '">';
        $html .= $renderCard($match, $level);
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
};

/**
 * 渲染一组汇流线:父场在上,两个子场在下。
 * 灰线是结构;冠军所在半段叠一条金线,形成阅读路径。
 */
$renderConnector = static function ($parent, $leftChild, $rightChild, $span) use ($championPathIds) {
    if (!is_array($parent) || !is_array($leftChild) || !is_array($rightChild)) {
        return '';
    }

    $parentId = isset($parent['id']) ? (int) $parent['id'] : 0;
    $leftId = isset($leftChild['id']) ? (int) $leftChild['id'] : 0;
    $rightId = isset($rightChild['id']) ? (int) $rightChild['id'] : 0;

    $path = null;
    if ($parentId > 0 && isset($championPathIds[$parentId])) {
        if ($leftId > 0 && isset($championPathIds[$leftId])) {
            $path = 'M50,0 V20 H25 V40';
        } elseif ($rightId > 0 && isset($championPathIds[$rightId])) {
            $path = 'M50,0 V20 H75 V40';
        }
    }

    return '<div class="tree-connector" style="grid-column: span ' . (int) $span . '" aria-hidden="true">'
        . '<svg viewBox="0 0 100 40" preserveAspectRatio="none" focusable="false">'
        . '<path class="tree-connector__base" d="M25,40 V20 H75 V40 M50,0 V20" vector-effect="non-scaling-stroke"/>'
        . ($path !== null
            ? '<path class="tree-connector__path" d="' . Html::encode($path) . '" vector-effect="non-scaling-stroke"/>'
            : '')
        . '</svg></div>';
};

/**
 * 渲染两轮之间的汇流线行。$span 是每个父场在 8 列网格里的跨度。
 */
$renderConnectors = static function (array $parents, array $children, $span) use ($renderConnector) {
    if ($parents === [] || count($children) < 2) {
        return '';
    }

    $html = '<div class="tree-connectors">';
    foreach ($parents as $index => $parent) {
        $left = isset($children[$index * 2]) ? $children[$index * 2] : null;
        $right = isset($children[$index * 2 + 1]) ? $children[$index * 2 + 1] : null;
        if ($left === null || $right === null) {
            continue;
        }
        $html .= $renderConnector($parent, $left, $right, $span);
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
        <h2 class="sr-only" id="bracket-title">淘汰赛对阵</h2>

        <?php if ($hasMainTree): ?>
            <div class="tree-scroll">
                <div class="tree" role="group" aria-label="淘汰赛对阵，决赛在顶，从 1/8 决赛向下阅读">
                    <?= $renderRound('final', 'final', 8, $finalMatches, '决赛') ?>
                    <?= $renderConnectors($finalMatches, $sfMatches, 8) ?>
                    <?= $renderRound('sf', 'sf', 4, $sfMatches, '半决赛') ?>
                    <?= $renderConnectors($sfMatches, $qfMatches, 4) ?>
                    <?= $renderRound('qf', 'qf', 2, $qfMatches, '1/4 决赛') ?>
                    <?= $renderConnectors($qfMatches, $r16Matches, 2) ?>
                    <?= $renderRound('r16', 'r16', 1, $r16Matches, '1/8 决赛') ?>
                </div>
            </div>
        <?php endif ?>

        <?php if ($r32Matches !== []): ?>
            <details class="tree-extra">
                <summary class="tree-extra__summary">
                    首轮 1/16 · <?= Html::encode((string) count($r32Matches)) ?> 场
                </summary>
                <div class="tree-extra-scroll">
                    <div class="tree-extra-grid">
                        <?php foreach ($r32Matches as $match): ?>
                            <div class="tree-extra-slot"><?= $renderCard($match, 'r32') ?></div>
                        <?php endforeach ?>
                    </div>
                </div>
            </details>
        <?php endif ?>

        <?php if ($thirdPlaceMatch !== null): ?>
            <div class="tree-consolation">
                <h3 class="tree-consolation__title">三四名决赛</h3>
                <div class="tree-consolation__card"><?= $renderCard($thirdPlaceMatch, 'consolation') ?></div>
            </div>
        <?php endif ?>
    </section>
<?php endif ?>

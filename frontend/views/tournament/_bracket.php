<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 淘汰赛晋级图
 *
 * 横向轮次列 + 紧凑比赛节点。默认只展示对阵、比分与胜者;
 * 悬停 / 聚焦由 bracket.js 把节点内隐藏的详情模板渲染为浮层,
 * 无脚本时仍可完整阅读比分与胜者。
 *
 * @var yii\web\View $this
 * @var array $bracket 轮次数组:key/label/matches
 * @var array|null $thirdPlaceMatch 三四名决赛节点
 * @var array $championPath 冠军晋级路径
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->registerCssFile('@web/css/bracket.css');
$this->registerJsFile('@web/js/bracket.js', ['position' => \yii\web\View::POS_END]);

$rounds = is_array($bracket) ? $bracket : [];

// 冠军晋级路径中的场次,用于节点与悬停卡片的轻量标记。
$championPathIds = [];
if (is_array($championPath)) {
    foreach ($championPath as $pathItem) {
        if (isset($pathItem['match_id'])) {
            $championPathIds[(int) $pathItem['match_id']] = true;
        }
    }
}

// 定位决赛轮次与决赛节点,用于冠军标记和三四名决赛的落位。
$finalRoundIndex = null;
foreach ($rounds as $roundIndex => $round) {
    if (isset($round['key']) && $round['key'] === 'final') {
        $finalRoundIndex = $roundIndex;
        break;
    }
}

$finalMatch = null;
if ($finalRoundIndex !== null && !empty($rounds[$finalRoundIndex]['matches'])) {
    $finalMatches = array_values($rounds[$finalRoundIndex]['matches']);
    $finalMatch = $finalMatches[count($finalMatches) - 1];
}

$championSide = null;
if (is_array($finalMatch)) {
    if (!empty($finalMatch['home']['winner'])) {
        $championSide = 'home';
    } elseif (!empty($finalMatch['away']['winner'])) {
        $championSide = 'away';
    }
}

// 冠军球队编号,用于给晋级路径上的连接线做金色强调。
$championTeamId = null;
if ($championSide !== null && isset($finalMatch[$championSide]['team_id'])) {
    $championTeamId = (int) $finalMatch[$championSide]['team_id'];
}

/**
 * 渲染单支球队行:代码徽章 + 队名 + 比分。
 * 仅做展示组合,不参与任何赛果判断。
 */
$renderTeam = function (array $team, $side, $isFinal, $championSide) {
    $name = isset($team['name']) && $team['name'] !== '' ? (string) $team['name'] : '待定';
    $code = isset($team['code']) && $team['code'] !== '' ? (string) $team['code'] : '—';
    $score = isset($team['score']) ? $team['score'] : null;
    $isWinner = !empty($team['winner']);
    $isChampion = $isFinal && $isWinner && $championSide === $side;

    $classes = ['bracket-team'];
    if ($isWinner) {
        $classes[] = 'bracket-team--winner';
    }
    if ($isChampion) {
        $classes[] = 'bracket-team--champion';
    }

    return '<span class="' . Html::encode(implode(' ', $classes)) . '">'
        . '<span class="bracket-team__code" aria-hidden="true" title="' . Html::encode($code) . '">'
        . Html::encode($code) . '</span>'
        . '<span class="bracket-team__name" title="' . Html::encode($name) . '">'
        . Html::encode($name) . '</span>'
        . '<span class="bracket-team__score">'
        . ($score === null ? '—' : Html::encode((string) $score)) . '</span>'
        . '</span>';
};

/**
 * 渲染一场淘汰赛节点。$isFinal 为 true 时对决赛胜者追加冠军标记。
 */
$renderMatch = function (array $match, $roundLabel, $isFinal) use ($renderTeam, $championPathIds, $championSide) {
    $matchId = isset($match['id']) ? (int) $match['id'] : 0;
    $home = isset($match['home']) && is_array($match['home']) ? $match['home'] : [];
    $away = isset($match['away']) && is_array($match['away']) ? $match['away'] : [];

    $homeName = isset($home['name']) && $home['name'] !== '' ? (string) $home['name'] : '待定';
    $awayName = isset($away['name']) && $away['name'] !== '' ? (string) $away['name'] : '待定';
    $homeScore = isset($home['score']) ? $home['score'] : null;
    $awayScore = isset($away['score']) ? $away['score'] : null;
    $homePenalty = isset($home['penalty']) ? $home['penalty'] : null;
    $awayPenalty = isset($away['penalty']) ? $away['penalty'] : null;

    $extraTime = !empty($match['extra_time']);
    $showPenalties = !empty($match['penalties'])
        && $homePenalty !== null
        && $awayPenalty !== null;
    $goals = isset($match['goals']) && is_array($match['goals']) ? $match['goals'] : [];
    $goalCount = isset($match['goal_count']) ? (int) $match['goal_count'] : 0;
    $commentCount = isset($match['comment_count']) ? (int) $match['comment_count'] : 0;
    $isPath = $matchId > 0 && isset($championPathIds[$matchId]);

    $winnerName = '';
    if (!empty($home['winner'])) {
        $winnerName = $homeName;
    } elseif (!empty($away['winner'])) {
        $winnerName = $awayName;
    }
    $isChampion = $isFinal && $winnerName !== '';

    // 供读屏使用的整场描述。
    $ariaLabel = $roundLabel . '，' . $homeName . ' 对 ' . $awayName;
    $ariaLabel .= $homeScore !== null && $awayScore !== null
        ? '，比分 ' . $homeScore . ' 比 ' . $awayScore
        : '，比分待定';
    if ($showPenalties) {
        $ariaLabel .= '，点球大战 ' . $homePenalty . ' 比 ' . $awayPenalty;
    }
    if ($extraTime) {
        $ariaLabel .= '，经过加时';
    }
    if ($winnerName !== '') {
        if ($isFinal) {
            $resultText = ' 夺得冠军';
        } elseif ($roundLabel === '三四名决赛') {
            $resultText = ' 获胜';
        } else {
            $resultText = ' 晋级';
        }
        $ariaLabel .= '，' . $winnerName . $resultText;
    }
    if ($isPath) {
        $ariaLabel .= '，属于冠军晋级之路';
    }
    $ariaLabel .= '。按回车键查看比赛详情';

    $matchClasses = ['bracket-match'];
    if ($isFinal) {
        $matchClasses[] = 'bracket-match--final';
    }
    if ($isChampion) {
        $matchClasses[] = 'bracket-match--champion';
    }
    if ($isPath) {
        $matchClasses[] = 'bracket-match--path';
    }

    $detailUrl = Url::to(['/game/view', 'id' => $matchId]);

    ob_start();
    ?>
    <article class="<?= Html::encode(implode(' ', $matchClasses)) ?>"
             data-bracket-match
             data-match-id="<?= Html::encode((string) $matchId) ?>">
        <a class="bracket-match__link"
           href="<?= Html::encode($detailUrl) ?>"
           aria-label="<?= Html::encode($ariaLabel) ?>">
            <?= $renderTeam($home, 'home', $isFinal, $championSide) ?>
            <?= $renderTeam($away, 'away', $isFinal, $championSide) ?>
        </a>
        <div class="bracket-match__foot">
            <span class="bracket-match__tags">
                <?php if ($extraTime): ?>
                    <span class="bracket-tag bracket-tag--time">加时</span>
                <?php endif ?>
                <?php if ($showPenalties): ?>
                    <span class="bracket-tag bracket-tag--pen"
                          aria-label="点球大战 <?= Html::encode((string) $homePenalty) ?> 比 <?= Html::encode((string) $awayPenalty) ?>">
                        (<?= Html::encode((string) $homePenalty) ?>-<?= Html::encode((string) $awayPenalty) ?>)
                    </span>
                <?php endif ?>
            </span>
            <button class="bracket-match__more"
                    type="button"
                    aria-expanded="false"
                    aria-controls="bracket-tooltip"
                    aria-label="查看<?= Html::encode($homeName) ?>对<?= Html::encode($awayName) ?>的比赛详情">
                <span class="bracket-match__more-icon" aria-hidden="true">i</span>
                <span class="bracket-match__more-text">详情</span>
            </button>
        </div>

        <div class="bracket-tip" hidden aria-hidden="true">
            <p class="bracket-tip__score">
                <span class="bracket-tip__team"><?= Html::encode($homeName) ?></span>
                <strong>
                    <?= $homeScore === null ? '—' : Html::encode((string) $homeScore) ?>
                    <span class="bracket-tip__sep">:</span>
                    <?= $awayScore === null ? '—' : Html::encode((string) $awayScore) ?>
                </strong>
                <span class="bracket-tip__team"><?= Html::encode($awayName) ?></span>
            </p>
            <p class="bracket-tip__meta">
                <span class="bracket-tip__stage"><?= Html::encode($roundLabel) ?></span>
                <?php if ($extraTime): ?>
                    <span class="bracket-tag bracket-tag--time">加时</span>
                <?php endif ?>
                <?php if ($showPenalties): ?>
                    <span class="bracket-tag bracket-tag--pen">点球 <?= Html::encode((string) $homePenalty) ?>-<?= Html::encode((string) $awayPenalty) ?></span>
                <?php endif ?>
                <?php if ($isPath): ?>
                    <span class="bracket-tag bracket-tag--path">冠军之路</span>
                <?php endif ?>
            </p>

            <?php if ($goals !== []): ?>
                <ul class="bracket-tip__goals">
                    <?php $shootoutStarted = false; ?>
                    <?php foreach ($goals as $goal): ?>
                        <?php
                        $goalPlayer = isset($goal['player']) && $goal['player'] !== ''
                            ? (string) $goal['player'] : '未知球员';
                        $goalTeam = isset($goal['team']) ? (string) $goal['team'] : '';
                        $goalMinute = isset($goal['minute']) ? $goal['minute'] : null;
                        $isOwnGoal = !empty($goal['is_own_goal']);
                        $isShootout = !empty($goal['is_shootout']);
                        $isPenalty = !empty($goal['is_penalty']);
                        $minuteLabel = $isShootout
                            ? 'PK'
                            : ($goalMinute !== null ? $goalMinute . "'" : '—');
                        ?>
                        <?php if ($isShootout && !$shootoutStarted): ?>
                            <?php $shootoutStarted = true; ?>
                            <li class="bracket-goal__caption">点球大战</li>
                        <?php endif ?>
                        <li class="bracket-goal<?= $isShootout ? ' bracket-goal--shootout' : '' ?>">
                            <span class="bracket-goal__minute"><?= Html::encode((string) $minuteLabel) ?></span>
                            <span class="bracket-goal__player">
                                <span class="bracket-goal__name"><?= Html::encode($goalPlayer) ?></span>
                                <?php if ($goalTeam !== ''): ?>
                                    <span class="bracket-goal__team"><?= Html::encode($goalTeam) ?></span>
                                <?php endif ?>
                            </span>
                            <span class="bracket-goal__tags">
                                <?php if ($isOwnGoal): ?>
                                    <span class="bracket-tag bracket-tag--own">乌龙</span>
                                <?php elseif ($isShootout): ?>
                                    <span class="bracket-tag bracket-tag--shootout">点球大战</span>
                                <?php elseif ($isPenalty): ?>
                                    <span class="bracket-tag bracket-tag--pen">点球</span>
                                <?php endif ?>
                            </span>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php else: ?>
                <p class="bracket-tip__empty">暂无进球记录</p>
            <?php endif ?>

            <p class="bracket-tip__stats">
                <span>总进球 <strong><?= Html::encode((string) $goalCount) ?></strong></span>
                <span>评论 <strong><?= Html::encode((string) $commentCount) ?></strong></span>
            </p>
        </div>
    </article>
    <?php
    return ob_get_clean();
};

/**
 * 判断一条比赛节点是否包含冠军球队;只用于连接线的金色强调。
 */
$matchHasChampion = function (array $match, $championTeamId) {
    if ($championTeamId === null) {
        return false;
    }
    foreach (['home', 'away'] as $side) {
        if (isset($match[$side]['team_id'])
            && (int) $match[$side]['team_id'] === $championTeamId) {
            return true;
        }
    }

    return false;
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
$roundCount = count($rounds);
for ($roundCursor = $roundCount - 2; $roundCursor >= 0; $roundCursor--) {
    $currentMatches = isset($rounds[$roundCursor]['matches'])
        && is_array($rounds[$roundCursor]['matches'])
        ? array_values($rounds[$roundCursor]['matches']) : [];
    $nextMatches = isset($rounds[$roundCursor + 1]['matches'])
        && is_array($rounds[$roundCursor + 1]['matches'])
        ? array_values($rounds[$roundCursor + 1]['matches']) : [];
    if ($currentMatches === [] || $nextMatches === []) {
        continue;
    }
    $orderedMatches = $orderMatchesByNextRound($currentMatches, $nextMatches);
    if ($orderedMatches !== null) {
        $rounds[$roundCursor]['matches'] = $orderedMatches;
    }
}
?>
<?php if ($rounds === [] && $thirdPlaceMatch === null): ?>
    <div class="bracket-empty empty-state">
        <h3>淘汰赛数据尚未录入</h3>
        <p>该届赛事的淘汰赛对阵会在数据补齐后显示。</p>
    </div>
<?php else: ?>
    <section class="bracket" data-bracket aria-labelledby="bracket-title">
        <header class="bracket__head">
            <div class="bracket__heading">
                <p class="bracket__eyebrow" aria-hidden="true">KNOCKOUT STAGE</p>
                <h2 class="bracket__title" id="bracket-title">淘汰赛晋级图</h2>
                <p class="bracket__note">悬停或聚焦比赛可查看进球与评论,点击进入比赛详情。</p>
            </div>
            <p class="bracket__hint" data-bracket-hint>
                <span class="bracket__hint-icon" aria-hidden="true">↔</span>
                <span>左右滑动查看</span>
            </p>
        </header>

        <div class="bracket__viewport"
             data-bracket-viewport
             tabindex="0"
             role="region"
             aria-label="淘汰赛晋级图,可左右滚动查看各轮次">
            <div class="bracket__track">
                <?php foreach ($rounds as $roundIndex => $round): ?>
                    <?php
                    $roundKey = isset($round['key']) ? (string) $round['key'] : 'round-' . $roundIndex;
                    $roundLabel = isset($round['label']) ? (string) $round['label'] : $roundKey;
                    $matches = isset($round['matches']) && is_array($round['matches'])
                        ? array_values($round['matches']) : [];
                    $isFinalRound = $roundIndex === $finalRoundIndex;
                    $roundClasses = ['bracket-round', 'bracket-round--' . $roundKey];
                    if ($isFinalRound) {
                        $roundClasses[] = 'bracket-round--final';
                    }

                    // 下一轮比赛用于两两汇流的连接线:第 2n-1、2n 场汇入下一轮第 n 场。
                    $nextRound = isset($rounds[$roundIndex + 1]) ? $rounds[$roundIndex + 1] : null;
                    $nextMatches = $nextRound !== null
                        && isset($nextRound['matches'])
                        && is_array($nextRound['matches'])
                        ? array_values($nextRound['matches']) : [];
                    $pairCount = min(
                        (int) floor(count($matches) / 2),
                        count($nextMatches)
                    );
                    $showLinks = !$isFinalRound && $pairCount > 0;
                    ?>
                    <section class="<?= Html::encode(implode(' ', $roundClasses)) ?>"
                             aria-labelledby="bracket-round-<?= Html::encode((string) $roundIndex) ?>">
                        <header class="bracket-round__head">
                            <h3 class="bracket-round__title"
                                id="bracket-round-<?= Html::encode((string) $roundIndex) ?>">
                                <?= Html::encode($roundLabel) ?>
                            </h3>
                            <span class="bracket-round__count"><?= Html::encode((string) count($matches)) ?> 场</span>
                        </header>
                        <div class="bracket-round__body">
                            <?php if ($showLinks): ?>
                                <div class="bracket-links" aria-hidden="true">
                                    <?php for ($pairIndex = 0; $pairIndex < $pairCount; $pairIndex++): ?>
                                        <?php
                                        $topMatch = $matches[$pairIndex * 2];
                                        $bottomMatch = $matches[$pairIndex * 2 + 1];
                                        $nextMatchId = isset($nextMatches[$pairIndex]['id'])
                                            ? (int) $nextMatches[$pairIndex]['id'] : 0;
                                        $pathSide = null;
                                        if ($nextMatchId > 0 && isset($championPathIds[$nextMatchId])) {
                                            if ($matchHasChampion($topMatch, $championTeamId)) {
                                                $pathSide = 'top';
                                            } elseif ($matchHasChampion($bottomMatch, $championTeamId)) {
                                                $pathSide = 'bottom';
                                            }
                                        }
                                        $linkClasses = ['bracket-link'];
                                        if ($pathSide !== null) {
                                            $linkClasses[] = 'bracket-link--path';
                                            $linkClasses[] = 'bracket-link--path-' . $pathSide;
                                        }
                                        ?>
                                        <span class="<?= Html::encode(implode(' ', $linkClasses)) ?>"
                                              data-pair="<?= Html::encode((string) $pairIndex) ?>"
                                              style="--pair: <?= Html::encode((string) $pairIndex) ?>; --matches: <?= Html::encode((string) count($matches)) ?>;"
                                              aria-hidden="true">
                                            <span class="bracket-link__arrow"></span>
                                        </span>
                                    <?php endfor ?>
                                </div>
                            <?php endif ?>
                            <?php foreach ($matches as $match): ?>
                                <?= $renderMatch($match, $roundLabel, $isFinalRound) ?>
                            <?php endforeach ?>
                        </div>
                    </section>
                <?php endforeach ?>
            </div>
        </div>

        <?php if ($thirdPlaceMatch !== null): ?>
            <section class="bracket-consolation" aria-labelledby="bracket-consolation-title">
                <header class="bracket-consolation__head">
                    <h3 class="bracket-consolation__title" id="bracket-consolation-title">三四名决赛</h3>
                    <span class="bracket-consolation__count">1 场</span>
                </header>
                <?= $renderMatch($thirdPlaceMatch, '三四名决赛', false) ?>
            </section>
        <?php endif ?>
    </section>
<?php endif ?>

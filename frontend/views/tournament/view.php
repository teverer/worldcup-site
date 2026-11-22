<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次详情页
 *
 * @var yii\web\View $this
 * @var common\models\Tournament $tournament
 * @var array|null $champion
 * @var array|null $runner_up
 * @var array|null $third_place
 * @var array|null $final
 * @var array $bracket
 * @var array|null $third_place_match
 * @var array $groups
 * @var array $stats
 * @var array $champion_path
 */
use common\components\CommentTarget;
use common\models\Tournament;
use frontend\widgets\CommentSection;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$model = $tournament;
$thirdPlaceMatch = $third_place_match;
$championPath = $champion_path;

$this->title = (string) $model->name;
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/tournament-detail.css');

$year = (int) $model->year;
$ordinal = 13 + (int) round(($year - 1986) / 4);
$host = $model->host_country !== null && $model->host_country !== ''
    ? (string) $model->host_country
    : '待定';

$championName = $champion !== null ? (string) $champion['name'] : null;
$championTeamId = $champion !== null ? (int) $champion['team_id'] : null;

/* 决赛在数据层按主客队保存,展示时统一按“冠军在前”排列。 */
$finalUrl = null;
$finalScoreText = null;
$finalOpponentName = null;
$finalPenaltyText = null;
$finalExtraTime = false;
if ($final !== null) {
    $finalUrl = Url::to(['/game/view', 'id' => (int) $final['id']]);
    $finalExtraTime = !empty($final['extra_time']);
    $hasPenalty = $final['home']['penalty'] !== null && $final['away']['penalty'] !== null;

    if ($championTeamId !== null) {
        $championIsHome = (int) $final['home']['team_id'] === $championTeamId;
        $championSide = $championIsHome ? $final['home'] : $final['away'];
        $opponentSide = $championIsHome ? $final['away'] : $final['home'];
        $finalOpponentName = (string) $opponentSide['name'];

        if ($championSide['score'] !== null && $opponentSide['score'] !== null) {
            $finalScoreText = (int) $championSide['score'] . '-' . (int) $opponentSide['score'];
        }
        if ($hasPenalty && $championSide['penalty'] !== null && $opponentSide['penalty'] !== null) {
            $finalPenaltyText = (int) $championSide['penalty'] . '-' . (int) $opponentSide['penalty'];
        }
    } else {
        // 冠军未定时仍展示决赛对阵,比分缺失用短横线占位。
        $homeScore = $final['home']['score'] !== null ? (string) (int) $final['home']['score'] : '—';
        $awayScore = $final['away']['score'] !== null ? (string) (int) $final['away']['score'] : '—';
        $finalScoreText = $homeScore . '-' . $awayScore;
        $finalOpponentName = (string) $final['away']['name'];
    }
}

$topScorer = $stats['top_scorer'];

/* 上一届 / 下一届:数据契约未包含,这里只取导航所需的最小字段。 */
$previous = Tournament::find()
    ->select(['id', 'year', 'name'])
    ->where(['<', 'year', $year])
    ->orderBy(['year' => SORT_DESC])
    ->asArray()
    ->one();
$next = Tournament::find()
    ->select(['id', 'year', 'name'])
    ->where(['>', 'year', $year])
    ->orderBy(['year' => SORT_ASC])
    ->asArray()
    ->one();

$bracketView = Yii::getAlias('@frontend/views/tournament/_bracket.php');
?>
<div class="tournament-detail">
    <header class="td-header" id="overview" tabindex="-1">
        <div class="td-header-top">
            <div class="td-header-title">
                <h1 class="td-header-name"><?= Html::encode((string) $model->name) ?></h1>
                <p class="td-header-sub">
                    第 <?= Html::encode((string) $ordinal) ?> 届
                    <span aria-hidden="true">·</span>
                    东道主 <?= Html::encode($host) ?>
                </p>
            </div>

            <div class="td-header-honor">
                <span class="td-champion-tag<?= $championName === null ? ' is-pending' : '' ?>">
                    <span class="td-champion-label">冠军</span>
                    <strong><?= Html::encode($championName !== null ? $championName : '待定') ?></strong>
                </span>

                <?php if ($finalUrl !== null): ?>
                    <a class="td-final-line" href="<?= Html::encode($finalUrl) ?>"
                       aria-label="<?= Html::encode('查看决赛详情:' . ($championName !== null ? $championName : '冠军待定') . ' ' . ($finalScoreText !== null ? $finalScoreText : '—') . ' ' . ($finalOpponentName !== null ? $finalOpponentName : '对手待定')) ?>">
                        <span class="td-final-label">决赛</span>
                        <strong class="td-final-score"><?= Html::encode($finalScoreText !== null ? $finalScoreText : '—') ?></strong>
                        <span class="td-final-opponent"><?= Html::encode($finalOpponentName !== null ? $finalOpponentName : '待定') ?></span>
                        <?php if ($finalPenaltyText !== null): ?>
                            <span class="td-final-badge">点球 <?= Html::encode($finalPenaltyText) ?></span>
                        <?php elseif ($finalExtraTime): ?>
                            <span class="td-final-badge">加时</span>
                        <?php endif ?>
                        <span class="td-final-arrow" aria-hidden="true">→</span>
                    </a>
                <?php else: ?>
                    <span class="td-final-line is-pending">决赛待定</span>
                <?php endif ?>
            </div>
        </div>

        <p class="td-header-facts">
            <span class="td-fact"><strong><?= Html::encode((string) $stats['team_count']) ?></strong> 队</span>
            <span class="td-fact-sep" aria-hidden="true">·</span>
            <span class="td-fact"><strong><?= Html::encode((string) $stats['match_count']) ?></strong> 场</span>
            <span class="td-fact-sep" aria-hidden="true">·</span>
            <span class="td-fact"><strong><?= Html::encode((string) $stats['goal_count']) ?></strong> 球</span>
            <span class="td-fact-sep" aria-hidden="true">·</span>
            <span class="td-fact">
                最佳射手
                <?php if ($topScorer !== null): ?>
                    <strong><?= Html::encode((string) $topScorer['name']) ?></strong>
                    <span class="td-fact-note"><?= Html::encode((string) $topScorer['goals']) ?> 球</span>
                <?php else: ?>
                    <strong>待定</strong>
                <?php endif ?>
            </span>
        </p>
    </header>

    <?php if ($championPath !== []): ?>
        <nav class="td-champion-path" aria-label="冠军晋级路径">
            <span class="td-champion-path-label">冠军之路</span>
            <div class="td-champion-path-track">
                <?php foreach ($championPath as $index => $step): ?>
                    <?php if ($index > 0): ?>
                        <span class="td-champion-path-arrow" aria-hidden="true">→</span>
                    <?php endif ?>
                    <?php
                    $stepTitle = $step['stage'] . ' ' . $step['score']
                        . ($step['penalty'] !== null ? ' · 点球 ' . $step['penalty'] : '');
                    $stepLabel = $stepTitle . ' ' . $step['opponent']['name'];
                    ?>
                    <a href="<?= Html::encode(Url::to(['/game/view', 'id' => (int) $step['match_id']])) ?>"
                       title="<?= Html::encode($stepTitle) ?>"
                       aria-label="<?= Html::encode($stepLabel) ?>"><?= Html::encode((string) $step['opponent']['name']) ?></a>
                <?php endforeach ?>
            </div>
        </nav>
    <?php endif ?>

    <nav class="td-nav" aria-label="页内导航">
        <a href="#overview" class="is-active">概览</a>
        <a href="#knockout">淘汰赛</a>
        <a href="#groups">小组赛</a>
        <a href="#charts">数据</a>
        <a href="#comments">讨论</a>
    </nav>

    <section class="td-knockout" id="knockout" tabindex="-1" aria-label="淘汰赛对阵">
        <?php if (is_file($bracketView)): ?>
            <?= $this->render('_bracket', [
                'bracket' => $bracket,
                'thirdPlaceMatch' => $thirdPlaceMatch,
                'championPath' => $championPath,
            ]) ?>
        <?php else: ?>
            <div class="empty-state td-bracket-placeholder">
                <h2>淘汰赛对阵</h2>
                <p>晋级图组件接入中,完整对阵将按轮次展示。</p>
            </div>
        <?php endif ?>
    </section>

    <?= $this->render('_groups', ['groups' => $groups]) ?>

    <?= $this->render('_stats', [
        'stats' => $stats,
        'third_place' => $third_place,
        'model' => $model,
    ]) ?>

    <section class="td-section td-comments" id="comments" tabindex="-1"
             aria-labelledby="td-comments-title">
        <header class="td-section-head">
            <h2 class="section-title" id="td-comments-title">讨论 <small>写下你记忆里的这一届</small></h2>
        </header>
        <?= CommentSection::widget([
            'targetType' => CommentTarget::TOURNAMENT,
            'targetId' => $model->id,
        ]) ?>
    </section>

    <?= $this->render('_history', [
        'previous' => $previous,
        'next' => $next,
    ]) ?>
</div>
<?php
$this->registerJs(<<<'JS'
(function () {
    var links = document.querySelectorAll('.td-nav a[href^="#"]');
    if (!links.length || !('IntersectionObserver' in window)) {
        return;
    }

    var byId = {};
    var sections = [];
    Array.prototype.forEach.call(links, function (link) {
        var id = link.getAttribute('href').slice(1);
        var section = document.getElementById(id);
        if (section) {
            byId[id] = link;
            sections.push(section);
        }
    });
    if (!sections.length) {
        return;
    }

    var activate = function (id) {
        Array.prototype.forEach.call(links, function (link) {
            var isActive = link.getAttribute('href') === '#' + id;
            link.classList.toggle('is-active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'true');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    // 首屏可能没有任何区块落在观察带内,先把默认高亮项的 aria 状态补齐。
    var initialLink = document.querySelector('.td-nav a.is-active');
    if (initialLink) {
        activate(initialLink.getAttribute('href').slice(1));
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting && byId[entry.target.id]) {
                activate(entry.target.id);
            }
        });
    }, {rootMargin: '-45% 0px -50% 0px', threshold: 0});

    sections.forEach(function (section) {
        observer.observe(section);
    });
})();
JS
, View::POS_END);
?>

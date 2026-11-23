<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:届次与比赛 —— 届次详情页
 *
 * 一个页面,一个舞台:紧凑记分牌头 → 淘汰赛 / 小组赛 / 统计三个本地 tab
 * (原生 #hash 切换,无 JS 时全部面板可见)→ 讨论单列 → 上一届 / 下一届。
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
 * @var int $comment_count
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
    : '待补';

// 届次名称已含年份,身份头把年份单独做成大数字,标题只留赛事名。
$editionName = preg_replace('/^\d{4}年/u', '', (string) $model->name);
if ($editionName === null || $editionName === '') {
    $editionName = (string) $model->name;
}

$formatDate = static function ($date) {
    if ($date === null || $date === '') {
        return '';
    }

    return str_replace('-', '.', substr((string) $date, 0, 10));
};
$startDate = $formatDate($model->start_date);
$endDate = $formatDate($model->end_date);
if ($startDate !== '' && $endDate !== '') {
    $dateRange = $startDate . '—' . $endDate;
} elseif ($startDate !== '') {
    $dateRange = $startDate . ' 开幕';
} elseif ($endDate !== '') {
    $dateRange = $endDate . ' 闭幕';
} else {
    $dateRange = '赛期待补';
}

$championName = $champion !== null ? (string) $champion['name'] : null;
$championTeamId = $champion !== null ? (int) $champion['team_id'] : null;

/* 决赛在数据层按主客队保存,展示时统一按“冠军在前”排列。 */
$finalUrl = null;
$finalScoreText = null;
$finalOpponentName = null;
$finalDecisiveText = null;
if ($final !== null) {
    $finalUrl = Url::to(['/game/view', 'id' => (int) $final['id']]);
    $hasPenalty = $final['home']['penalty'] !== null && $final['away']['penalty'] !== null;

    if ($championTeamId !== null) {
        $championIsHome = (int) $final['home']['team_id'] === $championTeamId;
        $championSide = $championIsHome ? $final['home'] : $final['away'];
        $opponentSide = $championIsHome ? $final['away'] : $final['home'];
        $finalOpponentName = (string) $opponentSide['name'];

        if ($championSide['score'] !== null && $opponentSide['score'] !== null) {
            $finalScoreText = (int) $championSide['score'] . '–' . (int) $opponentSide['score'];
        }
        if ($hasPenalty && $championSide['penalty'] !== null && $opponentSide['penalty'] !== null) {
            $finalDecisiveText = '点球 ' . (int) $championSide['penalty'] . '–' . (int) $opponentSide['penalty'];
        } elseif (!empty($final['extra_time'])) {
            $finalDecisiveText = '加时';
        }
    } else {
        // 冠军未定时仍展示决赛对阵,比分缺失用“待补”占位。
        $finalScoreText = $final['home']['score'] !== null
            ? (int) $final['home']['score'] . '–' . (int) $final['away']['score']
            : '待补';
        $finalOpponentName = (string) $final['away']['name'];
    }
}

$heroFacts = [
    ['label' => '参赛队', 'value' => (string) $stats['team_count']],
    ['label' => '比赛', 'value' => (string) $stats['match_count']],
    ['label' => '进球', 'value' => (string) $stats['goal_count']],
    ['label' => '场均进球', 'value' => number_format((float) $stats['avg_goals'], 2)],
];

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
    <header class="td-hero">
        <div class="td-hero-top">
            <a class="td-hero-back" href="<?= Html::encode(Url::to(['/site/index'])) ?>">← 首页</a>
            <p class="td-hero-top-meta">第 <?= Html::encode((string) $ordinal) ?> 届</p>
        </div>

        <div class="td-hero-main">
            <div class="td-hero-identity">
                <h1 class="td-hero-title">
                    <span class="td-hero-year"><?= Html::encode((string) $year) ?></span>
                    <span class="td-hero-name"><?= Html::encode($editionName) ?></span>
                </h1>
                <p class="td-hero-sub">
                    东道主 <?= Html::encode($host) ?>
                    <span aria-hidden="true">·</span>
                    <?= Html::encode($dateRange) ?>
                </p>
            </div>

            <div class="td-hero-honor">
                <p class="td-hero-champion<?= $championName === null ? ' is-pending' : '' ?>">
                    <span class="td-hero-honor-label">冠军</span>
                    <strong><?= Html::encode($championName !== null ? $championName : '待补') ?></strong>
                </p>

                <?php if ($finalUrl !== null): ?>
                    <a class="td-hero-final" href="<?= Html::encode($finalUrl) ?>"
                       aria-label="<?= Html::encode('查看决赛详情:' . ($championName !== null ? $championName : '冠军待补') . ' ' . ($finalScoreText !== null ? $finalScoreText : '—') . ' ' . ($finalOpponentName !== null ? $finalOpponentName : '对手待补')) ?>">
                        <span class="td-hero-final-label">决赛</span>
                        <strong class="td-hero-final-score"><?= Html::encode($finalScoreText !== null ? $finalScoreText : '待补') ?></strong>
                        <span class="td-hero-final-opp"><?= Html::encode($finalOpponentName !== null ? $finalOpponentName : '待补') ?></span>
                        <?php if ($finalDecisiveText !== null): ?>
                            <span class="td-hero-final-note"><?= Html::encode($finalDecisiveText) ?></span>
                        <?php endif ?>
                    </a>
                <?php else: ?>
                    <span class="td-hero-final is-pending">决赛待补</span>
                <?php endif ?>
            </div>
        </div>

        <dl class="td-hero-facts">
            <?php foreach ($heroFacts as $fact): ?>
                <div class="td-hero-fact">
                    <dt><?= Html::encode($fact['label']) ?></dt>
                    <dd><?= Html::encode($fact['value']) ?></dd>
                </div>
            <?php endforeach ?>
        </dl>
    </header>

    <nav class="td-tabs" aria-label="届次视图">
        <a href="#knockout" aria-current="page">淘汰赛</a>
        <a href="#groups">小组赛</a>
        <a href="#charts">统计</a>
        <a class="td-tabs-comments" href="#comments">
            讨论 <span><?= Html::encode((string) (int) $comment_count) ?></span>
        </a>
    </nav>

    <div class="td-switcher">
        <div class="td-stage">
            <section class="td-panel td-panel--knockout" id="knockout" tabindex="-1" aria-label="淘汰赛">
                <?php if (is_file($bracketView)): ?>
                    <?= $this->render('_bracket', [
                        'bracket' => $bracket,
                        'thirdPlaceMatch' => $thirdPlaceMatch,
                        'championPath' => $championPath,
                    ]) ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>淘汰赛对阵</h2>
                        <p>晋级图组件接入中,完整对阵将按轮次展示。</p>
                    </div>
                <?php endif ?>

                <p class="td-section-more">
                    <?= Html::a('查看本届全部比赛', [
                        '/game/index',
                        'tournamentId' => $model->id,
                    ]) ?>
                </p>
            </section>

            <?= $this->render('_groups', [
                'groups' => $groups,
                'model' => $model,
            ]) ?>

            <?= $this->render('_stats', [
                'model' => $model,
            ]) ?>
        </div>
    </div>

    <?= $this->render('_history', [
        'previous' => $previous,
        'next' => $next,
    ]) ?>

    <?php /* 看台:页尾的社区区,默认收起;顶部“讨论 N”会展开并跳到这里。 */ ?>
    <details class="td-stands" id="comments">
        <summary class="td-stands__bar">
            <span class="td-stands__name">看台</span>
            <span class="td-stands__note">这一届的讨论 · <?= Html::encode((string) (int) $comment_count) ?> 条</span>
            <span class="td-stands__toggle">
                <span class="td-stands__toggle-open">打开讨论</span>
                <span class="td-stands__toggle-close">收起</span>
                <span class="td-stands__chevron" aria-hidden="true"></span>
            </span>
        </summary>
        <div class="td-stands__body">
            <?= CommentSection::widget([
                'targetType' => CommentTarget::TOURNAMENT,
                'targetId' => $model->id,
            ]) ?>
        </div>
    </details>
</div>
<?php
$this->registerJs(<<<'JS'
(function () {
    var tabs = document.querySelectorAll('.td-tabs a[href^="#"]');
    if (!tabs.length) {
        return;
    }

    var syncTabs = function () {
        var hash = window.location.hash || '#knockout';
        Array.prototype.forEach.call(tabs, function (tab) {
            if (tab.getAttribute('href') === hash) {
                tab.setAttribute('aria-current', 'page');
            } else {
                tab.removeAttribute('aria-current');
            }
        });
    };

    // 统计面板默认隐藏,图表脚本等面板可见后再初始化。
    var notifyCharts = function () {
        if (window.location.hash !== '#charts') {
            return;
        }
        // 等 #charts 面板完成布局后再让图表初始化,避免按 0 尺寸绘制。
        window.setTimeout(function () {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    window.dispatchEvent(new Event('worldcup:charts-shown'));
                });
            });
        }, 80);
    };

    // 看台默认收起;顶部“讨论 N”跳转或直接带 #comments 打开时展开。
    var stands = document.getElementById('comments');
    var opensStandsFromNav = function () {
        if (stands && stands.tagName.toLowerCase() === 'details') {
            stands.open = true;
        }
    };
    var standsTab = document.querySelector('.td-tabs-comments');
    if (standsTab && stands) {
        // 不改 hash:讨论不属于舞台面板,跳过去不应把小组赛 / 统计重置回淘汰赛。
        standsTab.addEventListener('click', function (event) {
            event.preventDefault();
            opensStandsFromNav();
            stands.scrollIntoView();
        });
    }

    // 从外部带 #comments 进来时,收起后清掉 hash,避免留下“讨论”高亮。
    if (stands && stands.tagName.toLowerCase() === 'details') {
        stands.addEventListener('toggle', function () {
            if (!stands.open && window.location.hash === '#comments') {
                window.history.replaceState(null, '', window.location.pathname + window.location.search);
                syncTabs();
            }
        });
    }

    var syncStands = function () {
        if (window.location.hash === '#comments') {
            opensStandsFromNav();
        }
    };

    window.addEventListener('hashchange', function () {
        syncTabs();
        notifyCharts();
        syncStands();
    });

    syncTabs();
    notifyCharts();
    syncStands();
})();
JS
, View::POS_END);
?>

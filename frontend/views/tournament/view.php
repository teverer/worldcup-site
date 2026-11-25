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
 * @var array $history 跨届三块：四强 / 决赛常客 / 生涯射手
 * @var array $currentGoals 本届球员进球数 player_id => goals
 */
use common\components\CommentTarget;
use common\models\Tournament;
use frontend\models\EditionImage;
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
$heroImage = EditionImage::forYear($year);
if ($heroImage !== null) {
    $this->registerLinkTag(['rel' => 'preload', 'as' => 'image', 'href' => $heroImage['hero_url']]);
}
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
    // 同一年只写一次年份：2026.06.11—07.19，窄屏也能落在一行。
    $sameYear = substr($startDate, 0, 4) === substr($endDate, 0, 4);
    $dateRange = $startDate . '—' . ($sameYear ? substr($endDate, 5) : $endDate);
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
<?php
// 四格数据是可选层：设 false 即整块撤掉，大图与 tab 直接相接，不留空。
$showHeroFacts = true;
?>
<div class="tournament-detail">
    <header class="td-hero"<?php if ($heroImage !== null): ?> data-hero-image style="--td-hero-focus: <?= Html::encode($heroImage['focus']) ?>;"<?php endif ?>>
        <?php if ($heroImage !== null): ?>
            <div class="td-hero-media">
                <img class="td-hero-img"
                     src="<?= Html::encode($heroImage['hero_url']) ?>"
                     alt="<?= Html::encode($heroImage['alt']) ?>"
                     width="1920" height="800"
                     fetchpriority="high" decoding="async">
                <span class="td-hero-scrim" aria-hidden="true"></span>
            </div>
        <?php endif ?>
        <div class="td-hero-inner">
            <p class="td-hero-kicker">
                东道主 <?= Html::encode($host) ?>
                <span aria-hidden="true">·</span>
                <?= Html::encode($dateRange) ?>
            </p>
            <h1 class="td-hero-title">
                <span class="td-hero-year"><?= Html::encode((string) $year) ?></span>
                <span class="td-hero-name"><?= Html::encode($editionName) ?></span>
            </h1>
            <p class="td-hero-honor">
                <span class="td-hero-honor-label">冠军</span>
                <strong class="td-hero-champion<?= $championName === null ? ' is-pending' : '' ?>"><?= Html::encode($championName !== null ? $championName : '待补') ?></strong>
                <?php if ($finalUrl !== null): ?>
                    <a class="td-hero-final" href="<?= Html::encode($finalUrl) ?>"
                       aria-label="<?= Html::encode('查看决赛详情:' . ($championName !== null ? $championName : '冠军待补') . ' ' . ($finalScoreText !== null ? $finalScoreText : '—') . ' ' . ($finalOpponentName !== null ? $finalOpponentName : '对手待补')) ?>">
                        <span class="td-hero-final-label">决赛</span>
                        <span class="td-hero-final-score"><?= Html::encode($finalScoreText !== null ? $finalScoreText : '待补') ?></span>
                        <span class="td-hero-final-opp"><?= Html::encode($finalOpponentName !== null ? $finalOpponentName : '待补') ?></span>
                        <?php if ($finalDecisiveText !== null): ?>
                            <span class="td-hero-final-note"><?= Html::encode($finalDecisiveText) ?></span>
                        <?php endif ?>
                    </a>
                <?php else: ?>
                    <span class="td-hero-final is-pending">决赛待补</span>
                <?php endif ?>
            </p>

        </div>
        <?php if ($heroImage !== null): ?>
            <?php // 水印跟随图片本身的右上角，而不是 1320px 内容列的右上角；
                  // 放在 .td-hero-inner 之外，避免被正文层挡住点击。 ?>
            <p class="td-hero-credit">
                <a href="<?= Html::encode($heroImage['credit_url']) ?>" target="_blank" rel="noopener noreferrer">图：<?= Html::encode($heroImage['credit']) ?></a>
            </p>
        <?php endif ?>
    </header>

    <?php if ($showHeroFacts): ?>
        <div class="td-facts" aria-label="本届数据">
            <dl class="td-facts-inner">
                <?php foreach ($heroFacts as $fact): ?>
                    <div class="td-fact">
                        <dt><?= Html::encode($fact['label']) ?></dt>
                        <dd><?= Html::encode($fact['value']) ?></dd>
                    </div>
                <?php endforeach ?>
            </dl>
        </div>
    <?php endif ?>

    <nav class="td-tabs" aria-label="届次视图">
        <a href="#knockout" aria-current="true">淘汰赛</a>
        <a href="#groups">小组赛</a>
        <a href="#charts">统计</a>
        <a class="td-tabs-comments" href="#comments">
            讨论<?php if ((int) $comment_count > 0): ?> <span><?= Html::encode((string) (int) $comment_count) ?></span><?php endif ?>
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
                'history' => $history,
                'currentGoals' => $currentGoals,
                'champion' => $champion,
                'runnerUp' => $runner_up,
                'stats' => $stats,
            ]) ?>
        </div>
    </div>

    <?= $this->render('_history', [
        'previous' => $previous,
        'next' => $next,
    ]) ?>

    <?php /* 留言簿:页尾的社区记录;顶部“讨论 N”直接锚到这里。 */ ?>
    <?= CommentSection::widget([
        'targetType' => CommentTarget::TOURNAMENT,
        'targetId' => $model->id,
        'emptyTitle' => '目前还没有人留言',
    ]) ?>
</div>
<?php
$this->registerJs(<<<'JS'
(function () {
    var tabs = document.querySelectorAll('.td-tabs a[href^="#"]');
    if (!tabs.length) {
        return;
    }

    var detail = document.querySelector('.tournament-detail');
    var stageFromHash = function (hash) {
        if (hash === '#groups') {
            return 'groups';
        }
        if (hash === '#charts' || hash === '#history' || hash.indexOf('#hist-') === 0) {
            return 'charts';
        }
        if (hash === '#knockout') {
            return 'knockout';
        }
        return null;
    };
    var setStage = function (stage) {
        if (detail && stage) {
            detail.setAttribute('data-stage', stage);
        }
    };

    var syncTabs = function () {
        var rawHash = window.location.hash;
        var hashStage = stageFromHash(rawHash);
        // 新 hash 是舞台就记下来；#comments 不改，保留上一次的舞台。
        if (hashStage) {
            setStage(hashStage);
        }
        if (detail && !detail.getAttribute('data-stage')) {
            setStage('knockout');
        }
        var currentStage = detail && detail.getAttribute('data-stage')
            ? detail.getAttribute('data-stage') : 'knockout';
        var hash = rawHash;
        // 讨论不属于舞台面板:停在留言簿时,舞台仍是最后看的那一栏。
        if (!hash || hash === '#comments' || hash.indexOf('#comment-') === 0) {
            hash = '#' + currentStage;
        }
        // 历史坐标是「统计」里的子块:视觉上高亮统计,读屏也要跟着高亮。
        if (hash === '#history' || hash.indexOf('#hist-') === 0) {
            hash = '#charts';
        }
        Array.prototype.forEach.call(tabs, function (tab) {
            if (tab.getAttribute('href') === hash) {
                // 页内舞台不是“当前页”；全站导航的「届次」才是 page。
                tab.setAttribute('aria-current', 'true');
            } else {
                tab.removeAttribute('aria-current');
            }
        });
        // “讨论”是页内落点,不是舞台 tab;用 location 而不是 page。
        var commentTab = document.querySelector('.td-tabs-comments');
        if (commentTab) {
            if (rawHash === '#comments' || rawHash.indexOf('#comment-') === 0) {
                commentTab.setAttribute('aria-current', 'location');
            } else {
                commentTab.removeAttribute('aria-current');
            }
        }
        // 历史坐标的“跳到”子导航也标出当前段。
        var historyLinks = document.querySelectorAll('.stats-history-nav a[href^="#"]');
        Array.prototype.forEach.call(historyLinks, function (link) {
            if (link.getAttribute('href') === rawHash) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    // 点击舞台 tab 时先记住舞台，再让 hash 切面板；点“讨论”则保留原舞台。
    Array.prototype.forEach.call(tabs, function (tab) {
        tab.addEventListener('click', function () {
            var stage = stageFromHash(tab.getAttribute('href') || '');
            if (stage) {
                setStage(stage);
            }
        });
    });

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

    // “讨论 N”是普通锚点:滚到页尾留言簿,不改舞台 tab 的当前态。
    window.addEventListener('hashchange', function () {
        syncTabs();
        notifyCharts();
    });

    syncTabs();
    notifyCharts();
})();
JS
, View::POS_END);
?>

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * Coding by 李行阳 2411353, 20261123
 * 模块:框架与团队主页 —— 关于本站（范围与口径、团队、课程文档下载）。
 *
 * 一个页面回答三件事：这里收录什么、谁做的、文档在哪下载。
 * 原「团队展示」「作业下载」两页合并到此，下载区用 #docs 直达。
 *
 * @var yii\web\View $this
 * @var common\models\TeamInfo $team
 * @var common\models\Member[] $members
 * @var common\models\Work[] $teamWorks
 * @var common\models\Work[] $personalWorks
 * @var array $overview
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '关于本站';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/team-info.css');

$hasWorks = $teamWorks !== [] || $personalWorks !== [];
$sourceDocUrl = 'https://github.com/teverer/worldcup-site/blob/dev/docs/数据来源说明.md';

$renderWorks = static function (array $items) {
    if ($items === []) {
        return '<p class="about-doc-empty">暂无文件。</p>';
    }

    $html = '<ul class="about-docs">';
    foreach ($items as $work) {
        $size = $work->fileSize();
        $date = $work->create_time !== null && $work->create_time !== ''
            ? substr((string) $work->create_time, 0, 10)
            : null;
        $meta = array_filter([$date, $size !== null ? Yii::$app->formatter->asShortSize($size, 1) : null]);

        $html .= '<li><a class="about-doc" href="' . Html::encode(Url::to(['download', 'id' => $work->id])) . '">';
        $html .= '<span class="about-doc-main">';
        $html .= '<span class="about-doc-title">' . Html::encode($work->title) . '</span>';
        if ($work->description !== null && $work->description !== '') {
            $html .= '<span class="about-doc-desc">' . Html::encode($work->description) . '</span>';
        }
        $html .= '</span>';
        if ($meta !== []) {
            $html .= '<span class="about-doc-meta">' . Html::encode(implode(' · ', $meta)) . '</span>';
        }
        $html .= '<span class="about-doc-action">下载</span>';
        $html .= '</a></li>';
    }

    return $html . '</ul>';
};
?>
<div class="about-page">
    <header class="about-hero">
        <div class="about-hero-main">
            <p class="about-context">课程设计 · 数据库与 Web</p>
            <h1>关于本站</h1>
            <p class="about-lead"><?= Html::encode($team->slogan ?: '用数据看懂世界杯') ?></p>
            <?php if ($hasWorks): ?>
                <div class="about-actions">
                    <a class="btn btn-primary" href="#docs">下载课程文档</a>
                    <a class="btn btn-default" href="<?= Html::encode(Url::to(['/site/index'])) ?>">回到首页</a>
                </div>
            <?php endif ?>
            <?php // 空文档状态不再放「回到首页」：面包屑已经承担了返回，首屏只留一个主张。 ?>
        </div>
        <?php if ($team->logo): ?>
            <?= Html::img('@web/' . $team->logo, [
                'class' => 'about-logo',
                'alt' => $team->name . ' Logo',
            ]) ?>
        <?php else: ?>
            <div class="about-logo about-logo--fallback" aria-hidden="true">WC</div>
        <?php endif ?>
    </header>

    <section class="about-section" id="scope" aria-labelledby="about-scope-title">
        <div class="about-section-head">
            <h2 id="about-scope-title">收录范围</h2>
            <p>1986—2026 年十一届世界杯，全部来自公开数据；缺失处标「待补」，不补造。</p>
        </div>

        <dl class="about-facts">
            <div><dt>届次</dt><dd><?= (int) $overview['tournaments'] ?></dd></div>
            <div><dt>球队</dt><dd><?= (int) $overview['teams'] ?></dd></div>
            <div><dt>比赛</dt><dd><?= (int) $overview['games'] ?></dd></div>
            <div><dt>进球</dt><dd><?= (int) $overview['goals'] ?></dd></div>
        </dl>

        <ul class="about-notes">
            <li><strong>积分榜</strong><span>只统计小组赛且已结束的比赛；胜场积分按届次读取（1986 / 1990 为 2 分，之后为 3 分）。</span></li>
            <li><strong>射手榜</strong><span>排除乌龙球与点球大战进球。</span></li>
            <li><strong>进球归属</strong><span>按受益球队统计，乌龙球算给对方。</span></li>
        </ul>

        <p class="about-source">
            数据来源：openfootball 与 international_results（CC0），逐场交叉校验。
            <?= Html::a('查看数据来源说明', $sourceDocUrl, [
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]) ?>
        </p>
    </section>

    <section class="about-section" id="team" aria-labelledby="about-team-title">
        <div class="about-section-head">
            <h2 id="about-team-title">团队</h2>
            <p><?= Html::encode($team->name ?: '国足世界杯夺冠队') ?> · <?= count($members) ?> 人</p>
        </div>

        <?php if ($team->intro): ?>
            <p class="about-intro"><?= nl2br(Html::encode($team->intro)) ?></p>
        <?php endif ?>

        <?php if (!$members): ?>
            <div class="about-empty">
                <p>成员资料正在整理。</p>
            </div>
        <?php else: ?>
            <ul class="about-members">
                <?php foreach ($members as $member): ?>
                    <li class="about-member">
                        <?php if ($member->avatar): ?>
                            <?= Html::img('@web/' . $member->avatar, [
                                'class' => 'about-member-avatar',
                                'alt' => $member->name . '照片',
                            ]) ?>
                        <?php else: ?>
                            <span class="about-member-avatar" aria-hidden="true"><?= Html::encode(mb_substr($member->name, 0, 1, 'UTF-8')) ?></span>
                        <?php endif ?>
                        <span class="about-member-main">
                            <span class="about-member-name">
                                <?= Html::encode($member->name) ?>
                                <span class="about-member-role"><?= Html::encode($member->role ?: '组员') ?></span>
                            </span>
                            <span class="about-member-meta">
                                <?= Html::encode($member->student_id ?: '学号待补充') ?>
                                <?php if ($member->duty && $member->duty !== '待确认'): ?>
                                    <span aria-hidden="true">·</span> <?= Html::encode($member->duty) ?>
                                <?php endif ?>
                            </span>
                            <?php if ($member->intro): ?>
                                <span class="about-member-intro"><?= Html::encode($member->intro) ?></span>
                            <?php endif ?>
                        </span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <section class="about-section" id="docs" aria-labelledby="about-docs-title">
        <div class="about-section-head">
            <h2 id="about-docs-title">课程文档与作业</h2>
            <p>课程要求的六份文档，以及团队与个人作业；文件经控制器校验后下载。</p>
        </div>

        <?php if (!$hasWorks): ?>
            <p class="about-docs-note">
                六份课程文档仍在整理：需求、设计、实现、用户手册、部署、展示 PPT。
                底稿在
                <?= Html::a('GitHub 仓库 docs/ 目录', 'https://github.com/teverer/worldcup-site/tree/dev/docs', [
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer',
                ]) ?>，最终 PDF 会在这里提供下载。
            </p>
        <?php else: ?>
            <div class="about-doc-group">
                <h3>团队作业</h3>
                <?= $renderWorks($teamWorks) ?>
            </div>
            <div class="about-doc-group">
                <h3>个人作业</h3>
                <?= $renderWorks($personalWorks) ?>
            </div>
        <?php endif ?>
    </section>
</div>

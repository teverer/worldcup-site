<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 关于本站
 */

use yii\helpers\Html;

$this->title = '关于本站';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <header class="page-header">
        <h1>关于本站</h1>
        <p>世界杯数据门户——把 1986 到 2026 的届次、球队、球员、比赛与进球放进同一套档案，用数据回看绿茵经典。</p>
    </header>

    <section class="section">
        <h2 class="section-title">本站做什么</h2>
        <div class="grid">
            <article class="card span-4">
                <div class="card-body">
                    <h3>届次档案</h3>
                    <p>按年代回看十一届世界杯的东道主、赛制、决赛与冠军之路。</p>
                </div>
            </article>
            <article class="card span-4">
                <div class="card-body">
                    <h3>比赛与进球</h3>
                    <p>逐场比分、进球时间线、点球大战与乌龙球，都按统一口径归档。</p>
                </div>
            </article>
            <article class="card span-4">
                <div class="card-body">
                    <h3>社区讨论</h3>
                    <p>登录后可以留言、回复和点赞，把记忆里的比赛写进对应页面。</p>
                </div>
            </article>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">数据范围与口径</h2>
        <p>当前收录 1986–2026 年共十一届世界杯，覆盖届次、球队、球员、比赛与进球数据。积分榜只统计已结束的小组赛；射手榜排除乌龙球与点球大战进球；各队进球按进球归属球队统计。</p>
        <p>数据来源与整理过程见 <?= Html::a('数据来源说明', 'https://github.com/teverer/worldcup-site/blob/dev/docs/数据来源说明.md', [
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]) ?>。</p>
    </section>

    <section class="section">
        <h2 class="section-title">技术与分工</h2>
        <div class="grid">
            <article class="card span-6">
                <div class="card-body">
                    <h3>技术栈</h3>
                    <p>PHP 8.2 + MySQL 8.0 + Yii2 advanced 2.0.32；Bootstrap 3 布局，ECharts 本地引入，不依赖 CDN。</p>
                </div>
            </article>
            <article class="card span-6">
                <div class="card-body">
                    <h3>团队</h3>
                    <p>国足世界杯夺冠队，4 人课程设计。分工覆盖届次与比赛、球队与球员、留言与资讯、团队主页四个模块。</p>
                </div>
            </article>
        </div>
    </section>

    <section class="section">
        <h2 class="section-title">文档与源码</h2>
        <div class="stack">
            <p><?= Html::a('项目文档', 'https://github.com/teverer/worldcup-site/tree/dev/docs', [
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]) ?>：需求、设计、实现、用户手册、部署与项目展示材料。</p>
            <p><?= Html::a('源码仓库', 'https://github.com/teverer/worldcup-site', [
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]) ?>：Yii2 前后台代码、建库脚本与数据文件。</p>
        </div>
    </section>
</div>

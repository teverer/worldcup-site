<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队及成员展示页。
 *
 * @var yii\web\View $this
 * @var common\models\TeamInfo $team
 * @var common\models\Member[] $members
 */

use yii\helpers\Html;

$this->title = '团队展示';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/team-info.css');
?>
<div class="team-page">
    <section class="team-hero">
        <div>
            <p class="team-kicker">WORLD CUP DATA TEAM</p>
            <h1 class="team-title"><?= Html::encode($team->name ?: '国足世界杯夺冠队') ?></h1>
            <p class="team-slogan"><?= Html::encode($team->slogan ?: '用数据看懂世界杯') ?></p>
        </div>
        <?php if ($team->logo): ?>
            <?= Html::img('@web/' . $team->logo, [
                'class' => 'team-logo',
                'alt' => $team->name . ' Logo',
            ]) ?>
        <?php else: ?>
            <div class="team-logo-placeholder" aria-label="团队 Logo 占位">WC</div>
        <?php endif ?>
    </section>

    <section class="team-intro">
        <div class="section-heading">
            <h2 class="section-title">关于我们</h2>
            <p class="section-subtitle">以数据库和 Web 技术整理并呈现世界杯数据。</p>
        </div>
        <p><?= $team->intro ? nl2br(Html::encode($team->intro)) : '暂无团队介绍。' ?></p>
        <div class="team-actions">
            <?= Html::a('下载作业文件', ['downloads'], ['class' => 'btn btn-accent']) ?>
            <?= Html::a('查看最新资讯', ['/news/index'], ['class' => 'btn btn-default']) ?>
        </div>
    </section>

    <section>
        <div class="section-heading section-heading-inline">
            <div>
                <h2 class="section-title">团队成员</h2>
                <p class="section-subtitle">分工协作，共同完成数据门户的设计与实现。</p>
            </div>
            <span class="badge badge-primary"><?= count($members) ?> 人</span>
        </div>

        <?php if (!$members): ?>
            <div class="empty-state">
                <h3>暂无成员资料</h3>
                <p>管理员登录后台后可以添加团队成员。</p>
            </div>
        <?php else: ?>
            <div class="member-grid">
                <?php foreach ($members as $member): ?>
                    <article class="card member-card">
                        <div class="card-body">
                            <div class="member-profile">
                                <?php if ($member->avatar): ?>
                                    <?= Html::img('@web/' . $member->avatar, [
                                        'class' => 'member-avatar',
                                        'alt' => $member->name . '照片',
                                    ]) ?>
                                <?php else: ?>
                                    <div class="member-avatar-placeholder" aria-hidden="true">
                                        <?= Html::encode(mb_substr($member->name, 0, 1, 'UTF-8')) ?>
                                    </div>
                                <?php endif ?>
                                <div>
                                    <h3 class="member-name"><?= Html::encode($member->name) ?></h3>
                                    <p class="member-meta">
                                        <?= Html::encode(trim(($member->role ?: '组员') . ' · ' . ($member->student_id ?: '学号待补充'))) ?>
                                    </p>
                                </div>
                            </div>
                            <p class="member-duty"><?= Html::encode($member->duty ?: '分工待补充') ?></p>
                            <p class="member-intro"><?= $member->intro
                                ? nl2br(Html::encode($member->intro))
                                : '暂无个人简介。' ?></p>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</div>

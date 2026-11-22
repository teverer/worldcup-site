<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队及个人作业下载页。
 *
 * @var yii\web\View $this
 * @var common\models\Work[] $teamWorks
 * @var common\models\Work[] $personalWorks
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '作业下载';
$this->params['breadcrumbs'][] = ['label' => '团队展示', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/team-info.css');

$renderWorks = function ($items, $emptyText) {
    if (!$items) {
        return '<div class="empty-state"><h3>暂无可下载文件</h3><p>' . Html::encode($emptyText) . '</p></div>';
    }

    $html = '<div class="work-grid">';
    foreach ($items as $work) {
        $size = $work->fileSize();
        $html .= '<article class="card work-card"><div class="card-body">';
        $html .= '<span class="badge">' . Html::encode($work->type) . '</span>';
        $html .= '<h3>' . Html::encode($work->title) . '</h3>';
        $html .= '<p>' . Html::encode($work->description ?: '暂无文件说明。') . '</p>';
        $html .= '<p class="work-meta">上传时间：' . Html::encode(substr($work->create_time, 0, 10)) . '</p>';
        if ($size !== null) {
            $html .= '<p class="work-meta">文件大小：' . Html::encode(Yii::$app->formatter->asShortSize($size, 1)) . '</p>';
        }
        $html .= Html::a('下载文件', Url::to(['download', 'id' => $work->id]), [
            'class' => 'btn btn-primary btn-sm',
        ]);
        $html .= '</div></article>';
    }
    return $html . '</div>';
};
?>
<div class="team-page">
    <header class="page-header">
        <p class="team-kicker">COURSE WORK ARCHIVE</p>
        <h1>作业下载</h1>
        <p class="lead">这里归档团队作业和成员个人作业，文件仅通过控制器校验后下载。</p>
    </header>

    <section class="download-group">
        <div class="section-heading">
            <h2 class="section-title">团队作业</h2>
            <p class="section-subtitle">需求、设计、实现、用户手册与部署材料。</p>
        </div>
        <?= $renderWorks($teamWorks, '管理员尚未上传团队作业。') ?>
    </section>

    <section class="download-group">
        <div class="section-heading">
            <h2 class="section-title">个人作业</h2>
            <p class="section-subtitle">团队成员提交的个人课程作业。</p>
        </div>
        <?= $renderWorks($personalWorks, '管理员尚未上传个人作业。') ?>
    </section>

    <p><?= Html::a('返回团队展示', ['index'], ['class' => 'btn btn-default']) ?></p>
</div>

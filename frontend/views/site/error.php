<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 错误页
 *
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var Exception $exception
 */

use yii\helpers\Html;

$statusCode = isset($exception) && $exception instanceof \yii\web\HttpException
    ? (int) $exception->statusCode
    : 500;
$titles = [
    400 => '请求无法处理',
    403 => '没有访问权限',
    404 => '页面未找到',
];
$this->title = isset($titles[$statusCode]) ? $titles[$statusCode] : '服务器错误';

if ($statusCode === 404) {
    $reason = '这个地址可能已经失效，或者比赛 / 球队 / 球员编号不存在。';
} elseif ($statusCode === 403) {
    $reason = '这个页面需要登录或更高权限，请登录后再试。';
} else {
    $reason = '服务器处理请求时出错，请稍后再试。';
}
?>
<div class="site-error">
    <header class="page-header">
        <h1><?= Html::encode($statusCode . ' · ' . $this->title) ?></h1>
        <p><?= Html::encode($reason) ?></p>
    </header>

    <div class="empty-state">
        <p>你仍然可以继续浏览站内数据。</p>
        <p>
            <?php if ($statusCode === 403): ?>
                <?= Html::a('去登录', ['/site/login'], ['class' => 'btn btn-primary']) ?>
            <?php else: ?>
                <?= Html::a('回到首页', Yii::$app->homeUrl, ['class' => 'btn btn-primary']) ?>
            <?php endif ?>
            <?= Html::a('浏览比赛', ['/game/index'], ['class' => 'btn btn-default']) ?>
        </p>
    </div>
</div>

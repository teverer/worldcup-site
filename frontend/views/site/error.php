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
$isNotFound = $statusCode === 404;
?>
<div class="site-error">
    <header class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p>请求没有完成，但你还可以继续浏览站内数据。</p>
    </header>

    <div class="empty-state">
        <h3><?= Html::encode($statusCode) ?> · <?= Html::encode($this->title) ?></h3>
        <p><?php if ($isNotFound): ?>
            这个地址可能已经失效，或者比赛 / 球队 / 球员编号不存在。
        <?php elseif ($statusCode === 403): ?>
            这个页面需要登录或更高权限，请登录后再试。
        <?php else: ?>
            服务器处理请求时出错，请稍后再试。
        <?php endif ?></p>
        <p>
            <?= Html::a('返回首页', Yii::$app->homeUrl, ['class' => 'btn btn-primary']) ?>
            <?= Html::a('浏览比赛', ['/game/index'], ['class' => 'btn btn-default']) ?>
        </p>
    </div>
</div>

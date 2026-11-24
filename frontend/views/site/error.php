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
    <?php // 错误页只做一件事：用最大的数字说明“走到头了”，再把出口放在同一条左轴上。 ?>
    <p class="site-error-code" aria-hidden="true"><?= Html::encode((string) $statusCode) ?></p>
    <h1 class="site-error-title"><?= Html::encode($this->title) ?></h1>
    <p class="site-error-reason"><?= Html::encode($reason) ?></p>

    <div class="site-error-actions">
        <?php if ($statusCode === 403): ?>
            <?= Html::a('去登录', ['/site/login'], ['class' => 'btn btn-primary']) ?>
        <?php else: ?>
            <?= Html::a('回到首页', Yii::$app->homeUrl, ['class' => 'btn btn-primary']) ?>
        <?php endif ?>
        <?= Html::a('浏览比赛', ['/game/index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>

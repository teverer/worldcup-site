<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:后台壳层 —— 后台错误页：状态码、返回上一页与回到工作台。
 */

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Throwable */

use yii\helpers\Html;
use yii\helpers\Url;

$statusCode = $exception instanceof \yii\web\HttpException ? $exception->statusCode : 500;
$titles = [
    403 => '没有权限访问',
    404 => '页面不存在',
    500 => '服务器出了点问题',
];
$friendlyTitle = isset($titles[$statusCode]) ? $titles[$statusCode] : '请求没有完成';

$this->title = $friendlyTitle;

// 只接受同站来源，避免把「返回」做成开放跳转；没有来源时只留工作台入口。
$referrer = Yii::$app->request->referrer;
$backUrl = null;
if (is_string($referrer) && $referrer !== ''
    && strpos($referrer, Yii::$app->request->hostInfo) === 0) {
    $backUrl = $referrer;
}
?>
<div class="site-error">
    <div class="panel panel-warning">
        <div class="panel-heading">
            <strong><?= Html::encode($friendlyTitle) ?></strong>
            <span class="label label-warning pull-right"><?= Html::encode($statusCode) ?></span>
        </div>
        <div class="panel-body">
            <p><?= nl2br(Html::encode($message)) ?></p>
            <p class="text-muted">如果是从站内链接进来的，说明这个页面已经被移动或删除。</p>
            <p>
                <?php if ($backUrl !== null): ?>
                    <?= Html::a('返回上一页', $backUrl, ['class' => 'btn btn-default']) ?>
                <?php endif ?>
                <?= Html::a('回到工作台', Url::to(['/site/index']), ['class' => 'btn btn-primary']) ?>
            </p>
        </div>
    </div>
</div>

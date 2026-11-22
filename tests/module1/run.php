<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 数据模型只读回归测试。
 */

use common\models\Member;
use common\models\TeamInfo;
use common\models\Work;
use yii\helpers\ArrayHelper;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
require $root . '/console/config/bootstrap.php';

$config = ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/common/config/main-local.php',
    require $root . '/console/config/main.php',
    require $root . '/console/config/main-local.php'
);
new yii\console\Application($config);

$passed = 0;
function check($condition, $message)
{
    global $passed;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    $passed++;
    echo "PASS: {$message}\n";
}

$team = TeamInfo::singleton();
check(!$team->isNewRecord, '团队介绍是单例记录');
check(trim((string) $team->name) !== '', '团队名称非空');

$members = Member::find()->orderBy(['id' => SORT_ASC])->all();
check(count($members) === 4, '团队成员为 4 人');
$duties = implode(' ', ArrayHelper::getColumn($members, 'duty'));
foreach (['模块①', '模块②', '模块③', '模块④'] as $module) {
    check(strpos($duties, $module) !== false, "团队分工包含 {$module}");
}

$invalidPaths = [
    '../data/team/file.pdf',
    'team/../private.pdf',
    '/data/team/file.pdf',
    'team\\file.pdf',
    'other/file.pdf',
];
foreach ($invalidPaths as $path) {
    $work = new Work(['file_path' => $path]);
    check($work->resolveFilePath() === null, "拒绝非法文件路径 {$path}");
}

$work = new Work(['title' => '需求:文档/演示', 'file_path' => 'team/example.pdf']);
check($work->downloadName() === '需求-文档-演示.pdf', '下载文件名经过安全处理');
check(Work::typeOptions()[Work::TYPE_TEAM] === '团队', '团队作业类型可用');
check(Work::typeOptions()[Work::TYPE_PERSONAL] === '个人', '个人作业类型可用');

echo "module1 regression passed: {$passed} checks\n";

<?php
return [
    'name' => '世界杯数据门户',
    // 全站内容为中文,显式声明语言,避免 <html lang="en-US"> 与读屏/搜索引擎不一致。
    'language' => 'zh-CN',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            // MySQL 8 的 information_schema 查询返回大写列名，与 Yii 2.0.32 期望的小写列名不符，
            // 导致 mysql\Schema::findConstraints() 报 Undefined array key "constraint_name"。
            // 统一将 PDO 结果列名转为小写以兼容。
            'attributes' => [PDO::ATTR_CASE => PDO::CASE_LOWER],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        // 统一空值展示:避免表格里出现红色的英文“(not set)”。
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'nullDisplay' => '<span class="text-muted">—</span>',
        ],
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'common\models\User',
            // user 表没有 auth_key 字段,不支持“记住我”
            'enableAutoLogin' => false,
        ],
    ],
];

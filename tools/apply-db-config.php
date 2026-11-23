<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:部署文档 —— 根据环境变量生成 Yii 本地数据库配置。
 */

$root = dirname(__DIR__);
$target = $root . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'config'
    . DIRECTORY_SEPARATOR . 'main-local.php';
$host = getenv('WC_DB_HOST') ?: '127.0.0.1';
$port = getenv('WC_DB_PORT') ?: '3306';
$database = getenv('WC_DB_NAME') ?: 'worldcup';
$username = getenv('WC_DB_USER') ?: 'root';
$password = getenv('WC_DB_PASSWORD');
$password = $password === false ? '' : $password;

foreach (['host' => $host, 'port' => $port, 'database' => $database] as $name => $value) {
    if (!preg_match('/^[A-Za-z0-9_.:-]+$/D', $value)) {
        fwrite(STDERR, "Invalid database {$name}.\n");
        exit(2);
    }
}
if ($username === '' || preg_match('/[\r\n]/', $username)) {
    fwrite(STDERR, "Invalid database username.\n");
    exit(2);
}

$config = [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
    ],
];
$content = "<?php\nreturn " . var_export($config, true) . ";\n";

if (in_array('--check', $argv, true)) {
    echo "Database configuration is valid: {$username}@{$host}:{$port}/{$database}\n";
    exit(0);
}

$directory = dirname($target);
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
    fwrite(STDERR, "Unable to create the config directory.\n");
    exit(3);
}

$temporary = $target . '.tmp-' . bin2hex(random_bytes(4));
if (file_put_contents($temporary, $content, LOCK_EX) === false || !rename($temporary, $target)) {
    @unlink($temporary);
    fwrite(STDERR, "Unable to write common/config/main-local.php.\n");
    exit(3);
}

echo "Database configuration written to common/config/main-local.php.\n";

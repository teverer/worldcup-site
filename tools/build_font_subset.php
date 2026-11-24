<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:界面设计 —— 重新生成得意黑（Smiley Sans）站内字集。
 *
 * 用法：php tools/build_font_subset.php
 * 产物：runtime/font-charset.txt + runtime/SmileySans-Subset.woff2
 * 依赖：Python fontTools（pyftsubset 在 PATH 中）。
 *
 * 收集范围：前台/后台/公共 PHP 源码中的文案 + 数据库里的队名、球员名、届次、
 * 资讯与留言正文 + 手工补充的渲染字符。生成后请把产物复制到
 * frontend/web/fonts/ 与 backend/web/fonts/，并保留完整字体文件。
 */

$root = dirname(__DIR__);
$chars = [];
$add = static function ($s) use (&$chars) {
    foreach (preg_split('//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY) as $c) {
        $chars[$c] = true;
    }
};

// 1) 源码中的静态文案
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = str_replace(chr(92), '/', $file->getPathname());
    if (strpos($path, '/vendor/') !== false || strpos($path, '/runtime/') !== false
        || strpos($path, '/web/assets/') !== false || !preg_match('/\.php$/', $path)) {
        continue;
    }
    $add(file_get_contents($path));
}

// 2) 数据库中的动态文字（只读取，不修改）
$cfg = require $root . '/common/config/main-local.php';
try {
    $pdo = new PDO(
        $cfg['components']['db']['dsn'],
        $cfg['components']['db']['username'],
        $cfg['components']['db']['password']
    );
    $queries = [
        'SELECT name FROM team',
        'SELECT name_zh FROM player',
        'SELECT name FROM player',
        'SELECT name FROM tournament',
        'SELECT host_country FROM tournament',
        'SELECT title FROM news',
        'SELECT content FROM news',
        'SELECT content FROM comment',
        'SELECT position FROM player',
        'SELECT stage FROM game',
    ];
    foreach ($queries as $sql) {
        try {
            foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $value) {
                $add($value);
            }
        } catch (Throwable $e) {
            // 表结构变化时忽略该列，其余范围仍可生成
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, "数据库不可用，只使用源码文案生成字集。\n");
}

$runtime = $root . '/runtime';
if (!is_dir($runtime)) {
    mkdir($runtime, 0775, true);
}
$charsetFile = $runtime . '/font-charset.txt';
file_put_contents($charsetFile, implode('', array_keys($chars)));

$source = $root . '/frontend/web/fonts/SmileySans-Oblique.woff2';
$output = $runtime . '/SmileySans-Subset.woff2';
$cmd = 'pyftsubset ' . escapeshellarg($source)
    . ' --text-file=' . escapeshellarg($charsetFile)
    . ' --unicodes="U+0020-007E,U+00A0,U+2000-206F,U+3000-303F,U+FF00-FFEF"'
    . ' --flavor=woff2 --layout-features="kern,liga,vert"'
    . ' --output-file=' . escapeshellarg($output);
passthru($cmd, $code);
if ($code !== 0) {
    fwrite(STDERR, "pyftsubset 失败，请确认已安装 fontTools。\n");
    exit($code);
}
echo "字集：" . count($chars) . " 字符\n产物：$output\n";
echo "请复制到 frontend/web/fonts/ 与 backend/web/fonts/。\n";

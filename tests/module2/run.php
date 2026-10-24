<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 在专用测试库内验证跨届查询、统计口径及历史数据保护。
 */

use common\models\Player;
use common\models\PlayerSearch;
use common\models\Team;
use common\models\TeamSearch;
use yii\db\IntegrityException;
use yii\db\Query;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
$dsn = getenv('WC_TEST_DSN');
if (!$dsn || !preg_match('/dbname=worldcup_module2_test(?:;|$)/', $dsn)) {
    fwrite(STDERR, "WC_TEST_DSN 必须指向专用 worldcup_module2_test 数据库。\n");
    exit(1);
}
new yii\console\Application(['id' => 'module2-tests', 'basePath' => $root, 'components' => [
    'db' => ['class' => 'yii\db\Connection', 'dsn' => $dsn, 'username' => getenv('WC_TEST_USER') ?: 'root',
        'password' => getenv('WC_TEST_PASSWORD') ?: '', 'charset' => 'utf8mb4'],
]]);
$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo "PASS: $message\n";
}
function saveRecord($class, array $attributes) {
    $record = new $class();
    $record->attributes = $attributes;
    if (!$record->save()) {
        throw new RuntimeException(json_encode($record->errors, JSON_UNESCAPED_UNICODE));
    }
    return $record;
}
$db = Yii::$app->db;
$transaction = $db->beginTransaction();
try {
    $a = saveRecord(Team::class, ['name' => '测试球队甲', 'code' => 'tsta', 'continent' => '亚洲']);
    $b = saveRecord(Team::class, ['name' => '测试球队乙', 'code' => 'TSTB', 'continent' => '欧洲']);
    check($a->code === 'TSTA', '球队代码自动转大写');
    $duplicate = new Team(['name' => $a->name]);
    check(!$duplicate->validate() && $duplicate->hasErrors('name'), '禁止重复球队名称');
    $blank = saveRecord(Team::class, ['name' => '测试空代码甲', 'code' => '']);
    $otherBlank = saveRecord(Team::class, ['name' => '测试空代码乙', 'code' => '']);
    check($blank->code === null && $otherBlank->code === null, '多支球队允许不填写代码');
    $a->flag = '../outside.png';
    check(!$a->validate() && $a->hasErrors('flag'), '拒绝图片路径越界');
    $a->flag = 'https://outside.example/image.png';
    check(!$a->validate(), '图片只接受站内相对路径');
    $a->flag = 'uploads/team/test.png';
    check($a->validate(), '接受合法站内图片路径');
    $a->flag = null;

    $p = saveRecord(Player::class, ['name' => '测试球员甲', 'nationality' => '与代表队不同的国籍', 'position' => '前锋']);
    $q = saveRecord(Player::class, ['name' => '测试球员乙', 'nationality' => '测试国籍', 'position' => '中场']);
    $p->birth_date = '2020-02-30';
    check(!$p->validate(), '拒绝不存在的出生日期');
    $p->birth_date = date('Y-m-d', strtotime('+1 day'));
    check(!$p->validate(), '拒绝未来出生日期');
    $p->birth_date = '2000-02-29';
    check($p->validate(), '接受有效闰年日期');
    $p->load(['Player' => ['id' => 900000, 'create_time' => '1900-01-01']]);
    check($p->id != 900000 && $p->create_time !== '1900-01-01', '主键和创建时间不接受表单批量赋值');
    $y22 = (new Query())->select('id')->from('tournament')->where(['year' => 2022])->scalar();
    $y18 = (new Query())->select('id')->from('tournament')->where(['year' => 2018])->scalar();
    foreach ([[$y22, $a->id, $p->id, 10], [$y18, $a->id, $p->id, 10], [$y22, $b->id, $q->id, 9]] as $s) {
        $db->createCommand()->insert('squad', array_combine(['tournament_id', 'team_id', 'player_id', 'shirt_number'], $s))->execute();
    }
    $search = new PlayerSearch();
    $provider = $search->search(['teamId' => $a->id]);
    check($provider->getTotalCount() === 1 && $provider->getModels()[0]->id == $p->id, '按参赛名单筛选球队，跨届去重且不依赖国籍');
    check($a->rosterQuery()->count() == 1 && $a->rosterQuery($y18)->count() == 1, '球队名单支持跨届和单届查询');
    check(count($p->participations()) === 2, '球员履历保留每一届参赛经历');
    check((new PlayerSearch())->search(['teamId' => 'bad'])->getTotalCount() === 0, '非法球队筛选不返回全部球员');
    check((new PlayerSearch())->search(['q' => ['invalid']])->getTotalCount() === 0, '数组搜索参数不会导致服务器错误');
    check((new TeamSearch())->search(['q' => "' OR 1=1 --"])->getTotalCount() === 0, '搜索内容按参数值处理');
    check((new TeamSearch())->search(['q' => '测试球队', 'continent' => '亚洲'])->getTotalCount() === 1, '球队名称和大洲组合筛选');

    $games = [];
    foreach ([2, 1] as $status) {
        $db->createCommand()->insert('game', ['tournament_id' => $y22, 'home_team_id' => $a->id,
            'away_team_id' => $b->id, 'home_score' => 1, 'away_score' => 1, 'stage' => '小组赛', 'status' => $status])->execute();
        $games[] = $db->getLastInsertID();
    }
    foreach ([[$p->id, $q->id, 0, 0, $games[0]], [$q->id, $p->id, 0, 0, $games[0]],
        [$p->id, $q->id, 1, 0, $games[0]], [$p->id, $q->id, 0, 1, $games[0]],
        [$p->id, $q->id, 0, 0, $games[1]]] as $g) {
        $attributes = array_combine(['player_id', 'assist_player_id', 'is_own_goal', 'is_shootout', 'game_id'], $g);
        $db->createCommand()->insert('goal', $attributes + ['team_id' => $a->id, 'minute' => 50])->execute();
    }
    $stats = $p->statistics();
    check(count($stats) === 1 && $stats[0]['goals'] == 1 && $stats[0]['assists'] == 1, '统计排除乌龙、点球大战和未结束比赛');
    try {
        $p->delete();
        check(false, '球员历史数据外键保护');
    } catch (IntegrityException $exception) {
        check(Player::findOne($p->id) !== null, '有历史记录的球员不能删除');
    }
    try {
        $a->delete();
        check(false, '球队历史数据外键保护');
    } catch (IntegrityException $exception) {
        check(Team::findOne($a->id) !== null, '有历史记录的球队不能删除');
    }
    $uid = (new Query())->select('id')->from('user')->limit(1)->scalar();
    $db->createCommand()->insert('comment', ['user_id' => $uid, 'content' => '测试留言', 'target_type' => 'team', 'target_id' => $blank->id])->execute();
    check($blank->delete() === false && Team::findOne($blank->id) !== null, '多态留言保护球队删除');
    check($otherBlank->delete() === 1 && Team::findOne($otherBlank->id) === null, '无关联记录可以正常删除');
    $blank->name = '修改后的球队';
    check($blank->save() && Team::findOne($blank->id)->name === '修改后的球队', '球队资料可修改并持久化');
    $p->name = '修改后的球员';
    check($p->save() && Player::findOne($p->id)->name === '修改后的球员', '球员资料可修改并持久化');
    echo "\n$checks checks passed.\n";
} finally {
    $transaction->rollBack();
}

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261117
 * 模块:留言与资讯 —— 在专用测试库内回归模型规则和查询口径。
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

use backend\models\CommentSearch;
use common\components\CommentTarget;
use common\models\Comment;
use common\models\CommentLike;
use common\models\News;
use common\models\NewsSearch;
use common\models\Team;
use yii\db\Query;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';

$dsn = getenv('WC_TEST_DSN');
if (!$dsn || !preg_match('/dbname=worldcup_module4_test(?:;|$)/', $dsn)) {
    fwrite(STDERR, "WC_TEST_DSN 必须指向专用 worldcup_module4_test 数据库。\n");
    exit(1);
}

new yii\console\Application([
    'id' => 'module4-tests',
    'basePath' => $root,
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => $dsn,
            'username' => getenv('WC_TEST_USER') ?: 'root',
            'password' => getenv('WC_TEST_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'attributes' => [
                PDO::ATTR_CASE => PDO::CASE_LOWER,
            ],
        ],
    ],
]);

$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo "PASS: {$message}\n";
}

function saveRecord($class, array $attributes)
{
    $record = new $class();
    $record->attributes = $attributes;
    if (!$record->save()) {
        throw new RuntimeException(json_encode($record->getErrors(), JSON_UNESCAPED_UNICODE));
    }
    return $record;
}

$db = Yii::$app->db;
$transaction = $db->beginTransaction();
try {
    $demoUserId = (int) (new Query())->select('id')->from('{{%user}}')
        ->where(['username' => 'demo'])->scalar();
    $adminUserId = (int) (new Query())->select('id')->from('{{%user}}')
        ->where(['username' => 'admin'])->scalar();
    $tournamentId = (int) (new Query())->select('id')->from('{{%tournament}}')
        ->where(['year' => 2022])->scalar();
    check($demoUserId > 0 && $adminUserId > 0 && $tournamentId > 0, '测试用户和届次存在');

    $suffix = strtoupper(substr(uniqid(), -6));
    $teamA = saveRecord(Team::class, [
        'name' => '模块四测试队' . $suffix,
        'code' => 'M' . $suffix,
        'continent' => '亚洲',
    ]);
    $teamB = saveRecord(Team::class, [
        'name' => '模块四对手' . $suffix,
        'code' => 'N' . $suffix,
        'continent' => '欧洲',
    ]);
    $db->createCommand()->insert('{{%player}}', [
        'name' => '模块四测试球员' . $suffix,
        'position' => '前锋',
    ])->execute();
    $playerId = (int) $db->getLastInsertID();
    $db->createCommand()->insert('{{%game}}', [
        'tournament_id' => $tournamentId,
        'home_team_id' => $teamA->id,
        'away_team_id' => $teamB->id,
        'home_score' => 1,
        'away_score' => 0,
        'stage' => '小组赛',
        'status' => 2,
    ])->execute();
    $gameId = (int) $db->getLastInsertID();

    check(CommentTarget::exists(CommentTarget::TEAM, $teamA->id), '识别球队留言目标');
    check(CommentTarget::exists(CommentTarget::PLAYER, $playerId), '识别球员留言目标');
    check(CommentTarget::exists(CommentTarget::GAME, $gameId), '识别比赛留言目标');
    check(CommentTarget::exists(CommentTarget::TOURNAMENT, $tournamentId), '识别届次留言目标');
    check(!CommentTarget::exists(CommentTarget::NEWS, 99999999), '拒绝不存在的资讯目标');
    check(!CommentTarget::exists('unknown', 1), '拒绝未知目标类型');

    $invalidTarget = new Comment([
        'user_id' => $demoUserId,
        'content' => '非法目标',
        'target_type' => 'unknown',
        'target_id' => 1,
    ]);
    check(!$invalidTarget->validate() && $invalidTarget->hasErrors('target_type'), '留言模型拒绝非法目标类型');

    $rootComment = new Comment([
        'user_id' => $demoUserId,
        'content' => '模块四根留言',
        'target_type' => CommentTarget::TEAM,
        'target_id' => $teamA->id,
    ]);
    check($rootComment->save(), '有效球队留言可以保存');

    $reply = new Comment([
        'user_id' => $adminUserId,
        'content' => '模块四同目标回复',
        'target_type' => CommentTarget::TEAM,
        'target_id' => $teamA->id,
        'parent_id' => $rootComment->id,
    ]);
    check($reply->save(), '同目标回复可以保存');

    $crossTarget = new Comment([
        'user_id' => $demoUserId,
        'content' => '模块四跨目标回复',
        'target_type' => CommentTarget::PLAYER,
        'target_id' => $playerId,
        'parent_id' => $rootComment->id,
    ]);
    check(!$crossTarget->validate() && $crossTarget->hasErrors('parent_id'), '拒绝跨目标回复');

    $grandReply = new Comment([
        'user_id' => $adminUserId,
        'content' => '模块四二级回复',
        'target_type' => CommentTarget::TEAM,
        'target_id' => $teamA->id,
        'parent_id' => $reply->id,
    ]);
    check($grandReply->save(), '多级回复可以保存');
    check($rootComment->softDeleteTree() >= 3, '软删除隐藏完整回复树');
    check((int) Comment::findOne($reply->id)->status === Comment::STATUS_DELETED, '回复同步软删除');
    check(!Comment::findActive()->andWhere(['id' => $rootComment->id])->exists(), '已删除留言退出有效查询');

    $likeTarget = new Comment([
        'user_id' => $demoUserId,
        'content' => '模块四点个赞',
        'target_type' => CommentTarget::PLAYER,
        'target_id' => $playerId,
    ]);
    check($likeTarget->save(), '球员留言可以保存');
    $like = new CommentLike(['comment_id' => $likeTarget->id, 'user_id' => $adminUserId]);
    check($like->save(), '有效留言可以点赞');
    $duplicateLike = new CommentLike([
        'comment_id' => $likeTarget->id,
        'user_id' => $adminUserId,
    ]);
    check(!$duplicateLike->save() && $duplicateLike->hasErrors('comment_id'), '阻止重复点赞');
    $deletedLike = new CommentLike(['comment_id' => $rootComment->id, 'user_id' => $adminUserId]);
    check(!$deletedLike->save() && $deletedLike->hasErrors('comment_id'), '已删除留言不能点赞');

    $published = new News([
        'title' => '模块四发布资讯' . $suffix,
        'content' => '<p>正文</p><script>alert(1)</script>',
        'author' => '测试者',
        'status' => News::STATUS_PUBLISHED,
    ]);
    check($published->save(), '发布资讯可以保存');
    check(!empty($published->publish_time), '首次发布自动填写时间');
    check(strpos(News::findOne($published->id)->content, '<script>') === false, '资讯正文脚本被清理');

    $draft = new News([
        'title' => '模块四草稿资讯' . $suffix,
        'content' => '<p>草稿</p>',
        'author' => '测试者',
        'status' => News::STATUS_DRAFT,
    ]);
    check($draft->save(), '草稿资讯可以保存');
    check(empty($draft->publish_time), '草稿没有发布时间');

    $invalidTime = new News([
        'title' => '非法时间',
        'status' => News::STATUS_PUBLISHED,
        'publish_time' => ['bad'],
    ]);
    check(!$invalidTime->validate() && $invalidTime->hasErrors('publish_time'), '拒绝非法发布时间');
    $invalidCover = new News([
        'title' => '非法封面',
        'status' => News::STATUS_DRAFT,
        'cover' => '../outside.png',
    ]);
    check(!$invalidCover->validate() && $invalidCover->hasErrors('cover'), '拒绝越界封面路径');

    $newsSearch = new NewsSearch();
    $publishedIds = array_map('intval', array_column(
        $newsSearch->searchPublished([])->getModels(),
        'id'
    ));
    check(in_array((int) $published->id, $publishedIds, true), '前台资讯查询包含已发布资讯');
    check(!in_array((int) $draft->id, $publishedIds, true), '前台资讯查询排除草稿');
    $draftSearch = new NewsSearch();
    check($draftSearch->search(['status' => News::STATUS_DRAFT])->getTotalCount() >= 1, '后台资讯状态筛选生效');

    $commentSearch = new CommentSearch();
    $searchResult = $commentSearch->search([
        'q' => '模块四',
        'targetType' => CommentTarget::PLAYER,
        'status' => Comment::STATUS_ACTIVE,
    ]);
    check($searchResult->getTotalCount() >= 1, '后台留言组合筛选生效');
    $invalidSearch = new CommentSearch();
    check($invalidSearch->search(['targetType' => 'unknown'])->getTotalCount() === 0, '非法留言筛选返回空结果');

    $newsComment = new Comment([
        'user_id' => $demoUserId,
        'content' => '模块四资讯留言',
        'target_type' => CommentTarget::NEWS,
        'target_id' => $published->id,
    ]);
    check($newsComment->save(), '资讯可以接收留言');
    check($published->delete() === 1, '资讯可以删除');
    check(Comment::findOne($newsComment->id) === null, '删除资讯时清理多态留言');

    echo "\n{$checks} module4 model checks passed.\n";
} finally {
    $transaction->rollBack();
}

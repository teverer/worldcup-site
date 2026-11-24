<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:留言与资讯 —— 可复用的多态留言展示组件（留言簿）。
 */

namespace frontend\widgets;

use Yii;
use common\components\CommentTarget;
use common\models\Comment;
use common\models\CommentLike;
use common\models\User;
use yii\base\InvalidArgumentException;
use yii\base\Widget;
use yii\db\Expression;
use yii\web\View;

class CommentSection extends Widget
{
    public $targetType;
    public $targetId;

    /**
     * 留言簿标题；全站页可以用“全站留言”。
     *
     * @var string
     */
    public $heading = '留言簿';

    /**
     * 标题的 HTML 标签；独立留言簿页把标题作为页面 h1。
     *
     * @var string
     */
    public $headingTag = 'h2';

    /**
     * 标题下的一句说明；为空时不输出。
     *
     * @var string
     */
    public $intro = '';

    /**
     * 空态标题；默认按「这一页」措辞，全站留言簿页可改成面向整本簿的说法。
     *
     * @var string
     */
    public $emptyTitle = '这一页还没有人留言。';

    /**
     * 每个目标页的固定提问：不是“添加评论”，而是把这一页的记忆问出来。
     *
     * @return string[]
     */
    public static function prompts()
    {
        return [
            CommentTarget::TOURNAMENT => '欢迎留言分享',
            CommentTarget::GAME => '这一场，你记得哪一刻？',
            CommentTarget::TEAM => '你和这支球队，从哪一届开始？',
            CommentTarget::PLAYER => '关于他，你记得哪一个球？',
            CommentTarget::NEWS => '读完这一篇，你想起了什么？',
            CommentTarget::SITE => '你和世界杯的故事，从哪一届开始？',
        ];
    }

    /**
     * @param string $type
     * @return string
     */
    public static function promptFor($type)
    {
        $prompts = self::prompts();
        return isset($prompts[$type]) ? $prompts[$type] : '这一页，你想留下什么？';
    }

    /**
     * 输入框里的引导语；不重复标题下的提问。
     *
     * @param string $type
     * @return string
     */
    public static function composerPlaceholderFor($type)
    {
        $placeholders = [
            CommentTarget::TOURNAMENT => 'Ctrl + Enter 记下',
            CommentTarget::GAME => '写下你记得的那一刻……',
            CommentTarget::TEAM => '写下你和这支球队的记忆……',
            CommentTarget::PLAYER => '写下你记得的那个球……',
            CommentTarget::NEWS => '写下读完这一篇的想法……',
            CommentTarget::SITE => '写下你和世界杯的一件小事……',
        ];
        return isset($placeholders[$type]) ? $placeholders[$type] : '写下你想留下的那句话……';
    }

    /**
     * 空留言簿里的三个起点。点击后直接填进输入框。
     *
     * @param string $type
     * @return string[]
     */
    public static function startersFor($type)
    {
        $starters = [
            CommentTarget::TOURNAMENT => [
                '你当时在哪看的？',
                '这一届，你记住了哪一夜？',
                '比分之外，你还记得什么？',
            ],
            CommentTarget::GAME => [
                '你当时在哪看的？',
                '哪一刻让你站了起来？',
                '比分之外，你还记得什么？',
            ],
            CommentTarget::TEAM => [
                '你从哪一届开始看这支球队？',
                '你记得他们的哪一场？',
                '哪个球员让你记住了这支球队？',
            ],
            CommentTarget::PLAYER => [
                '你第一次记住他，是哪一届？',
                '你记得他的哪一个球？',
                '关于他，你还有什么想说？',
            ],
            CommentTarget::NEWS => [
                '读完这篇，你想起了什么？',
                '你同意作者的观点吗？',
                '你还有什么想补充的？',
            ],
            CommentTarget::SITE => [
                '你第一次看世界杯是哪一届？',
                '你和谁一起看过球？',
                '哪一届是你的起点？',
            ],
        ];
        return isset($starters[$type]) ? $starters[$type] : ['写下你想留下的那句话……'];
    }

    /**
     * 名字印章的六种深色：像不同人的深色外套，不做彩虹头像。
     *
     * @return string[]
     */
    public static function sealPalette()
    {
        return [
            '#0E5138',
            '#2F4A55',
            '#5A3A2E',
            '#3E4A46',
            '#4A4A2A',
            '#0B1418',
        ];
    }

    /**
     * @param int $userId
     * @return string
     */
    public static function sealColor($userId)
    {
        $palette = self::sealPalette();
        $index = abs((int) $userId) % count($palette);
        return $palette[$index];
    }

    /**
     * @param string $name
     * @return string
     */
    public static function sealInitial($name)
    {
        $name = trim((string) $name);
        return $name === '' ? '?' : mb_substr($name, 0, 1, 'UTF-8');
    }

    /**
     * @param Comment $comment
     * @return string
     */
    public static function authorName(Comment $comment)
    {
        $user = $comment->user;
        return $user ? ($user->nickname ?: $user->username) : '未知用户';
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if (!CommentTarget::isSupportedType($this->targetType)) {
            throw new InvalidArgumentException('Unsupported comment target type.');
        }
        $this->targetId = CommentTarget::normalizeId($this->targetId);
        if ($this->targetId === null) {
            throw new InvalidArgumentException('Invalid comment target id.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getViewPath()
    {
        return Yii::getAlias('@frontend/views/comment');
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->view->registerJsFile('@web/js/ledger.js', ['position' => View::POS_END]);

        $comments = Comment::findForTarget($this->targetType, $this->targetId)
            ->with('user')
            ->all();
        $childrenByParent = [];
        $authorsById = [];
        $participantIds = [];
        foreach ($comments as $comment) {
            $parentKey = $comment->parent_id === null ? 0 : (int) $comment->parent_id;
            $childrenByParent[$parentKey][] = $comment;
            $authorsById[(int) $comment->id] = self::authorName($comment);
            if ((int) $comment->user_id > 0) {
                $participantIds[(int) $comment->user_id] = true;
            }
        }
        $roots = isset($childrenByParent[0]) ? $childrenByParent[0] : [];
        $commentIds = array_map('intval', array_column($comments, 'id'));

        $likeCounts = [];
        $likedIds = [];
        if ($commentIds) {
            $rows = CommentLike::find()
                ->select(['comment_id', 'like_count' => new Expression('COUNT(*)')])
                ->where(['comment_id' => $commentIds])
                ->groupBy('comment_id')
                ->asArray()
                ->all();
            foreach ($rows as $row) {
                $likeCounts[(int) $row['comment_id']] = (int) $row['like_count'];
            }

            if (!Yii::$app->user->isGuest) {
                $ids = CommentLike::find()
                    ->select('comment_id')
                    ->where(['comment_id' => $commentIds, 'user_id' => Yii::$app->user->id])
                    ->column();
                $likedIds = array_fill_keys(array_map('intval', $ids), true);
            }
        }

        $identity = Yii::$app->user->identity;
        return $this->render('_section', [
            'targetType' => $this->targetType,
            'targetId' => $this->targetId,
            'comments' => $comments,
            'roots' => $roots,
            'childrenByParent' => $childrenByParent,
            'authorsById' => $authorsById,
            'likeCounts' => $likeCounts,
            'likedIds' => $likedIds,
            'isAdmin' => $identity !== null && (int) $identity->role >= User::ROLE_ADMIN,
            'currentUserId' => $identity !== null ? (int) $identity->id : null,
            'prompt' => self::promptFor($this->targetType),
            'placeholder' => self::composerPlaceholderFor($this->targetType),
            'starters' => self::startersFor($this->targetType),
            'participantCount' => count($participantIds),
            'heading' => $this->heading,
            'headingTag' => in_array($this->headingTag, ['h1', 'h2'], true) ? $this->headingTag : 'h2',
            'intro' => $this->intro,
            'emptyTitle' => $this->emptyTitle,
        ]);
    }
}

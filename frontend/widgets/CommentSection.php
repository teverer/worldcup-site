<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 可复用的多态留言展示组件。
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

class CommentSection extends Widget
{
    public $targetType;
    public $targetId;

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
        $this->view->registerCssFile('@web/css/community.css');

        $comments = Comment::findForTarget($this->targetType, $this->targetId)
            ->with('user')
            ->all();
        $childrenByParent = [];
        foreach ($comments as $comment) {
            $parentKey = $comment->parent_id === null ? 0 : (int) $comment->parent_id;
            $childrenByParent[$parentKey][] = $comment;
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
            'targetLabel' => CommentTarget::label($this->targetType),
            'comments' => $comments,
            'roots' => $roots,
            'childrenByParent' => $childrenByParent,
            'likeCounts' => $likeCounts,
            'likedIds' => $likedIds,
            'isAdmin' => $identity !== null && (int) $identity->role >= User::ROLE_ADMIN,
        ]);
    }
}

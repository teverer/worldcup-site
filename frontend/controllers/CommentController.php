<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 登录用户发表、回复、点赞和删除留言。
 */

namespace frontend\controllers;

use Yii;
use common\components\CommentTarget;
use common\models\Comment;
use common\models\CommentLike;
use common\models\User;
use frontend\models\CommentForm;
use frontend\widgets\CommentSection;
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class CommentController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['create', 'like', 'delete'],
                'rules' => [
                    [
                        'actions' => ['create', 'like', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'like' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $isAjax = Yii::$app->request->isAjax;
        $form = new CommentForm();
        if (!$form->load(Yii::$app->request->post()) || !$form->validate()) {
            if ($isAjax) {
                return $this->jsonError($form);
            }
            $this->flashFormErrors($form);
            return $this->redirectToTargetOrHome($form->targetType, $form->targetId);
        }

        $comment = $form->createComment(Yii::$app->user->id);
        if ($comment === null) {
            if ($isAjax) {
                return $this->jsonError($form);
            }
            $this->flashFormErrors($form);
            return $this->redirectToTargetOrHome($form->targetType, $form->targetId);
        }

        // 表单请求保留 PRG：新留言落在自己那条记录上。
        if (!$isAjax) {
            return $this->redirectToTarget(
                $comment->target_type,
                $comment->target_id,
                'comment-' . (int) $comment->id
            );
        }

        // AJAX：不整页刷新，把新记录的 HTML 和最新计数交回前端就地插入。
        return $this->jsonCreated($comment);
    }

    /**
     * @param CommentForm $form
     * @return \yii\web\Response
     */
    private function jsonError(CommentForm $form)
    {
        $messages = ArrayHelper::getColumn($form->getErrors(), 0);
        $response = $this->asJson([
            'ok' => false,
            'error' => $messages
                ? implode('；', array_unique($messages))
                : '留言提交失败，请检查输入内容。',
        ]);
        $response->setStatusCode(422);
        return $response;
    }

    /**
     * @param Comment $comment
     * @return \yii\web\Response
     */
    private function jsonCreated(Comment $comment)
    {
        // TimestampBehavior 在保存后仍把 create_time 留在 Expression('NOW()')，
        // 同一次请求里直接渲染会拿它去格式化；先回读成数据库里的时间字符串。
        $comment->refresh();

        $widget = new CommentSection([
            'targetType' => $comment->target_type,
            'targetId' => $comment->target_id,
        ]);
        $identity = Yii::$app->user->identity;
        $isAdmin = $identity !== null && (int) $identity->role >= User::ROLE_ADMIN;
        $currentUserId = $identity !== null ? (int) $identity->id : null;
        $isReply = $comment->parent_id !== null;
        $rootId = $isReply ? $this->threadRootId($comment) : (int) $comment->id;

        if ($isReply) {
            $parent = $comment->parent;
            $html = $widget->render('_reply', [
                'comment' => $comment,
                'rootId' => $rootId,
                'parentAuthor' => $parent ? CommentSection::authorName($parent) : null,
                'likeCounts' => [],
                'likedIds' => [],
                'targetType' => $comment->target_type,
                'targetId' => (int) $comment->target_id,
                'isAdmin' => $isAdmin,
                'currentUserId' => $currentUserId,
            ]);
            $replyCount = $this->threadReplyCount(
                $comment->target_type,
                $comment->target_id,
                $rootId
            );
        } else {
            $html = $widget->render('_item', [
                'comment' => $comment,
                'replies' => [],
                'authorsById' => [(int) $comment->id => CommentSection::authorName($comment)],
                'likeCounts' => [],
                'likedIds' => [],
                'targetType' => $comment->target_type,
                'targetId' => (int) $comment->target_id,
                'isAdmin' => $isAdmin,
                'currentUserId' => $currentUserId,
            ]);
            $replyCount = 0;
        }

        $comments = Comment::findForTarget($comment->target_type, $comment->target_id)
            ->with('user')
            ->all();
        $participantIds = [];
        foreach ($comments as $row) {
            if ((int) $row->user_id > 0) {
                $participantIds[(int) $row->user_id] = true;
            }
        }

        return $this->asJson([
            'ok' => true,
            'id' => (int) $comment->id,
            'html' => $html,
            'total' => count($comments),
            'participants' => count($participantIds),
            'isReply' => $isReply,
            'rootId' => $rootId,
            'parentId' => $comment->parent_id !== null ? (int) $comment->parent_id : null,
            'replyCount' => $replyCount,
        ]);
    }

    /**
     * @param Comment $comment
     * @return int
     */
    private function threadRootId(Comment $comment)
    {
        $node = $comment;
        $guard = 0;
        while ($node->parent_id !== null && $guard++ < 64) {
            $parent = $node->parent;
            if ($parent === null) {
                break;
            }
            $node = $parent;
        }
        return (int) $node->id;
    }

    /**
     * @param string $type
     * @param mixed $id
     * @param int $rootId
     * @return int
     */
    private function threadReplyCount($type, $id, $rootId)
    {
        $rows = Comment::findForTarget($type, $id)
            ->select(['id', 'parent_id'])
            ->asArray()
            ->all();
        $children = [];
        foreach ($rows as $row) {
            $children[(int) $row['parent_id']][] = (int) $row['id'];
        }

        $count = 0;
        $stack = [$rootId];
        while ($stack) {
            $current = array_pop($stack);
            if (!isset($children[$current])) {
                continue;
            }
            foreach ($children[$current] as $child) {
                $count++;
                $stack[] = $child;
            }
        }
        return $count;
    }

    /**
     * @param mixed $id
     * @return \yii\web\Response
     */
    public function actionLike($id)
    {
        $comment = $this->findActiveComment($id);
        $isAjax = Yii::$app->request->isAjax;
        $like = new CommentLike([
            'comment_id' => $comment->id,
            'user_id' => Yii::$app->user->id,
        ]);

        try {
            if ($like->save() || $like->hasErrors('comment_id')) {
                // 已同感 / 刚点过都一样：数字就是反馈。
                if ($isAjax) {
                    return $this->jsonLiked($comment);
                }
                return $this->redirectToTarget(
                    $comment->target_type,
                    $comment->target_id,
                    'comment-' . (int) $comment->id
                );
            }
            Yii::$app->session->setFlash('error', '点赞失败，请稍后重试。');
        } catch (IntegrityException $exception) {
            // 唯一索引兜底：重复同感不报错。
            if ($isAjax) {
                return $this->jsonLiked($comment);
            }
            return $this->redirectToTarget(
                $comment->target_type,
                $comment->target_id,
                'comment-' . (int) $comment->id
            );
        }

        if ($isAjax) {
            $response = $this->asJson(['ok' => false, 'error' => '点赞失败，请稍后重试。']);
            $response->setStatusCode(422);
            return $response;
        }
        return $this->redirectToTarget($comment->target_type, $comment->target_id);
    }

    /**
     * @param Comment $comment
     * @return \yii\web\Response
     */
    private function jsonLiked(Comment $comment)
    {
        $count = (int) CommentLike::find()
            ->where(['comment_id' => $comment->id])
            ->count();
        return $this->asJson([
            'ok' => true,
            'count' => $count,
            'liked' => true,
        ]);
    }

    /**
     * @param mixed $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $comment = $this->findActiveComment($id);
        $identity = Yii::$app->user->identity;
        $isAdmin = $identity !== null && (int) $identity->role >= User::ROLE_ADMIN;

        if (!$comment->isOwnedBy(Yii::$app->user->id) && !$isAdmin) {
            throw new ForbiddenHttpException('你无权删除这条留言。');
        }

        $comment->softDeleteTree();
        return $this->redirectToTarget($comment->target_type, $comment->target_id, 'comments');
    }

    /**
     * @param mixed $id
     * @return Comment
     */
    private function findActiveComment($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('留言不存在。');
        }
        $comment = Comment::findActive()->andWhere(['id' => (int) $id])->one();
        if ($comment === null) {
            throw new NotFoundHttpException('留言不存在。');
        }
        return $comment;
    }

    /**
     * @param CommentForm $form
     */
    private function flashFormErrors($form)
    {
        $messages = ArrayHelper::getColumn($form->getErrors(), 0);
        Yii::$app->session->setFlash('error', $messages
            ? implode('；', array_unique($messages))
            : '留言提交失败，请检查输入内容。');
    }

    /**
     * @param string $type
     * @param mixed $id
     * @return \yii\web\Response
     */
    private function redirectToTargetOrHome($type, $id)
    {
        if (CommentTarget::isSupportedType($type)
            && CommentTarget::normalizeId($id) !== null
            && CommentTarget::exists($type, $id)) {
            return $this->redirectToTarget($type, $id);
        }
        return $this->redirect(Yii::$app->homeUrl);
    }

    /**
     * @param string $type
     * @param int $id
     * @param string|null $fragment 不带 # 的锚点，如 comment-12 / comments
     * @return \yii\web\Response
     */
    private function redirectToTarget($type, $id, $fragment = null)
    {
        $route = CommentTarget::route($type, $id);
        if ($route === null) {
            return $this->redirect(Yii::$app->homeUrl);
        }

        $url = Url::to($route);
        if ($fragment !== null && $fragment !== '') {
            $url .= '#' . ltrim($fragment, '#');
        }
        return $this->redirect($url);
    }
}

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
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
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
        $form = new CommentForm();
        if (!$form->load(Yii::$app->request->post()) || !$form->validate()) {
            $this->flashFormErrors($form);
            return $this->redirectToTargetOrHome($form->targetType, $form->targetId);
        }

        $comment = $form->createComment(Yii::$app->user->id);
        if ($comment === null) {
            $this->flashFormErrors($form);
            return $this->redirectToTargetOrHome($form->targetType, $form->targetId);
        }

        Yii::$app->session->setFlash('success', $comment->parent_id
            ? '回复已发布。'
            : '留言已发布。');
        return $this->redirectToTarget($comment->target_type, $comment->target_id);
    }

    /**
     * @param mixed $id
     * @return \yii\web\Response
     */
    public function actionLike($id)
    {
        $comment = $this->findActiveComment($id);
        $like = new CommentLike([
            'comment_id' => $comment->id,
            'user_id' => Yii::$app->user->id,
        ]);

        try {
            if ($like->save()) {
                Yii::$app->session->setFlash('success', '点赞成功。');
            } elseif ($like->hasErrors('comment_id')) {
                Yii::$app->session->setFlash('info', '你已经点赞过这条留言。');
            } else {
                Yii::$app->session->setFlash('error', '点赞失败，请稍后重试。');
            }
        } catch (IntegrityException $exception) {
            Yii::$app->session->setFlash('info', '你已经点赞过这条留言。');
        }

        return $this->redirectToTarget($comment->target_type, $comment->target_id);
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
        Yii::$app->session->setFlash('success', '留言已删除。');
        return $this->redirectToTarget($comment->target_type, $comment->target_id);
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
     * @return \yii\web\Response
     */
    private function redirectToTarget($type, $id)
    {
        $route = CommentTarget::route($type, $id);
        return $route ? $this->redirect($route) : $this->redirect(Yii::$app->homeUrl);
    }
}

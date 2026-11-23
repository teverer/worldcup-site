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

        // 新留言本身就是反馈：落在自己那条记录上并高亮，不再把用户丢回页顶。
        return $this->redirectToTarget(
            $comment->target_type,
            $comment->target_id,
            'comment-' . (int) $comment->id
        );
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
            if ($like->save() || $like->hasErrors('comment_id')) {
                // 已同感 / 刚点过都一样：停在原记录上，数字就是反馈。
                return $this->redirectToTarget(
                    $comment->target_type,
                    $comment->target_id,
                    'comment-' . (int) $comment->id
                );
            }
            Yii::$app->session->setFlash('error', '同感提交失败，请稍后重试。');
        } catch (IntegrityException $exception) {
            // 唯一索引兜底：重复同感不报错，停在原记录上。
            return $this->redirectToTarget(
                $comment->target_type,
                $comment->target_id,
                'comment-' . (int) $comment->id
            );
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

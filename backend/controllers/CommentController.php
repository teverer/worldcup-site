<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261117
 * 模块:留言与资讯 —— 管理员查看、筛选和软删除留言。
 */

namespace backend\controllers;

use Yii;
use backend\models\CommentSearch;
use common\models\Comment;
use common\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
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
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $identity = Yii::$app->user->identity;
                            return $identity !== null
                                && (int) $identity->role >= User::ROLE_ADMIN;
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CommentSearch();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search(Yii::$app->request->queryParams),
        ]);
    }

    /**
     * @param mixed $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $comment = $this->findModel($id);
        if ((int) $comment->status === Comment::STATUS_DELETED) {
            Yii::$app->session->setFlash('info', '该留言已经删除。');
            return $this->redirect(['index']);
        }

        $affected = $comment->softDeleteTree();
        Yii::$app->session->setFlash('success', "已删除该留言及其 {$affected} 条相关记录。");
        return $this->redirect(['index']);
    }

    /**
     * @param mixed $id
     * @return Comment
     */
    private function findModel($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('留言不存在。');
        }
        $model = Comment::findOne((int) $id);
        if ($model === null) {
            throw new NotFoundHttpException('留言不存在。');
        }
        return $model;
    }
}

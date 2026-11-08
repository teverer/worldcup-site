<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261108
 * 模块:留言与资讯 —— 公开资讯列表、搜索和详情。
 */

namespace frontend\controllers;

use Yii;
use common\models\News;
use common\models\NewsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class NewsController extends Controller
{
    /**
     * 只展示已发布资讯，草稿不会进入前台查询。
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new NewsSearch();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->searchPublished(Yii::$app->request->queryParams),
        ]);
    }

    /**
     * @param mixed $id
     * @return string
     */
    public function actionView($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('资讯不存在。');
        }

        $model = News::find()
            ->where(['id' => (int) $id, 'status' => News::STATUS_PUBLISHED])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('资讯不存在。');
        }

        return $this->render('view', ['model' => $model]);
    }
}

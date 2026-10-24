<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 共享后台维护流程、管理员鉴权与历史数据删除保护。
 */

namespace backend\components;

use Yii;
use common\models\User;
use common\models\Team;
use yii\db\IntegrityException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

abstract class CatalogController extends Controller {
    protected $modelClass;
    protected $searchClass;

    public function behaviors() {
        return [
            'access' => ['class' => AccessControl::class, 'rules' => [[
                'allow' => true, 'roles' => ['@'],
                'matchCallback' => function () { return Yii::$app->user->identity->role >= User::ROLE_ADMIN; },
            ]]],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['delete' => ['POST']]],
        ];
    }

    public function actionIndex() {
        $searchModel = new $this->searchClass();
        return $this->render('index', ['searchModel' => $searchModel, 'teams' => Team::options(),
            'dataProvider' => $searchModel->search(Yii::$app->request->queryParams)]);
    }

    public function actionView($id) {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate() {
        return $this->edit(new $this->modelClass(), 'create');
    }

    public function actionUpdate($id) {
        return $this->edit($this->findModel($id), 'update');
    }

    protected function edit($model, $view) {
        if ($model->load(Yii::$app->request->post())) {
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '资料已保存。');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                $model->addError('name', '资料与现有记录冲突，请检查名称或代码。');
            }
        }
        return $this->render($view, ['model' => $model]);
    }

    public function actionDelete($id) {
        $model = $this->findModel($id);
        try {
            if ($model->delete() === false) {
                Yii::$app->session->setFlash('error', implode('；', $model->getFirstErrors()));
            } else {
                Yii::$app->session->setFlash('success', '记录已删除。');
            }
        } catch (IntegrityException $exception) {
            Yii::$app->session->setFlash('error', '该记录已被参赛名单、比赛或进球引用，不能删除历史数据。');
        }
        return $this->redirect(['index']);
    }

    protected function findModel($id) {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('记录不存在。');
        }
        $model = $this->modelClass::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('记录不存在。');
        }
        return $model;
    }
}

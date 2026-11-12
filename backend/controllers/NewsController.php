<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261112
 * 模块:留言与资讯 —— 后台资讯增删改查与封面上传。
 */

namespace backend\controllers;

use Yii;
use common\models\News;
use common\models\NewsSearch;
use common\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class NewsController extends Controller
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
        $searchModel = new NewsSearch();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search(Yii::$app->request->queryParams),
        ]);
    }

    /**
     * @param mixed $id
     * @return string
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new News();
        $identity = Yii::$app->user->identity;
        $model->author = $identity->nickname ?: $identity->username;
        return $this->edit($model, 'create');
    }

    /**
     * @param mixed $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        return $this->edit($this->findModel($id), 'update');
    }

    /**
     * @param mixed $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $cover = $model->cover;
        if ($model->delete() !== false) {
            $this->deleteStoredCover($cover);
            Yii::$app->session->setFlash('success', '资讯及其留言已删除。');
        } else {
            Yii::$app->session->setFlash('error', '资讯删除失败。');
        }
        return $this->redirect(['index']);
    }

    /**
     * @param News $model
     * @param string $view
     * @return string|\yii\web\Response
     */
    protected function edit($model, $view)
    {
        $oldCover = $model->isNewRecord ? null : $model->getOldAttribute('cover');
        if ($model->load(Yii::$app->request->post())) {
            $model->coverFile = UploadedFile::getInstance($model, 'coverFile');
            if ($model->validate()) {
                $newCover = null;
                try {
                    if ($model->coverFile !== null) {
                        $newCover = $this->storeCover($model->coverFile);
                        $model->cover = $newCover;
                    }

                    if ($model->save(false)) {
                        if ($newCover !== null && $oldCover !== $newCover) {
                            $this->deleteStoredCover($oldCover);
                        }
                        Yii::$app->session->setFlash('success', '资讯已保存。');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (\Throwable $exception) {
                    if ($newCover !== null) {
                        $this->deleteStoredCover($newCover, false);
                    }
                    Yii::error($exception, __METHOD__);
                    $model->addError('coverFile', '封面保存失败，请检查上传目录权限。');
                }
            }
        }

        return $this->render($view, ['model' => $model]);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    private function storeCover($file)
    {
        $directory = Yii::getAlias('@frontend/web/uploads/news');
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create the news upload directory.');
        }

        $extension = strtolower($file->extension);
        $name = date('YmdHis') . '-' . Yii::$app->security->generateRandomString(16) . '.' . $extension;
        $fullPath = $directory . DIRECTORY_SEPARATOR . $name;
        if (!$file->saveAs($fullPath, false)) {
            throw new \RuntimeException('Unable to store the uploaded news cover.');
        }

        return 'uploads/news/' . $name;
    }

    /**
     * 只删除由本模块保存在 news 上传目录中的无人引用文件。
     *
     * @param string|null $path
     * @param bool $checkReferences
     */
    private function deleteStoredCover($path, $checkReferences = true)
    {
        if (!is_string($path)
            || !preg_match('~^uploads/news/[a-zA-Z0-9._-]+$~D', $path)) {
            return;
        }

        $directory = realpath(Yii::getAlias('@frontend/web/uploads/news'));
        $fullPath = realpath(Yii::getAlias('@frontend/web') . '/' . $path);
        if ($directory === false || $fullPath === false) {
            return;
        }
        if (strpos($fullPath, $directory . DIRECTORY_SEPARATOR) !== 0 || !is_file($fullPath)) {
            return;
        }
        if ($checkReferences && News::find()->where(['cover' => $path])->exists()) {
            return;
        }
        @unlink($fullPath);
    }

    /**
     * @param mixed $id
     * @return News
     */
    private function findModel($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('资讯不存在。');
        }
        $model = News::findOne((int) $id);
        if ($model === null) {
            throw new NotFoundHttpException('资讯不存在。');
        }
        return $model;
    }
}

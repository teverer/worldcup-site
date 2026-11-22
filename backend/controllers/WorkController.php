<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台作业文件增删改查和安全上传。
 */

namespace backend\controllers;

use Yii;
use backend\components\TeamAdminController;
use common\models\Work;
use yii\data\ActiveDataProvider;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class WorkController extends TeamAdminController
{
    public function actionIndex()
    {
        $query = Work::find();
        $q = Yii::$app->request->get('q');
        $type = Yii::$app->request->get('type');
        if (is_string($q) && trim($q) !== '') {
            $q = trim($q);
            $query->andWhere(['or', ['like', 'title', $q], ['like', 'description', $q]]);
        }
        if (is_string($type) && in_array($type, array_keys(Work::typeOptions()), true)) {
            $query->andWhere(['type' => $type]);
        } else {
            $type = '';
        }

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 20],
                'sort' => [
                    'defaultOrder' => ['create_time' => SORT_DESC, 'id' => SORT_DESC],
                    'attributes' => ['id', 'title', 'type', 'create_time'],
                ],
            ]),
            'q' => (string) $q,
            'type' => $type,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findWork($id)]);
    }

    public function actionCreate()
    {
        return $this->edit(new Work(), 'create');
    }

    public function actionUpdate($id)
    {
        return $this->edit($this->findWork($id), 'update');
    }

    public function actionDelete($id)
    {
        $model = $this->findWork($id);
        if ($model->delete() !== false) {
            Yii::$app->session->setFlash('success', '作业文件已删除。');
        } else {
            Yii::$app->session->setFlash('error', implode('；', $model->getFirstErrors()));
        }
        return $this->redirect(['index']);
    }

    protected function edit(Work $model, $view)
    {
        $oldFile = $model->isNewRecord ? null : $model->file_path;
        if ($model->load(Yii::$app->request->post())) {
            $model->workFile = UploadedFile::getInstance($model, 'workFile');
            if ($model->validate()) {
                $newFile = null;
                try {
                    if ($model->workFile !== null) {
                        $newFile = $this->storeWorkFile($model->workFile, $model->type);
                        $model->file_path = $newFile;
                    }
                    if ($model->save(false)) {
                        if ($newFile !== null && $oldFile !== null && $oldFile !== $newFile) {
                            $this->deleteWorkFile($oldFile);
                        }
                        Yii::$app->session->setFlash('success', '作业文件已保存。');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (\Throwable $exception) {
                    if ($newFile !== null) {
                        $this->deleteWorkFile($newFile);
                    }
                    Yii::error($exception, __METHOD__);
                    $model->addError('workFile', '文件保存失败，请检查 data 目录权限。');
                }
            }
        }

        return $this->render($view, ['model' => $model]);
    }

    protected function storeWorkFile(UploadedFile $file, $type)
    {
        $folder = $type === Work::TYPE_PERSONAL ? 'personal' : 'team';
        $directory = Work::storageRoot() . DIRECTORY_SEPARATOR . $folder;
        FileHelper::createDirectory($directory, 0775, true);

        $extension = strtolower($file->extension);
        $name = date('YmdHis') . '-' . Yii::$app->security->generateRandomString(20) . '.' . $extension;
        $fullPath = $directory . DIRECTORY_SEPARATOR . $name;
        if (!$file->saveAs($fullPath, false)) {
            throw new \RuntimeException('Unable to store the uploaded work file.');
        }

        return $folder . '/' . $name;
    }

    protected function deleteWorkFile($storedPath)
    {
        $model = new Work(['file_path' => $storedPath]);
        $fullPath = $model->resolveFilePath();
        if ($fullPath !== null) {
            @unlink($fullPath);
        }
    }

    protected function findWork($id)
    {
        return $this->findModel($id, Work::class, '作业文件不存在。');
    }
}

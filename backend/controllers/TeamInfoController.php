<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台团队介绍编辑。
 */

namespace backend\controllers;

use Yii;
use backend\components\TeamAdminController;
use common\models\TeamInfo;
use yii\web\UploadedFile;

class TeamInfoController extends TeamAdminController
{
    public function actionIndex()
    {
        return $this->render('index', ['model' => TeamInfo::singleton()]);
    }

    public function actionUpdate()
    {
        $model = TeamInfo::singleton();
        $oldLogo = $model->isNewRecord ? null : $model->getOldAttribute('logo');

        if ($model->load(Yii::$app->request->post())) {
            $model->logoFile = UploadedFile::getInstance($model, 'logoFile');
            if ($model->validate()) {
                $newLogo = null;
                try {
                    if ($model->logoFile !== null) {
                        $newLogo = $this->storeImage($model->logoFile);
                        $model->logo = $newLogo;
                    }
                    $model->id = 1;
                    if ($model->save(false)) {
                        if ($newLogo !== null && $oldLogo !== $newLogo) {
                            $this->deleteImage($oldLogo);
                        }
                        Yii::$app->session->setFlash('success', '团队信息已保存。');
                        return $this->redirect(['index']);
                    }
                } catch (\Throwable $exception) {
                    if ($newLogo !== null) {
                        $this->deleteImage($newLogo);
                    }
                    Yii::error($exception, __METHOD__);
                    $model->addError('logoFile', 'Logo 保存失败，请检查上传目录权限。');
                }
            }
        }

        return $this->render('update', ['model' => $model]);
    }
}

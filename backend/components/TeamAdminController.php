<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队后台管理公共权限、图片存储和查找逻辑。
 */

namespace backend\components;

use Yii;
use common\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

abstract class TeamAdminController extends Controller
{
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
                            return $identity !== null && (int) $identity->role >= User::ROLE_ADMIN;
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

    protected function findModel($id, $class, $message)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException($message);
        }
        $model = $class::findOne((int) $id);
        if ($model === null) {
            throw new NotFoundHttpException($message);
        }
        return $model;
    }

    protected function storeImage(UploadedFile $file)
    {
        $directory = Yii::getAlias('@frontend/web/uploads/team');
        FileHelper::createDirectory($directory, 0775, true);

        $extension = strtolower($file->extension);
        $name = date('YmdHis') . '-' . Yii::$app->security->generateRandomString(16) . '.' . $extension;
        if (!$file->saveAs($directory . DIRECTORY_SEPARATOR . $name, false)) {
            throw new \RuntimeException('Unable to store the uploaded image.');
        }

        return 'uploads/team/' . $name;
    }

    protected function deleteImage($path)
    {
        if (!is_string($path) || !preg_match('~^uploads/team/[A-Za-z0-9._-]+$~D', $path)) {
            return;
        }

        $directory = realpath(Yii::getAlias('@frontend/web/uploads/team'));
        $fullPath = realpath(Yii::getAlias('@frontend/web') . DIRECTORY_SEPARATOR . $path);
        if ($directory === false || $fullPath === false) {
            return;
        }
        if (strpos($fullPath, $directory . DIRECTORY_SEPARATOR) === 0) {
            @unlink($fullPath);
        }
    }
}

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 团队展示、成员展示和作业下载。
 */

namespace frontend\controllers;

use Yii;
use common\models\Member;
use common\models\TeamInfo;
use common\models\Work;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class TeamInfoController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', [
            'team' => TeamInfo::singleton(),
            'members' => Member::find()->orderBy(['id' => SORT_ASC])->all(),
        ]);
    }

    public function actionDownloads()
    {
        $works = Work::find()
            ->orderBy(['type' => SORT_ASC, 'create_time' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $teamWorks = [];
        $personalWorks = [];
        foreach ($works as $work) {
            if ($work->type === Work::TYPE_PERSONAL) {
                $personalWorks[] = $work;
            } else {
                $teamWorks[] = $work;
            }
        }

        return $this->render('downloads', [
            'teamWorks' => $teamWorks,
            'personalWorks' => $personalWorks,
        ]);
    }

    public function actionDownload($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('作业文件不存在。');
        }
        $model = Work::findOne((int) $id);
        if ($model === null) {
            throw new NotFoundHttpException('作业文件不存在。');
        }
        $path = $model->resolveFilePath();
        if ($path === null) {
            throw new NotFoundHttpException('作业文件不存在或已被移除。');
        }

        return Yii::$app->response->sendFile($path, $model->downloadName());
    }
}

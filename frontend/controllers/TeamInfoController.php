<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * Coding by 李行阳 2411353, 20261123
 * 模块:框架与团队主页 —— 关于本站（团队、统计口径、课程文档下载）。
 *
 * 2026-11-23 合并：原「团队展示」与「作业下载」两页内容重复、互相跳转，
 * 合并为一页「关于本站」；/team-info/downloads 301 到 /team-info/index#docs。
 */

namespace frontend\controllers;

use frontend\models\HomeStats;
use Yii;
use common\models\Member;
use common\models\TeamInfo;
use common\models\Work;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class TeamInfoController extends Controller
{
    /**
     * 关于本站：数据范围与口径、团队成员、课程文档下载。
     *
     * @return string
     */
    public function actionIndex()
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

        return $this->render('index', [
            'team' => TeamInfo::singleton(),
            'members' => Member::find()->orderBy(['id' => SORT_ASC])->all(),
            'teamWorks' => $teamWorks,
            'personalWorks' => $personalWorks,
            'overview' => HomeStats::overview(),
        ]);
    }

    /**
     * 旧的作业下载页：并入「关于本站」的文档区，保留地址兼容。
     *
     * @return \yii\web\Response
     */
    public function actionDownloads()
    {
        return $this->redirect(Url::to(['index']) . '#docs', 301);
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

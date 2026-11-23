<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次详情页与旧列表重定向
 */

namespace frontend\controllers;

use common\models\Tournament;
use frontend\models\TournamentDetail;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class TournamentController extends Controller
{
    public const STAGES = [
        '小组赛',
        '1/16决赛',
        '1/8决赛',
        '1/4决赛',
        '半决赛',
        '三四名决赛',
        '决赛',
    ];

    /**
     * 旧编年列表页已由根页记分牌墙取代,统一跳回根页。
     *
     * 旧视图与 tournament-index.css 暂留但不被引用,后续清理。
     *
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        return $this->redirect(['/site/index'], 301);
    }

    /**
     * 单届详情：一次性准备冠军、决赛、淘汰赛对阵、小组积分、统计与冠军路径。
     *
     * 所有业务计算集中在 TournamentDetail,视图只按固定结构渲染。
     * 固定 5 条批量查询 + 1 条 findOne,不产生 N+1。
     *
     * @param mixed $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('届次不存在。');
        }

        $model = Tournament::findOne((int) $id);
        if ($model === null) {
            throw new NotFoundHttpException('届次不存在。');
        }

        return $this->render('view', TournamentDetail::build($model));
    }
}

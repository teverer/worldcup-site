<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:届次与比赛 —— 四十年射手榜数据接口
 *
 * 只读 JSON；给统计页的射手榜懒加载用，只发曾进入任一届前十二的球员。
 */

namespace frontend\controllers;

use frontend\models\PlayerStarfield;
use yii\web\Controller;
use yii\web\Response;

class PlayerStarController extends Controller
{
    /**
     * @return array
     */
    public function actionData()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return PlayerStarfield::data();
    }
}

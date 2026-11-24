<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:纪录页 —— 跨届纪录（颁奖页，黑金）
 */

namespace frontend\controllers;

use frontend\models\Records;
use yii\web\Controller;

class RecordController extends Controller
{
    /**
     * 十一届的纪录：冠军、比分、单届产量与历史射手。
     *
     * 页面属于「颁奖」这一叙事层：黑底、金只给纪录数字，
     * 不引入新的图表，也不重复届次页的逐届统计。
     *
     * @return string
     */
    public function actionIndex()
    {
        $this->view->title = '纪录';
        $this->view->params['breadcrumbs'][] = ['label' => '纪录'];
        $this->view->registerCssFile('@web/css/record.css', [
            'depends' => [\yii\bootstrap\BootstrapAsset::class],
        ]);

        return $this->render('index', Records::all());
    }
}

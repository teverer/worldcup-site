<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:后台 —— 统一的列表操作列。
 *
 * 后台各表原来一半用「查看/编辑/删除」文字按钮、一半用裸图标，删除也不变红；
 * 这里收成同一套 11.5px 小按钮，删除统一二次确认。
 */

namespace common\components;

use yii\grid\ActionColumn;
use yii\helpers\Html;

class AdminActionColumn extends ActionColumn
{
    public $header = '操作';

    public $template = '{view} {update} {delete}';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->buttons = array_merge($this->buttons, [
            'view' => static function ($url) {
                return Html::a('查看', $url, ['class' => 'btn btn-xs btn-default']);
            },
            'update' => static function ($url) {
                return Html::a('编辑', $url, ['class' => 'btn btn-xs btn-primary']);
            },
            'delete' => static function ($url) {
                return Html::a('删除', $url, [
                    'class' => 'btn btn-xs btn-danger',
                    'data' => [
                        'method' => 'post',
                        'confirm' => '确定删除这条记录吗？',
                    ],
                ]);
            },
        ]);
    }
}

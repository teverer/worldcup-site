<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261028
 * 模块:留言与资讯 —— 前台发布资讯与后台全部资讯的筛选分页。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class NewsSearch extends Model
{
    public $q;
    public $status;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q', 'status'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['status', 'in', 'range' => array_keys(News::statusOptions())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'q' => '标题或作者',
            'status' => '发布状态',
        ];
    }

    /**
     * @param array $params
     * @param bool $onlyPublished
     * @return ActiveDataProvider
     */
    public function search($params, $onlyPublished = false)
    {
        $query = $onlyPublished ? News::findPublished() : News::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10],
            'sort' => [
                'defaultOrder' => ['publish_time' => SORT_DESC, 'id' => SORT_DESC],
                'attributes' => ['id', 'title', 'status', 'publish_time', 'create_time'],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->where('0=1');
            return $provider;
        }

        $query->andFilterWhere([
            'or',
            ['like', 'title', $this->q],
            ['like', 'author', $this->q],
        ]);
        if (!$onlyPublished) {
            $query->andFilterWhere(['status' => $this->status]);
        }

        return $provider;
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function searchPublished($params)
    {
        return $this->search($params, true);
    }
}

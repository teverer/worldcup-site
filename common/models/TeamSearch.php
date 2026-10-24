<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 前后台共用的球队搜索与分页。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class TeamSearch extends Model {
    public $q;
    public $continent;

    public function rules() {
        return [
            [['q', 'continent'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['continent', 'in', 'range' => Team::CONTINENTS],
        ];
    }

    public function attributeLabels() {
        return ['q' => '球队名称或代码', 'continent' => '所属大洲'];
    }

    public function search($params) {
        $query = Team::find();
        $provider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 12],
            'sort' => ['defaultOrder' => ['name' => SORT_ASC], 'attributes' => ['name', 'code', 'continent', 'id']]]);
        $this->load($params, '');
        if (!$this->validate()) {
            $query->where('0=1');
            return $provider;
        }
        $query->andFilterWhere(['continent' => $this->continent]);
        $query->andFilterWhere(['or', ['like', 'name', $this->q], ['like', 'code', $this->q]]);
        return $provider;
    }
}

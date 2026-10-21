<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 按实际参赛名单筛选球员，避免国籍与代表球队混淆。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class PlayerSearch extends Model {
    public $q;
    public $teamId;
    public $position;

    public function rules() {
        return [
            [['q', 'position'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['position', 'string', 'max' => 20],
            ['teamId', 'integer', 'min' => 1],
            ['teamId', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels() {
        return ['q' => '球员姓名或国籍', 'teamId' => '代表球队', 'position' => '主要位置'];
    }

    public function search($params) {
        $query = Player::find();
        $provider = new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 12],
            'sort' => ['defaultOrder' => ['name' => SORT_ASC, 'id' => SORT_ASC],
                'attributes' => ['id', 'name', 'nationality', 'position', 'birth_date']]]);
        $this->load($params, '');
        if (!$this->validate()) {
            $query->where('0=1');
            return $provider;
        }
        $query->andFilterWhere(['or', ['like', 'name', $this->q], ['like', 'nationality', $this->q]]);
        $query->andFilterWhere(['position' => $this->position]);
        if ($this->teamId !== null && $this->teamId !== '') {
            $query->andWhere(['id' => (new Query())->select('player_id')->from('{{%squad}}')->where(['team_id' => $this->teamId])]);
        }
        return $provider;
    }
}

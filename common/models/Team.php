<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球队资料与跨届名单查询。
 */

namespace common\models;

use common\validators\AssetPathValidator;
use yii\db\ActiveRecord;
use yii\db\Query;

class Team extends ActiveRecord {
    const CONTINENTS = ['亚洲', '欧洲', '非洲', '北美洲', '南美洲', '大洋洲'];

    public static function tableName() {
        return '{{%team}}';
    }

    public function rules() {
        return [
            [['name', 'code', 'continent', 'flag', 'logo', 'intro'], 'trim'],
            [['code', 'continent', 'flag', 'logo', 'intro'], 'default', 'value' => null],
            ['name', 'required'],
            ['name', 'string', 'max' => 50],
            ['name', 'unique'],
            ['code', 'filter', 'filter' => function ($value) { return $value === null ? null : strtoupper($value); }],
            ['code', 'match', 'pattern' => '/^[A-Z0-9]{2,10}$/D', 'message' => '代码应为 2 至 10 位英文字母或数字。'],
            ['code', 'unique'],
            ['continent', 'in', 'range' => self::CONTINENTS],
            [['flag', 'logo'], 'string', 'max' => 255],
            [['flag', 'logo'], AssetPathValidator::class],
            ['intro', 'string', 'max' => 15000],
        ];
    }

    public function attributeLabels() {
        return ['id' => '编号', 'name' => '球队名称', 'code' => 'FIFA 代码', 'continent' => '所属大洲',
            'flag' => '国旗路径', 'logo' => '队徽路径', 'intro' => '球队简介', 'create_time' => '创建时间'];
    }

    public static function options() {
        return self::find()->select('name')->orderBy(['name' => SORT_ASC])->indexBy('id')->column();
    }

    public function rosterQuery($tournamentId = null) {
        $ids = (new Query())->select('player_id')->from('{{%squad}}')->where(['team_id' => $this->id]);
        $ids->andFilterWhere(['tournament_id' => $tournamentId]);
        return Player::find()->where(['id' => $ids]);
    }

    public function tournaments() {
        return (new Query())->select(['t.id', 't.year', 't.name'])->distinct()
            ->from(['t' => '{{%tournament}}'])->innerJoin(['s' => '{{%squad}}'], 's.tournament_id = t.id')
            ->where(['s.team_id' => $this->id])->orderBy(['t.year' => SORT_DESC])->all();
    }

    public function beforeDelete() {
        if (!parent::beforeDelete()) {
            return false;
        }
        // 留言为多态关系，没有数据库外键，必须额外保护。
        if ((new Query())->from('{{%comment}}')->where(['target_type' => 'team', 'target_id' => $this->id])->exists()) {
            $this->addError('id', '球队已有留言，请先处理留言再删除。');
            return false;
        }
        return true;
    }
}

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球员资料及世界杯进球、助攻统计。
 */

namespace common\models;

use common\validators\AssetPathValidator;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;

class Player extends ActiveRecord {
    public static function tableName() {
        return '{{%player}}';
    }

    public function rules() {
        return [
            [['name', 'birth_date', 'nationality', 'position', 'photo', 'intro'], 'trim'],
            [['birth_date', 'nationality', 'position', 'photo', 'intro'], 'default', 'value' => null],
            ['name', 'required'],
            [['name', 'nationality'], 'string', 'max' => 50],
            ['position', 'string', 'max' => 20],
            ['birth_date', 'date', 'format' => 'php:Y-m-d', 'max' => date('Y-m-d'), 'tooBig' => '出生日期不能晚于今天。'],
            ['photo', 'string', 'max' => 255],
            ['photo', AssetPathValidator::class],
            ['intro', 'string', 'max' => 15000],
        ];
    }

    public function attributeLabels() {
        return ['id' => '编号', 'name' => '球员姓名', 'birth_date' => '出生日期', 'nationality' => '国籍',
            'position' => '主要位置', 'photo' => '照片路径', 'intro' => '球员简介', 'create_time' => '创建时间'];
    }

    public function participations() {
        return (new Query())->select(['t.year', 't.name', 'team_id' => 'tm.id', 'team_name' => 'tm.name',
            's.shirt_number', 's.position'])->from(['s' => '{{%squad}}'])
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = s.tournament_id')
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = s.team_id')
            ->where(['s.player_id' => $this->id])->orderBy(['t.year' => SORT_DESC])->all();
    }

    public function statistics() {
        // 不把乌龙球计为球员进球，也排除点球大战和未结束的比赛。
        return (new Query())->select(['t.year',
            'goals' => new Expression('SUM(CASE WHEN g.player_id = :playerId THEN 1 ELSE 0 END)', [':playerId' => $this->id]),
            'assists' => new Expression('SUM(CASE WHEN g.assist_player_id = :playerId THEN 1 ELSE 0 END)', [':playerId' => $this->id]),
        ])->from(['g' => '{{%goal}}'])->innerJoin(['m' => '{{%game}}'], 'm.id = g.game_id')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = m.tournament_id')
            ->where(['m.status' => 2, 'g.is_own_goal' => 0, 'g.is_shootout' => 0])
            ->andWhere(['or', ['g.player_id' => $this->id], ['g.assist_player_id' => $this->id]])
            ->groupBy('t.year')->orderBy(['t.year' => SORT_DESC])->all();
    }

    public function beforeDelete() {
        if (!parent::beforeDelete()) {
            return false;
        }
        if ((new Query())->from('{{%comment}}')->where(['target_type' => 'player', 'target_id' => $this->id])->exists()) {
            $this->addError('id', '球员已有留言，请先处理留言再删除。');
            return false;
        }
        return true;
    }
}

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 某届赛事参赛队伍模型。
 *
 * 对应 worldcup.tournament_team 表,记录球队在某届赛事中的分组、教练与排名。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $tournament_id 届次
 * @property int $team_id 球队
 * @property string|null $group_name 小组
 * @property string|null $coach 主教练
 * @property int|null $fifa_rank 参赛时 FIFA 排名
 * @property int|null $final_rank 最终名次
 * @property string $create_time 创建时间
 */
class TournamentTeam extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tournament_team}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tournament_id', 'team_id', 'group_name', 'coach', 'fifa_rank', 'final_rank'], 'trim'],
            [['group_name', 'coach', 'fifa_rank', 'final_rank'], 'default', 'value' => null],
            [['tournament_id', 'team_id'], 'required'],
            [['tournament_id', 'team_id'], 'integer', 'min' => 1],
            ['tournament_id', 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id',
                'message' => '所选届次不存在。'],
            ['team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id',
                'message' => '所选球队不存在。'],
            ['group_name', 'string', 'max' => 10],
            ['coach', 'string', 'max' => 50],
            ['fifa_rank', 'integer', 'min' => 1, 'max' => 300,
                'message' => 'FIFA 排名必须是整数。',
                'tooSmall' => 'FIFA 排名不能小于 1。', 'tooBig' => 'FIFA 排名不能大于 300。'],
            ['final_rank', 'integer', 'min' => 1, 'max' => 64,
                'message' => '最终名次必须是整数。',
                'tooSmall' => '最终名次不能小于 1。', 'tooBig' => '最终名次不能大于 64。'],
            ['team_id', 'validateUniqueTournamentTeam'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'tournament_id' => '届次',
            'team_id' => '球队',
            'group_name' => '小组',
            'coach' => '主教练',
            'fifa_rank' => '参赛时 FIFA 排名',
            'final_rank' => '最终名次',
            'create_time' => '创建时间',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'group_name' => '例如 A、B,不同届次的小组数量不同。',
            'coach' => '该届赛事期间的主教练。',
            'fifa_rank' => '参赛时的 FIFA 世界排名。',
            'final_rank' => '最终名次,1 表示冠军。',
        ];
    }

    /**
     * 同一届赛事中一支球队只能出现一次。
     *
     * @param string $attribute
     */
    public function validateUniqueTournamentTeam($attribute)
    {
        if ($this->hasErrors('tournament_id') || $this->hasErrors('team_id')) {
            return;
        }
        if ($this->tournament_id === null || $this->team_id === null) {
            return;
        }
        $query = static::find()->where([
            'tournament_id' => $this->tournament_id,
            'team_id' => $this->team_id,
        ]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, '该球队已经报名参加本届赛事,请勿重复添加。');
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTournament()
    {
        return $this->hasOne(Tournament::class, ['id' => 'tournament_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    /**
     * @return \yii\db\ActiveQuery 本队本届参赛名单
     */
    public function getSquads()
    {
        return $this->hasMany(Squad::class, [
            'tournament_id' => 'tournament_id',
            'team_id' => 'team_id',
        ]);
    }
}

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 进球记录模型。
 *
 * 对应 worldcup.goal 表,记录比赛中的进球、助攻、点球、乌龙与点球大战进球。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $game_id 比赛
 * @property int $team_id 进球归属球队(乌龙球填受益方)
 * @property int $player_id 进球球员(乌龙球填防守方球员)
 * @property int|null $assist_player_id 助攻球员
 * @property int|null $minute 进球分钟
 * @property int $is_penalty 是否点球
 * @property int $is_own_goal 是否乌龙
 * @property int $is_shootout 是否点球大战进球
 * @property string $create_time 创建时间
 */
class Goal extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goal}}';
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
            [['game_id', 'team_id', 'player_id', 'assist_player_id', 'minute',
                'is_penalty', 'is_own_goal', 'is_shootout'], 'trim'],
            ['assist_player_id', 'default', 'value' => null],
            [['is_penalty', 'is_own_goal', 'is_shootout'], 'default', 'value' => 0],
            [['game_id', 'team_id', 'player_id'], 'required'],
            [['game_id', 'team_id', 'player_id'], 'integer', 'min' => 1],
            ['game_id', 'exist', 'targetClass' => Game::class, 'targetAttribute' => 'id',
                'message' => '所选比赛不存在。'],
            ['team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id',
                'message' => '所选进球归属球队不存在。'],
            ['player_id', 'exist', 'targetClass' => Player::class, 'targetAttribute' => 'id',
                'message' => '所选进球球员不存在。'],
            ['assist_player_id', 'integer', 'min' => 1],
            ['assist_player_id', 'exist', 'targetClass' => Player::class, 'targetAttribute' => 'id',
                'message' => '所选助攻球员不存在。'],
            ['minute', 'integer', 'min' => 1, 'max' => 130,
                'message' => '进球分钟必须是 1 至 130 之间的整数。',
                'tooSmall' => '进球分钟不能小于 1。', 'tooBig' => '进球分钟不能大于 130。'],
            [['is_penalty', 'is_own_goal', 'is_shootout'], 'integer', 'min' => 0, 'max' => 1,
                'message' => '布尔字段只能是 0 或 1。'],
            ['team_id', 'validateTeamBelongsToGame'],
            ['assist_player_id', 'validateAssistPlayer'],
            ['is_own_goal', 'validateGoalFlags'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'game_id' => '比赛',
            'team_id' => '进球归属球队',
            'player_id' => '进球球员',
            'assist_player_id' => '助攻球员',
            'minute' => '进球分钟',
            'is_penalty' => '是否点球',
            'is_own_goal' => '是否乌龙',
            'is_shootout' => '是否点球大战',
            'create_time' => '创建时间',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'team_id' => '乌龙球请选择受益球队。',
            'player_id' => '乌龙球请选择防守方球员。',
            'assist_player_id' => '没有助攻时留空。',
            'minute' => '常规时间 1 至 90,加时赛可填至 120,点球大战可填至 130。',
        ];
    }

    /**
     * 进球归属球队必须是本场比赛的主队或客队。
     *
     * @param string $attribute
     */
    public function validateTeamBelongsToGame($attribute)
    {
        if ($this->hasErrors('game_id') || $this->hasErrors('team_id')) {
            return;
        }
        if ($this->game_id === null || $this->team_id === null) {
            return;
        }
        $game = Game::find()
            ->select(['home_team_id', 'away_team_id'])
            ->where(['id' => $this->game_id])
            ->asArray()
            ->one();
        if ($game === null) {
            return;
        }
        $teamIds = [(int) $game['home_team_id'], (int) $game['away_team_id']];
        if (!in_array((int) $this->team_id, $teamIds, true)) {
            $this->addError($attribute, '进球归属球队必须是本场比赛的参赛球队。');
        }
    }

    /**
     * 助攻球员不能与进球球员相同。
     *
     * @param string $attribute
     */
    public function validateAssistPlayer($attribute)
    {
        if ($this->assist_player_id === null || $this->assist_player_id === '') {
            return;
        }
        if ($this->hasErrors('player_id') || $this->hasErrors('assist_player_id')) {
            return;
        }
        if ((int) $this->assist_player_id === (int) $this->player_id) {
            $this->addError($attribute, '助攻球员不能与进球球员相同。');
        }
    }

    /**
     * 点球与乌龙球不能同时标记。
     *
     * @param string $attribute
     */
    public function validateGoalFlags($attribute)
    {
        if ((int) $this->is_penalty === 1 && (int) $this->is_own_goal === 1) {
            $this->addError($attribute, '点球和乌龙球不能同时标记。');
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGame()
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'player_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssistPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'assist_player_id']);
    }
}

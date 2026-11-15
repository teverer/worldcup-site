<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 某届赛事参赛名单模型。
 *
 * 对应 worldcup.squad 表,记录球员在某届赛事代表哪支球队以及球衣号。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $tournament_id 届次
 * @property int $team_id 球队
 * @property int $player_id 球员
 * @property int|null $shirt_number 该届球衣号
 * @property string|null $position 该届位置
 * @property string $create_time 创建时间
 */
class Squad extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%squad}}';
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
            [['tournament_id', 'team_id', 'player_id', 'shirt_number', 'position'], 'trim'],
            [['shirt_number', 'position'], 'default', 'value' => null],
            [['tournament_id', 'team_id', 'player_id'], 'required'],
            [['tournament_id', 'team_id', 'player_id'], 'integer', 'min' => 1],
            ['tournament_id', 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id',
                'message' => '所选届次不存在。'],
            ['team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id',
                'message' => '所选球队不存在。'],
            ['player_id', 'exist', 'targetClass' => Player::class, 'targetAttribute' => 'id',
                'message' => '所选球员不存在。'],
            ['shirt_number', 'integer', 'min' => 1, 'max' => 99,
                'message' => '球衣号必须是 1 至 99 之间的整数。',
                'tooSmall' => '球衣号不能小于 1。', 'tooBig' => '球衣号不能大于 99。'],
            ['position', 'string', 'max' => 20],
            ['team_id', 'validateTeamInTournament'],
            ['player_id', 'validatePlayerUnique'],
            ['shirt_number', 'validateShirtUnique'],
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
            'player_id' => '球员',
            'shirt_number' => '球衣号',
            'position' => '该届位置',
            'create_time' => '创建时间',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'shirt_number' => '填写 1 至 99;同一届同一球队内不能重号。',
            'position' => '可留空,默认参考球员的主要位置。',
        ];
    }

    /**
     * 球队必须已经报名参加所选届次,避免名单与参赛队脱节。
     *
     * @param string $attribute
     */
    public function validateTeamInTournament($attribute)
    {
        if ($this->hasErrors('tournament_id') || $this->hasErrors('team_id')) {
            return;
        }
        if ($this->tournament_id === null || $this->team_id === null) {
            return;
        }
        $exists = TournamentTeam::find()->where([
            'tournament_id' => $this->tournament_id,
            'team_id' => $this->team_id,
        ])->exists();
        if (!$exists) {
            $this->addError($attribute, '该球队尚未报名参加所选届次,请先在“参赛队”中添加。');
        }
    }

    /**
     * 同一球员在同一届赛事只能属于一支球队。
     *
     * @param string $attribute
     */
    public function validatePlayerUnique($attribute)
    {
        if ($this->hasErrors('tournament_id') || $this->hasErrors('player_id')) {
            return;
        }
        if ($this->tournament_id === null || $this->player_id === null) {
            return;
        }
        $query = static::find()->where([
            'tournament_id' => $this->tournament_id,
            'player_id' => $this->player_id,
        ]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, '同一球员在同一届赛事只能属于一支球队,该球员已在本届名单中。');
        }
    }

    /**
     * 同一届同一球队内球衣号不能重复。
     *
     * @param string $attribute
     */
    public function validateShirtUnique($attribute)
    {
        if ($this->shirt_number === null || $this->shirt_number === '') {
            return;
        }
        if ($this->hasErrors('tournament_id') || $this->hasErrors('team_id')
            || $this->hasErrors('shirt_number')) {
            return;
        }
        $query = static::find()->where([
            'tournament_id' => $this->tournament_id,
            'team_id' => $this->team_id,
            'shirt_number' => $this->shirt_number,
        ]);
        if (!$this->isNewRecord) {
            $query->andWhere(['<>', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, '该球衣号在本届本队已被占用,请更换号码。');
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
     * @return \yii\db\ActiveQuery
     */
    public function getPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'player_id']);
    }
}

<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 比赛模型。
 *
 * 对应 worldcup.game 表(不使用 match 作为类名或表名,避免 PHP 8 保留字)。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $tournament_id 届次
 * @property int $home_team_id 主队
 * @property int $away_team_id 客队
 * @property int|null $home_score 主队比分(120 分钟含加时)
 * @property int|null $away_score 客队比分(120 分钟含加时)
 * @property int|null $home_penalty_score 点球大战主队比分
 * @property int|null $away_penalty_score 点球大战客队比分
 * @property string|null $match_time 比赛时间
 * @property string $stage 阶段
 * @property string|null $venue 比赛场地
 * @property int $status 状态 0未赛 1进行中 2已结束
 * @property string $create_time 创建时间
 */
class Game extends ActiveRecord
{
    const STAGE_GROUP = '小组赛';
    const STAGE_ROUND_OF_32 = '1/16决赛';
    const STAGE_ROUND_OF_16 = '1/8决赛';
    const STAGE_QUARTER_FINAL = '1/4决赛';
    const STAGE_SEMI_FINAL = '半决赛';
    const STAGE_THIRD_PLACE = '三四名决赛';
    const STAGE_FINAL = '决赛';

    const STATUS_PENDING = 0;
    const STATUS_LIVE = 1;
    const STATUS_FINISHED = 2;

    /**
     * 固定的比赛阶段,后台下拉框只能从中选择。
     */
    const STAGES = [
        self::STAGE_GROUP,
        self::STAGE_ROUND_OF_32,
        self::STAGE_ROUND_OF_16,
        self::STAGE_QUARTER_FINAL,
        self::STAGE_SEMI_FINAL,
        self::STAGE_THIRD_PLACE,
        self::STAGE_FINAL,
    ];

    const STATUSES = [
        self::STATUS_PENDING => '未赛',
        self::STATUS_LIVE => '进行中',
        self::STATUS_FINISHED => '已结束',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%game}}';
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
            [['tournament_id', 'home_team_id', 'away_team_id', 'home_score', 'away_score',
                'home_penalty_score', 'away_penalty_score', 'match_time', 'stage', 'venue', 'status'], 'trim'],
            [['home_score', 'away_score', 'home_penalty_score', 'away_penalty_score', 'match_time', 'venue'],
                'default', 'value' => null],
            ['status', 'default', 'value' => self::STATUS_PENDING],
            [['tournament_id', 'home_team_id', 'away_team_id', 'stage', 'status'], 'required'],
            [['tournament_id', 'home_team_id', 'away_team_id'], 'integer', 'min' => 1],
            ['status', 'integer'],
            ['tournament_id', 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id',
                'message' => '所选届次不存在。'],
            ['home_team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id',
                'message' => '所选主队不存在。'],
            ['away_team_id', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id',
                'message' => '所选客队不存在。'],
            ['away_team_id', 'validateDifferentTeams'],
            [['home_score', 'away_score'], 'integer', 'min' => 0, 'max' => 99,
                'message' => '比分必须是 0 至 99 之间的整数。',
                'tooSmall' => '比分不能小于 0。', 'tooBig' => '比分不能大于 99。'],
            [['home_penalty_score', 'away_penalty_score'], 'integer', 'min' => 0, 'max' => 30,
                'message' => '点球大战比分必须是 0 至 30 之间的整数。',
                'tooSmall' => '点球大战比分不能小于 0。', 'tooBig' => '点球大战比分不能大于 30。'],
            ['match_time', 'filter', 'filter' => function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }
                $value = str_replace('T', ' ', trim((string) $value));
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
                    $value .= ':00';
                }
                return $value;
            }],
            ['match_time', 'validateDateTimeValue'],
            ['stage', 'in', 'range' => self::STAGES, 'message' => '比赛阶段不在允许的范围内。'],
            ['venue', 'string', 'max' => 50],
            ['status', 'in', 'range' => array_keys(self::STATUSES), 'message' => '比赛状态不在允许的范围内。'],
            ['home_score', 'validateFinishedScore', 'skipOnEmpty' => false],
            ['away_score', 'validateFinishedScore', 'skipOnEmpty' => false],
            ['home_penalty_score', 'validatePenaltyScores', 'skipOnEmpty' => false],
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
            'home_team_id' => '主队',
            'away_team_id' => '客队',
            'home_score' => '主队比分',
            'away_score' => '客队比分',
            'home_penalty_score' => '点球大战主队比分',
            'away_penalty_score' => '点球大战客队比分',
            'match_time' => '比赛时间',
            'stage' => '比赛阶段',
            'venue' => '比赛场地',
            'status' => '比赛状态',
            'create_time' => '创建时间',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [];
    }

    /**
     * @return array 阶段下拉框选项
     */
    public static function stageOptions()
    {
        return array_combine(self::STAGES, self::STAGES);
    }

    /**
     * @return array 状态下拉框选项
     */
    public static function statusOptions()
    {
        return self::STATUSES;
    }

    /**
     * 校验比赛时间格式。PHP 8.2 下 Yii 2.0.32 的 datetime 验证器存在兼容性问题,
     * 这里改用正则与 checkdate() 做等价校验。
     *
     * @param string $attribute
     */
    public function validateDateTimeValue($attribute)
    {
        if ($this->match_time === null || $this->match_time === '') {
            return;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/D', $this->match_time, $matches)
            || !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
            || (int) $matches[4] > 23 || (int) $matches[5] > 59 || (int) $matches[6] > 59) {
            $this->addError($attribute, '比赛时间格式不正确,应为 YYYY-MM-DD HH:MM:SS。');
        }
    }

    /**
     * 主队和客队不能相同。
     *
     * @param string $attribute
     */
    public function validateDifferentTeams($attribute)
    {
        if ($this->hasErrors('home_team_id') || $this->hasErrors('away_team_id')) {
            return;
        }
        if ($this->home_team_id === null || $this->away_team_id === null) {
            return;
        }
        if ((int) $this->home_team_id === (int) $this->away_team_id) {
            $this->addError($attribute, '主队和客队不能是同一支球队。');
        }
    }

    /**
     * 比赛状态为已结束时,主客队比分都必须填写。
     *
     * @param string $attribute
     */
    public function validateFinishedScore($attribute)
    {
        if ((int) $this->status !== self::STATUS_FINISHED) {
            return;
        }
        if ($this->$attribute === null || $this->$attribute === '') {
            $team = $attribute === 'home_score' ? '主队' : '客队';
            $this->addError($attribute, '比赛状态为“已结束”时必须填写' . $team . '比分。');
        }
    }

    /**
     * 点球大战比分必须成对填写,且只在决赛阶段或常规比分平局时允许。
     *
     * @param string $attribute
     */
    public function validatePenaltyScores($attribute)
    {
        $homeEmpty = $this->home_penalty_score === null || $this->home_penalty_score === '';
        $awayEmpty = $this->away_penalty_score === null || $this->away_penalty_score === '';
        if ($homeEmpty && $awayEmpty) {
            return;
        }
        if ($homeEmpty || $awayEmpty) {
            $this->addError($attribute, '点球大战比分必须同时填写主队和客队。');
            return;
        }
        if ($this->home_score === null || $this->home_score === ''
            || $this->away_score === null || $this->away_score === '') {
            $this->addError($attribute, '填写点球大战比分前请先填写常规比分。');
            return;
        }
        if ($this->hasErrors('home_score') || $this->hasErrors('away_score')) {
            return;
        }
        $isDraw = (int) $this->home_score === (int) $this->away_score;
        if ($this->stage !== self::STAGE_FINAL && !$isDraw) {
            $this->addError($attribute, '点球大战比分只允许在决赛阶段或常规比分平局时填写。');
        }
    }

    /**
     * @return bool 比赛是否已结束
     */
    public function isFinished()
    {
        return (int) $this->status === self::STATUS_FINISHED;
    }

    /**
     * @return string 状态中文文本
     */
    public function getStatusText()
    {
        return isset(self::STATUSES[(int) $this->status]) ? self::STATUSES[(int) $this->status] : '未知';
    }

    /**
     * @return string 比分展示文本,包含点球大战比分
     */
    public function getScoreText()
    {
        if ($this->home_score === null && $this->away_score === null) {
            return (int) $this->status === self::STATUS_PENDING ? '未开赛' : '比分未录入';
        }
        $text = $this->scorePart($this->home_score) . ' : ' . $this->scorePart($this->away_score);
        if ($this->home_penalty_score !== null || $this->away_penalty_score !== null) {
            $text .= '（点球 ' . $this->scorePart($this->home_penalty_score)
                . ' : ' . $this->scorePart($this->away_penalty_score) . '）';
        }
        return $text;
    }

    /**
     * @return string 主队名称
     */
    public function getHomeTeamName()
    {
        return $this->homeTeam === null ? '未知主队' : $this->homeTeam->name;
    }

    /**
     * @return string 客队名称
     */
    public function getAwayTeamName()
    {
        return $this->awayTeam === null ? '未知客队' : $this->awayTeam->name;
    }

    /**
     * @return string “主队 vs 客队”文本
     */
    public function getMatchTitle()
    {
        return $this->getHomeTeamName() . ' vs ' . $this->getAwayTeamName();
    }

    /**
     * @param int|null $value
     * @return string
     */
    private function scorePart($value)
    {
        return $value === null || $value === '' ? '-' : (string) $value;
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
    public function getHomeTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'home_team_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAwayTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'away_team_id']);
    }

    /**
     * @return \yii\db\ActiveQuery 本场比赛的进球记录
     */
    public function getGoals()
    {
        return $this->hasMany(Goal::class, ['game_id' => 'id'])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC]);
    }
}

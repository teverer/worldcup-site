<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 世界杯届次模型。
 *
 * 对应 worldcup.tournament 表,记录每届赛事的年份、东道主、赛制与积分规则。
 */

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $year 年份
 * @property string $name 官方名称
 * @property string|null $host_country 东道主
 * @property int|null $team_count 参赛队数
 * @property string|null $format 赛制说明
 * @property int $win_points 胜一场积分
 * @property string|null $start_date 开幕日期
 * @property string|null $end_date 闭幕日期
 * @property string $create_time 创建时间
 */
class Tournament extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tournament}}';
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
            [['year', 'name', 'host_country', 'team_count', 'format', 'win_points', 'start_date', 'end_date'], 'trim'],
            [['host_country', 'team_count', 'format', 'start_date', 'end_date'], 'default', 'value' => null],
            [['year', 'name'], 'required'],
            ['year', 'integer', 'message' => '年份必须是整数。',
                'tooSmall' => '年份不能小于 1900。', 'tooBig' => '年份不能大于 2100。'],
            ['year', 'unique', 'message' => '该年份的届次已经存在,请勿重复添加。'],
            ['name', 'string', 'max' => 100],
            ['host_country', 'string', 'max' => 100],
            ['team_count', 'integer', 'message' => '参赛队数必须是整数。',
                'tooSmall' => '参赛队数不能小于 16。', 'tooBig' => '参赛队数不能大于 64。'],
            ['format', 'string', 'max' => 100],
            ['win_points', 'default', 'value' => 3],
            ['win_points', 'integer'],
            ['win_points', 'in', 'range' => [1, 2, 3], 'message' => '胜场积分只能是 1、2 或 3 分。'],
            [['start_date', 'end_date'], 'validateDateValue'],
            ['end_date', 'validateDateRange'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'year' => '年份',
            'name' => '官方名称',
            'host_country' => '东道主',
            'team_count' => '参赛队数',
            'format' => '赛制说明',
            'win_points' => '胜场积分',
            'start_date' => '开幕日期',
            'end_date' => '闭幕日期',
            'create_time' => '创建时间',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeHints()
    {
        return [
            'year' => '例如 2022,同一届只能出现一次。',
            'name' => '例如 2022年卡塔尔世界杯。',
            'host_country' => '多国合办时用“/”分隔,如 韩国/日本。',
            'team_count' => '常见为 24、32 或 48。',
            'format' => '例如 32队/8组/小组前2晋级。',
            'win_points' => '1986、1990 两届为 2 分,其余为 3 分。',
        ];
    }

    /**
     * 校验日期格式。PHP 8.2 下 Yii 2.0.32 的 date 验证器存在兼容性问题,
     * 这里改用 checkdate() 做等价校验。
     *
     * @param string $attribute
     */
    public function validateDateValue($attribute)
    {
        if ($this->$attribute === null || $this->$attribute === '') {
            return;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $this->$attribute, $matches)
            || !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            $this->addError($attribute, '日期格式不正确,应为 YYYY-MM-DD。');
        }
    }

    /**
     * 校验闭幕日期不能早于开幕日期。
     *
     * @param string $attribute
     */
    public function validateDateRange($attribute)
    {
        if ($this->hasErrors('start_date') || $this->hasErrors('end_date')) {
            return;
        }
        if ($this->start_date === null || $this->start_date === ''
            || $this->end_date === null || $this->end_date === '') {
            return;
        }
        if (strtotime($this->end_date) < strtotime($this->start_date)) {
            $this->addError($attribute, '闭幕日期不能早于开幕日期。');
        }
    }

    /**
     * @return \yii\db\ActiveQuery 本届参赛队伍
     */
    public function getTournamentTeams()
    {
        return $this->hasMany(TournamentTeam::class, ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery 本届比赛
     */
    public function getGames()
    {
        return $this->hasMany(Game::class, ['tournament_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery 本届参赛名单
     */
    public function getSquads()
    {
        return $this->hasMany(Squad::class, ['tournament_id' => 'id']);
    }

    /**
     * 后台下拉框使用的“届次编号 => 届次名称”映射。
     *
     * @return array
     */
    public static function options()
    {
        return static::find()
            ->select(['name'])
            ->orderBy(['year' => SORT_DESC])
            ->indexBy('id')
            ->column();
    }
}
